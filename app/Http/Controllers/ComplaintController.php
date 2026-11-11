<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComplaintRequest;
use App\Http\Requests\UpdateComplaintStatusRequest;
use App\Models\Complaint;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function index(Request $request): View
    {
        // Otomatis bersihkan pengaduan yang sudah diperbaiki lebih dari 24 jam
        Complaint::purgeResolvedOverdue();

        $user = Auth::user();
        $query = Complaint::with(['room.pic', 'reporter', 'handler', 'event']);

        // REVISI POIN 6: Filter Riwayat Pengaduan Berdasarkan Akun Login
        // Hanya Admin yang dapat memantau seluruh laporan pengaduan sarpras sekolah.
        // Pengguna non-admin hanya dapat melihat riwayat pengaduan yang mereka buat sendiri (WHERE reporter_id = current_user_id).
        if (! $user->isAdmin()) {
            if ($user->isPic()) {
                // PIC dapat melihat aduan pada ruangan yang dikelolanya atau aduan yang dibuatnya sendiri
                $roomIds = Room::where('pic_id', $user->id)->pluck('id');
                $query->where(function ($sub) use ($roomIds, $user) {
                    $sub->whereIn('room_id', $roomIds)
                        ->orWhere('reporter_id', $user->id);
                });
            } else {
                // Pengguna biasa / Guru hanya melihat aduan milik akun yang sedang login
                $query->where('reporter_id', $user->id);
            }
        }

        if ($request->filled('q')) {
            $keyword = $request->query('q');
            $query->where(function ($sub) use ($keyword) {
                $sub->where('description', 'like', "%{$keyword}%")
                    ->orWhereHas('room', fn ($r) => $r->where('room_name', 'like', "%{$keyword}%"))
                    ->orWhereHas('reporter', fn ($u) => $u->where('fullname', 'like', "%{$keyword}%"));
            });
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->query('room_id'));
        }

        if ($request->filled('status_repair')) {
            $query->where('status_repair', $request->query('status_repair'));
        }

        // REVISI ADMIN 5: Pagination 5 laporan per halaman
        $complaints = $query->orderBy('reported_at', 'desc')->paginate(5)->withQueryString();
        $rooms = Room::where('is_active', true)->orderBy('room_name')->get();

        return view('complaints.index', compact('complaints', 'rooms'));
    }

    public function create(Request $request): View
    {
        $rooms = Room::where('is_active', true)->orderBy('room_name')->get();
        $selectedEvent = null;

        // REVISI POIN 3: Auto-fill data peminjaman terkait
        if ($request->filled('event_id')) {
            $selectedEvent = \App\Models\Event::with('room')->find($request->event_id);
        }

        return view('complaints.create', compact('rooms', 'selectedEvent'));
    }

    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        $photoPath = null;

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = 'bukti_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationFolder = storage_path('app/public/complaints');

            if (!file_exists($destinationFolder)) {
                mkdir($destinationFolder, 0755, true);
            }

            $targetFilePath = $destinationFolder . DIRECTORY_SEPARATOR . $filename;
            $this->compressAndSaveImage($file->getRealPath(), $targetFilePath, $file->getClientMimeType());

            $photoPath = 'complaints/' . $filename;
        }

        $room = Room::findOrFail($request->room_id);

        $complaint = Complaint::create([
            'room_id' => $room->id,
            'event_id' => $request->event_id,
            'reporter_id' => Auth::id(),
            'description' => $request->description,
            'photo_path' => $photoPath,
            'status_repair' => 'Belum Diperbaiki',
            'handler_id' => $room->pic_id, // Default otomatis ditugaskan ke PIC ruangan terkait
            'reported_at' => Carbon::now(),
        ]);

        return redirect()->route('complaints.index')
            ->with('success', "Laporan kerusakan di {$room->room_name} berhasil dikirim dan didelegasikan ke PIC {$room->pic->fullname}.");
    }

    public function updateStatus(UpdateComplaintStatusRequest $request, Complaint $complaint): RedirectResponse
    {
        $user = Auth::user();

        // Administrator / Waka tidak dapat mengubah status perbaikan secara langsung
        if ($user->isAdmin()) {
            return back()->with('error', 'Akses ditolak: Administrator/Waka Sarpras tidak dapat mengubah status perbaikan secara langsung. Hak pembaruan status hanya dimiliki oleh staf PIC ruangan terkait. Silakan gunakan tombol peringatan WhatsApp untuk menghubungi PIC.');
        }

        // VALIDASI OTORISASI PIC: Hanya staf PIC ruangan terkait yang berwenang
        if ((int) $complaint->room->pic_id !== (int) $user->id) {
            return back()->with('error', "Akses ditolak: Anda bukan staf PIC yang ditugaskan mengelola ruangan '{$complaint->room->room_name}'.");
        }

        // Pengaduan yang sudah berstatus 'Sudah Diperbaiki' terkunci dan tidak dapat diubah lagi
        if ($complaint->status_repair === 'Sudah Diperbaiki') {
            return back()->with('error', 'Pengaduan yang sudah diperbaiki tidak dapat diubah statusnya.');
        }

        // REVISI PIC 4: Tangani keterangan perbaikan saat status diperbarui
        $dataToUpdate = [
            'status_repair' => $request->status_repair,
            'handler_id' => $user->id,
        ];

        if ($request->status_repair === 'Sudah Diperbaiki') {
            $dataToUpdate['repair_notes'] = $request->repair_notes ?: ($complaint->repair_notes ?: 'Fasilitas telah selesai diperbaiki dan siap digunakan kembali.');
            $dataToUpdate['repaired_at'] = $request->filled('repaired_at') ? Carbon::parse($request->repaired_at) : ($complaint->repaired_at ?: Carbon::now());
        } elseif ($request->filled('repair_notes')) {
            $dataToUpdate['repair_notes'] = $request->repair_notes;
        }

        $complaint->update($dataToUpdate);

        return back()->with('success', "Status perbaikan sarpras di '{$complaint->room->room_name}' berhasil diubah menjadi: '{$complaint->status_repair}'.");
    }

    public function destroy(Complaint $complaint): RedirectResponse
    {
        $user = Auth::user();

        // Otorisasi: Admin, pelapor asli, atau PIC ruangan penanggung jawab dapat menghapus laporan
        $isPicRuang = (int) ($complaint->room?->pic_id) === (int) $user->id;
        $isReporter = (int) $complaint->reporter_id === (int) $user->id;

        if (!$user->isAdmin() && !$isReporter && !$isPicRuang) {
            return back()->with('error', 'Akses ditolak: Anda tidak memiliki hak akses untuk menghapus laporan kerusakan ini.');
        }

        // Hapus file fisik foto bukti dari disk storage bila ada
        if ($complaint->photo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($complaint->photo_path);
        }

        $complaint->delete();

        return back()->with('success', "Laporan kerusakan sarpras di '{$complaint->room?->room_name}' berhasil dihapus dari sistem.");
    }

    /**
     * Kompresi dan simpan foto kamera gawai menggunakan GD library bawaan PHP
     */
    private function compressAndSaveImage(string $sourcePath, string $targetPath, string $mimeType): void
    {
        $image = match ($mimeType) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
            default => null,
        };

        if (!$image) {
            copy($sourcePath, $targetPath);
            return;
        }

        $origWidth = imagesx($image);
        $origHeight = imagesy($image);
        $maxDimension = 1200;

        if ($origWidth > $maxDimension || $origHeight > $maxDimension) {
            if ($origWidth >= $origHeight) {
                $newWidth = $maxDimension;
                $newHeight = (int) round(($origHeight / $origWidth) * $maxDimension);
            } else {
                $newHeight = $maxDimension;
                $newWidth = (int) round(($origWidth / $origHeight) * $maxDimension);
            }

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($image);
            $image = $resized;
        }

        imagejpeg($image, $targetPath, 80);
        imagedestroy($image);
    }
}
