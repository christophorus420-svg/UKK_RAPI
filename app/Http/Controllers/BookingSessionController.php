<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBookingSessionRequest;
use App\Models\BookingSession;
use Illuminate\Http\RedirectResponse;

class BookingSessionController extends Controller
{
    /**
     * Memperbarui informasi nama dan rentang jam sesi peminjaman
     */
    public function update(UpdateBookingSessionRequest $request, BookingSession $bookingSession): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $bookingSession->update($validated);

        return redirect()->route('dashboard')
            ->with('success', "Waktu {$bookingSession->session_name} berhasil diperbarui ({$bookingSession->formatted_time}).");
    }

    /**
     * Mengaktifkan atau menonaktifkan ketersediaan sesi untuk peminjaman
     */
    public function toggleStatus(BookingSession $bookingSession): RedirectResponse
    {
        $bookingSession->is_active = !$bookingSession->is_active;
        $bookingSession->save();

        $statusText = $bookingSession->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('dashboard')
            ->with('success', "Sesi '{$bookingSession->session_name}' berhasil {$statusText}.");
    }
}
