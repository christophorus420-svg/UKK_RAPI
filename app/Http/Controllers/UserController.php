<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Menampilkan daftar pengguna terdaftar
     */
    public function index(Request $request): View
    {
        $query = User::with('role')->withCount(['rooms', 'events', 'complaints']);

        // Filter pencarian teks
        if ($request->filled('q')) {
            $keyword = trim($request->query('q'));
            $query->where(function ($sub) use ($keyword) {
                $sub->where('username', 'like', "%{$keyword}%")
                    ->orWhere('fullname', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone_number', 'like', "%{$keyword}%");
            });
        }

        // Filter role
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->query('role_id'));
        }

        $users = $query->orderBy('role_id')->orderBy('fullname')->paginate(12)->withQueryString();
        $roles = Role::orderBy('id')->get();

        // Hitung total ringkasan per role
        $totalUsers = User::count();
        $totalAdmin = User::whereHas('role', fn ($q) => $q->where('role_name', 'admin'))->count();
        $totalPic = User::whereHas('role', fn ($q) => $q->where('role_name', 'pic'))->count();
        $totalGuru = User::whereHas('role', fn ($q) => $q->where('role_name', 'guru'))->count();
        $totalGuest = User::whereHas('role', fn ($q) => $q->where('role_name', 'guest'))->count();

        $roleStats = [
            'total' => $totalUsers,
            'admin' => $totalAdmin,
            'pic' => $totalPic,
            'guru' => $totalGuru,
            'guest' => $totalGuest,
        ];

        return view('users.index', compact('users', 'roles', 'roleStats'));
    }

    /**
     * Menampilkan form pembuatan pengguna baru
     */
    public function create(): View
    {
        $roles = Role::orderBy('id')->get();

        return view('users.create', compact('roles'));
    }

    /**
     * Menyimpan data pengguna baru ke database
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return redirect()->route('users.index')
            ->with('success', "Pengguna baru '{$user->fullname}' (@{$user->username}) berhasil ditambahkan ke dalam sistem.");
    }

    /**
     * Menampilkan form edit pengguna
     */
    public function edit(User $user): View
    {
        $roles = Role::orderBy('id')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Memperbarui data pengguna
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', "Data pengguna '{$user->fullname}' (@{$user->username}) berhasil diperbarui.");
    }

    /**
     * Menghapus pengguna dari sistem
     */
    public function destroy(User $user): RedirectResponse
    {
        // Proteksi 1: Tidak boleh menghapus akun diri sendiri yang sedang login
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.');
        }

        // Proteksi 2: Tidak boleh menghapus user yang masih memiliki data terkait
        if ($user->rooms()->exists() || $user->events()->exists() || $user->complaints()->exists()) {
            return redirect()->route('users.index')
                ->with('error', "Pengguna '{$user->fullname}' tidak dapat dihapus karena memiliki keterkaitan riwayat ruangan, peminjaman agenda, atau pengaduan kerusakan.");
        }

        $fullname = $user->fullname;
        $username = $user->username;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "Akun pengguna '{$fullname}' (@{$username}) berhasil dihapus dari sistem.");
    }

    /**
     * Mengunduh template berkas Excel / CSV untuk import massal pengguna
     */
    public function downloadTemplate(\App\Services\UserImportService $importService)
    {
        $csvContent = $importService->generateTemplateCsv();

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_pengguna.csv"',
        ]);
    }

    /**
     * Memproses import massal pengguna dari file Excel / CSV
     */
    public function importExcel(Request $request, \App\Services\UserImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ], [
            'file.required' => 'Silakan pilih berkas Excel atau CSV untuk diimpor.',
            'file.mimes' => 'Format berkas harus berupa .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran berkas maksimal adalah 5 MB.',
        ]);

        $result = $importService->import($request->file('file'));

        $pesan = "Proses import selesai: {$result['success']} pengguna berhasil ditambahkan, {$result['failed']} baris gagal/dilewati.";

        return redirect()->route('users.index')
            ->with('import_result', $result)
            ->with($result['success'] > 0 ? 'success' : 'error', $pesan);
    }
}

