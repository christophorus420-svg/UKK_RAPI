import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import '../../../data/models/event_model.dart';

/// Modal Pop-up Notifikasi Pemindahan Ruangan oleh Waka Sarpras
/// Memuat nama agenda, ruangan baru, tanggal, dan alasan resmi pemindahan.
class MovedRoomAlertDialog extends StatelessWidget {
  final EventModel event;
  final VoidCallback onAcknowledge;

  const MovedRoomAlertDialog({
    super.key,
    required this.event,
    required this.onAcknowledge,
  });

  static void show(BuildContext context, EventModel event, VoidCallback onAcknowledge) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => MovedRoomAlertDialog(
        event: event,
        onAcknowledge: onAcknowledge,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      title: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: AppColors.warningContainer,
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.swap_horiz_rounded,
              color: AppColors.textWarning,
              size: 24,
            ),
          ),
          const SizedBox(width: 12),
          const Expanded(
            child: Text(
              'Ruangan Dipindahkan!',
              style: TextStyle(
                fontSize: 17,
                fontWeight: FontWeight.bold,
                color: AppColors.textPrimary,
              ),
            ),
          ),
        ],
      ),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Pemberitahuan: Waka Sarpras telah memindahkan ruangan untuk agenda kegiatan Anda:',
              style: TextStyle(fontSize: 13, height: 1.4, color: AppColors.textSecondary),
            ),
            const SizedBox(height: 14),

            // Kartu Rincian Agenda & Ruangan Baru
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppColors.surfaceVariant,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.border),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    event.eventName,
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      const Icon(Icons.calendar_today_outlined, size: 13, color: AppColors.textSecondary),
                      const SizedBox(width: 6),
                      Text(
                        '${event.dateFormatted} (${event.timeRange})',
                        style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                      ),
                    ],
                  ),
                  const Divider(height: 18),

                  // Ruang Baru yang Dialihkan
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Icon(Icons.meeting_room_rounded, size: 18, color: AppColors.primary),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Ruangan Baru:',
                              style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppColors.textMuted),
                            ),
                            Text(
                              '${event.room.name} (${event.room.location})',
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: AppColors.primary,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),

            // Alasan Transparan Pemindahan oleh Waka
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppColors.warningContainer.withValues(alpha: 0.6),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.warning.withValues(alpha: 0.4)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: const [
                      Icon(Icons.info_outline, size: 16, color: AppColors.textWarning),
                      SizedBox(width: 6),
                      Text(
                        'Alasan Pemindahan oleh Waka:',
                        style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppColors.textWarning),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '"${event.moveReason ?? "Penyesuaian jadwal dinas sekolah."}"',
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
      actions: [
        ElevatedButton(
          onPressed: () {
            Navigator.pop(context);
            onAcknowledge();
          },
          style: ElevatedButton.styleFrom(
            backgroundColor: AppColors.primary,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
          child: const Text('Saya Mengerti'),
        ),
      ],
    );
  }
}

