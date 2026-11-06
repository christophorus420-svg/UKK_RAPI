import 'package:flutter/material.dart';
import '../core/config/booking_sessions.dart';
import '../core/theme/app_colors.dart';

/// Widget Chip Pilihan Sesi Waktu Sekolah
/// Memiliki 3 state visual:
/// 1. Tersedia (Bisa dipilih)
/// 2. Terpilih (Warna primer biru aktif)
/// 3. Terpakai / Bentrok (Disabled / abu-abu dengan ikon gembok)
class SessionChip extends StatelessWidget {
  final SchoolSession session;
  final bool isSelected;
  final bool isOccupied;
  final String? occupiedBy;
  final VoidCallback? onTap;

  const SessionChip({
    super.key,
    required this.session,
    required this.isSelected,
    required this.isOccupied,
    this.occupiedBy,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    // 1. Sesi Sudah Terpakai oleh Agenda Lain
    if (isOccupied) {
      return Tooltip(
        message: 'Sudah terpakai: ${occupiedBy ?? "Agenda lain"}',
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
          decoration: BoxDecoration(
            color: AppColors.surfaceVariant.withValues(alpha: 0.8),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: AppColors.border),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.lock_outline, size: 13, color: AppColors.textMuted),
                  const SizedBox(width: 4),
                  Text(
                    'Sesi ${session.id}',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textMuted,
                      decoration: TextDecoration.lineThrough,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 2),
              Text(
                '${session.startTime} - ${session.endTime}',
                style: const TextStyle(fontSize: 10, color: AppColors.textMuted),
              ),
            ],
          ),
        ),
      );
    }

    // 2. Sesi Sedang Dipilih User
    if (isSelected) {
      return InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
          decoration: BoxDecoration(
            color: AppColors.primary,
            borderRadius: BorderRadius.circular(10),
            boxShadow: [
              BoxShadow(
                color: AppColors.primary.withValues(alpha: 0.25),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.check_circle, size: 13, color: AppColors.textWhite),
                  const SizedBox(width: 4),
                  Text(
                    'Sesi ${session.id}',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textWhite,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 2),
              Text(
                '${session.startTime} - ${session.endTime}',
                style: const TextStyle(fontSize: 10, color: AppColors.primaryContainer),
              ),
            ],
          ),
        ),
      );
    }

    // 3. Sesi Tersedia (Siap Dipilih)
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: AppColors.border, width: 1.2),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Sesi ${session.id}',
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              '${session.startTime} - ${session.endTime}',
              style: const TextStyle(fontSize: 10, color: AppColors.textSecondary),
            ),
          ],
        ),
      ),
    );
  }
}

