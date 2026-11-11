<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $url;
    protected ?string $token;

    public function __construct()
    {
        $this->url = config('services.whatsapp.url', env('WA_GATEWAY_URL', 'https://api.fonnte.com/send'));
        $this->token = config('services.whatsapp.token', env('WA_GATEWAY_TOKEN'));
    }

    /**
     * Mengirim notifikasi WhatsApp ke peminjam saat ruangan agenda dipindahkan oleh admin
     */
    public function sendRoomMovedNotification(Event $event, string $oldRoomName, Room $newRoom): bool
    {
        $user = $event->user;
        if (!$user) {
            Log::warning("Notifikasi WA dibatalkan: peminjam agenda #{$event->id} tidak ditemukan.");
            return false;
        }

        $phoneNumber = $user->phone_number;
        if (empty($phoneNumber)) {
            Log::info("Notifikasi WA dilewati: nomor WA peminjam '{$user->fullname}' (User ID: {$user->id}) belum terisi.");
            return false;
        }

        $targetWa = $user->whatsapp_number;
        $tanggalPeminjaman = Carbon::parse($event->date)->translatedFormat('l, d F Y');
        $jamMulai = substr((string) $event->start_time, 0, 5);
        $jamSelesai = substr((string) $event->end_time, 0, 5);

        $pesan = "Halo {$user->fullname},\n\n"
            . "Peminjaman Anda untuk agenda \"{$event->event_name}\" pada {$tanggalPeminjaman} (pukul {$jamMulai} - {$jamSelesai} WIB) telah dipindahkan dari {$oldRoomName} ke {$newRoom->room_name}.\n\n";

        if (!empty($event->move_reason)) {
            $pesan .= "Alasan pemindahan: {$event->move_reason}\n\n";
        }

        $pesan .= "Mohon menyesuaikan. Terima kasih.\n"
            . "- Pengelola Sarpras " . config('app.name', 'Sekolah');

        return $this->sendMessage($targetWa, $pesan);
    }

    /**
     * Mengirim pesan teks ke nomor tujuan via gateway WhatsApp API
     */
    public function sendMessage(string $target, string $message): bool
    {
        if (empty($this->token)) {
            Log::info("Pengiriman pesan WhatsApp ke {$target} dilewati: WA_GATEWAY_TOKEN belum diatur di file .env.");
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => $this->token,
                ])
                ->post($this->url, [
                    'target' => $target,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                Log::info("Notifikasi WhatsApp berhasil dikirim ke {$target}.");
                return true;
            }

            Log::error("Gagal mengirim notifikasi WhatsApp ke {$target}. Status: {$response->status()}, Response: {$response->body()}");
            return false;
        } catch (\Throwable $e) {
            Log::error("Pengecualian saat menghubungi gateway WhatsApp ({$target}): " . $e->getMessage());
            return false;
        }
    }
}

