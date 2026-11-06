import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import '../../../data/models/event_model.dart';

/// Modal Lembar Bawah (Bottom Sheet) Rincian Agenda Lengkap
class EventDetailBottomSheet extends StatelessWidget {
  final EventModel event;

  const EventDetailBottomSheet({super.key, required this.event});

  static void show(BuildContext context, EventModel event) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => EventDetailBottomSheet(event: event),
    );
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Container(
        decoration: const BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: ConstrainedBox(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.of(context).size.height * 0.85,
          ),
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(24, 12, 24, 32),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Handle Bar Penarik
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    margin: const EdgeInsets.only(bottom: 20),
                    decoration: BoxDecoration(
                      color: AppColors.border,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),

                // Judul Agenda & Status
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Text(
                        event.eventName,
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: AppColors.textPrimary,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppColors.getStatusContainerColor(event.status),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        event.isApproved ? 'Disetujui' : (event.isPending ? 'Menunggu' : 'Ditolak'),
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: AppColors.getStatusColor(event.status),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                // Peringatan Pemindahan Ruangan oleh Waka (Jika ada)
                if (event.isMoved) ...[
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: AppColors.warningContainer.withValues(alpha: 0.6),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: AppColors.warning.withValues(alpha: 0.4)),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(Icons.info_rounded, color: AppColors.textWarning, size: 20),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Catatan Pemindahan oleh Waka Sarpras:',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.textWarning,
                                ),
                              ),
                              const SizedBox(height: 3),
                              Text(
                                '"${event.moveReason}"',
                                style: const TextStyle(
                                  fontSize: 12,
                                  fontStyle: FontStyle.italic,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                ],

                // Informasi Waktu & Tanggal
                _buildDetailItem(
                  icon: Icons.calendar_today_rounded,
                  title: 'Waktu Pelaksanaan',
                  value: '${event.dateFormatted}\nPukul ${event.timeRange}',
                ),
                const SizedBox(height: 12),

                // Informasi Ruangan
                _buildDetailItem(
                  icon: Icons.meeting_room_outlined,
                  title: 'Ruangan & Lokasi',
                  value: '${event.room.name}\n${event.room.location} (Kapasitas: ${event.room.capacity} orang)',
                ),
                const SizedBox(height: 12),

                // Penanggung Jawab / Pemohon
                _buildDetailItem(
                  icon: Icons.person_outline_rounded,
                  title: 'Penyelenggara / Guru',
                  value: '${event.organizer.fullname} (${event.organizer.role.toUpperCase()})',
                ),
                const SizedBox(height: 24),

                // Tombol Tutup
                SizedBox(
                  width: double.infinity,
                  height: 46,
                  child: ElevatedButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Tutup Rincian'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildDetailItem({
    required IconData icon,
    required String title,
    required String value,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: AppColors.primaryContainer.withValues(alpha: 0.5),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, size: 20, color: AppColors.primary),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(fontSize: 11, color: AppColors.textMuted, fontWeight: FontWeight.w500),
              ),
              const SizedBox(height: 2),
              Text(
                value,
                style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.textPrimary, height: 1.3),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

