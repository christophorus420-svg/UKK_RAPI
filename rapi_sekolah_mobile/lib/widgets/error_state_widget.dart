import 'package:flutter/material.dart';
import '../core/network/api_exception.dart';
import '../core/theme/app_colors.dart';

/// Widget Standar Tampilan Error Koneksi & Server (Error State)
class ErrorStateWidget extends StatelessWidget {
  final Object? error;
  final String? customMessage;
  final VoidCallback? onRetry;

  const ErrorStateWidget({
    super.key,
    this.error,
    this.customMessage,
    this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    String title = 'Terjadi Kendala';
    String message = 'Tidak dapat memuat data. Silakan coba kembali.';
    IconData icon = Icons.error_outline_rounded;
    Color iconColor = AppColors.error;

    if (error is ApiException) {
      final apiErr = error as ApiException;
      message = apiErr.message;

      if (apiErr.statusCode == 0 || apiErr.statusCode == 408) {
        title = 'Masalah Jaringan';
        icon = Icons.wifi_off_rounded;
        iconColor = AppColors.warning;
      } else if (apiErr.statusCode == 403) {
        title = 'Akses Dibatasi';
        icon = Icons.lock_outline_rounded;
        iconColor = AppColors.warning;
      } else if (apiErr.statusCode == 404) {
        title = 'Tidak Ditemukan';
        icon = Icons.search_off_rounded;
      } else if (apiErr.statusCode == 500) {
        title = 'Kesalahan Server';
        icon = Icons.dns_outlined;
      }
    } else if (customMessage != null) {
      message = customMessage!;
    }

    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32.0, vertical: 36.0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: iconColor.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(
                icon,
                size: 40,
                color: iconColor,
              ),
            ),
            const SizedBox(height: 20),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 13,
                height: 1.4,
                color: AppColors.textSecondary,
              ),
            ),
            if (onRetry != null) ...[
              const SizedBox(height: 24),
              ElevatedButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh_rounded, size: 18),
                label: const Text('Coba Lagi'),
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

