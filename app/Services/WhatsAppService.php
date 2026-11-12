<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Room;
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

        $event->setRelation('room', $newRoom);
        $targetWa = $user->whatsapp_number;
        $pesan = $event->buildWhatsappMoveMessage($oldRoomName);

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

