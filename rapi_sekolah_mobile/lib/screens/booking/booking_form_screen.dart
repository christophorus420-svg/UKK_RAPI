import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/config/booking_sessions.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/date_formatter.dart';
import '../../data/models/room_model.dart';
import '../../providers/booking_provider.dart';
import '../../widgets/common_button.dart';
import '../../widgets/custom_text_field.dart';
import '../../widgets/loading_indicator.dart';
import '../../widgets/session_chip.dart';
import 'my_bookings_screen.dart';

/// Formulir Pengajuan Peminjaman Ruangan & Agenda Sekolah (Sistem Sesi 1-13)
class BookingFormScreen extends StatefulWidget {
  final RoomModel? initialRoom;

  const BookingFormScreen({super.key, this.initialRoom});

  @override
  State<BookingFormScreen> createState() => _BookingFormScreenState();
}

class _BookingFormScreenState extends State<BookingFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _eventNameController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = context.read<BookingProvider>();
      provider.loadRooms().then((_) {
        if (widget.initialRoom != null) {
          provider.selectRoom(widget.initialRoom!);
        }
      });
    });
  }

  @override
  void dispose() {
    _eventNameController.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final provider = context.read<BookingProvider>();
    final today = DateTime.now();

    final picked = await showDatePicker(
      context: context,
      initialDate: provider.selectedDate.isBefore(today) ? today : provider.selectedDate,
      firstDate: today,
      lastDate: today.add(const Duration(days: 180)),
      locale: const Locale('id', 'ID'),
    );

    if (picked != null) {
      provider.selectDate(picked);
    }
  }

  Future<void> _handleSubmit() async {
    if (!_formKey.currentState!.validate()) return;

    FocusScope.of(context).unfocus();
    final provider = context.read<BookingProvider>();

    final success = await provider.submitBooking(
      eventName: _eventNameController.text,
    );

    if (!mounted) return;

    if (success) {
      _showSuccessDialog();
    } else if (provider.serverConflicts != null && provider.serverConflicts!.isNotEmpty) {
      _showConflictDialog(provider.errorMessage, provider.serverConflicts!);
    } else if (provider.errorMessage != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(provider.errorMessage!),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  void _showConflictDialog(String? message, List<dynamic> conflicts) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: const [
            Icon(Icons.warning_amber_rounded, color: AppColors.error, size: 28),
            SizedBox(width: 10),
            Expanded(
              child: Text(
                'Jadwal Bentrok!',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
            ),
          ],
        ),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                message ?? 'Jadwal sesi ruangan yang Anda pilih telah dipesan oleh agenda lain.',
                style: const TextStyle(fontSize: 13, height: 1.4, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 14),
              const Text(
                'Agenda yang bertabrakan di server:',
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.textSecondary),
              ),
              const SizedBox(height: 6),
              ...conflicts.map((c) {
                final map = c as Map<String, dynamic>;
                return Container(
                  margin: const EdgeInsets.only(bottom: 6),
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppColors.errorContainer.withValues(alpha: 0.5),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: AppColors.error.withValues(alpha: 0.3)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.event_busy, color: AppColors.textError, size: 16),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              map['event_name']?.toString() ?? 'Kegiatan Sekolah',
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.textError),
                            ),
                            Text(
                              'Jam: ${map['time']} (${map['status']})',
                              style: const TextStyle(fontSize: 11, color: AppColors.textError),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                );
              }),
            ],
          ),
        ),
        actions: [
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
            child: const Text('Pilih Sesi / Tanggal Lain'),
          ),
        ],
      ),
    );
  }

  void _showSuccessDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: const [
            Icon(Icons.check_circle_outline, color: AppColors.success, size: 28),
            SizedBox(width: 10),
            Text('Pengajuan Terkirim!'),
          ],
        ),
        content: const Text(
          'Pengajuan peminjaman ruangan berhasil dikirim ke server. Status saat ini "Menunggu Persetujuan Waka/Admin".',
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              Navigator.pop(context);
            },
            child: const Text('Selesai'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (_) => const MyBookingsScreen()),
              );
            },
            child: const Text('Lihat Riwayat Saya'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<BookingProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Ajukan Peminjaman Ruang'),
      ),
      body: provider.isLoadingRooms
          ? const LoadingIndicator(message: 'Memuat data ruangan & jadwal...')
          : Form(
              key: _formKey,
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 20.0, vertical: 16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // 1. Pemilihan Ruangan
                    const Text(
                      '1. Pilih Ruangan Fasilitas',
                      style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                    ),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppColors.border, width: 1.2),
                      ),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<RoomModel>(
                          value: provider.selectedRoom,
                          isExpanded: true,
                          hint: const Text('Pilih Ruangan Sekolah'),
                          items: provider.rooms.map((room) {
                            return DropdownMenuItem<RoomModel>(
                              value: room,
                              child: Row(
                                children: [
                                  const Icon(Icons.meeting_room_outlined, size: 18, color: AppColors.primary),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      '${room.roomName} (${room.capacityLabel})',
                                      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
                                    ),
                                  ),
                                ],
                              ),
                            );
                          }).toList(),
                          onChanged: (room) {
                            if (room != null) provider.selectRoom(room);
                          },
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // 2. Pemilihan Tanggal Peminjaman
                    const Text(
                      '2. Tanggal Peminjaman',
                      style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                    ),
                    const SizedBox(height: 8),
                    InkWell(
                      onTap: _pickDate,
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                        decoration: BoxDecoration(
                          color: AppColors.surface,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.border, width: 1.2),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.calendar_today_rounded, size: 20, color: AppColors.primary),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Text(
                                DateFormatter.formatFullDate(provider.selectedDate),
                                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
                              ),
                            ),
                            const Icon(Icons.edit_calendar_rounded, size: 18, color: AppColors.textSecondary),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),

                    // 3. Sistem Pemilihan Sesi (1 - 13) Real-Time
                    Row(
                      children: [
                        const Expanded(
                          child: Text(
                            '3. Pilih Sesi Jam Pelajaran',
                            style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                          ),
                        ),
                        if (provider.isCheckingAvailability)
                          const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    const Text(
                      'Sesi yang telah terpakai oleh agenda lain otomatis terkunci (abu-abu).',
                      style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
                    ),
                    const SizedBox(height: 10),

                    // Legenda Warna Sesi
                    Row(
                      children: [
                        _buildLegend(color: AppColors.surface, borderColor: AppColors.border, label: 'Tersedia'),
                        const SizedBox(width: 12),
                        _buildLegend(color: AppColors.primary, label: 'Dipilih'),
                        const SizedBox(width: 12),
                        _buildLegend(color: AppColors.surfaceVariant, icon: Icons.lock_outline, label: 'Terpakai'),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Grid 13 Sesi Jam Pelajaran
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: BookingSessionsData.standardSessions.map((session) {
                        final isOccupied = provider.isSessionOccupied(session.id);
                        final isSelected = provider.selectedSessionIds.contains(session.id);
                        final occupant = provider.getSessionOccupant(session.id);

                        return SessionChip(
                          session: session,
                          isSelected: isSelected,
                          isOccupied: isOccupied,
                          occupiedBy: occupant,
                          onTap: () => provider.toggleSession(session.id),
                        );
                      }).toList(),
                    ),
                    const SizedBox(height: 14),

                    // Kartu Ringkasan Jam Terpilih
                    if (provider.selectedTimeSummary != null)
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: AppColors.primaryContainer.withValues(alpha: 0.6),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: AppColors.primary.withValues(alpha: 0.2)),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.access_time_filled, color: AppColors.primary, size: 20),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Rentang Jam Terhitung Otomatis:',
                                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppColors.primary),
                                  ),
                                  Text(
                                    provider.selectedTimeSummary!,
                                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.primaryDark),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    const SizedBox(height: 24),

                    // 4. Input Nama Agenda / Kegiatan
                    const Text(
                      '4. Informasi Kegiatan',
                      style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                    ),
                    const SizedBox(height: 8),
                    CustomTextField(
                      controller: _eventNameController,
                      label: 'Nama Kegiatan / Agenda',
                      hint: 'Cth: Praktikum Basis Data / Rapat OSIS',
                      prefixIcon: Icons.event_note_rounded,
                      validator: (val) {
                        if (val == null || val.trim().isEmpty) {
                          return 'Nama kegiatan wajib diisi';
                        }
                        if (val.trim().length < 3) {
                          return 'Nama kegiatan minimal 3 karakter';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 32),

                    // Tombol Submit Pengajuan
                    CommonButton(
                      text: 'Kirim Pengajuan Peminjaman',
                      icon: Icons.send_rounded,
                      isLoading: provider.isSubmitting,
                      onPressed: _handleSubmit,
                    ),
                    const SizedBox(height: 20),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildLegend({
    required Color color,
    Color? borderColor,
    IconData? icon,
    required String label,
  }) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 14,
          height: 14,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(4),
            border: Border.all(color: borderColor ?? Colors.transparent, width: 1),
          ),
          child: icon != null ? Icon(icon, size: 10, color: AppColors.textMuted) : null,
        ),
        const SizedBox(width: 5),
        Text(
          label,
          style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
        ),
      ],
    );
  }
}

