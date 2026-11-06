import 'package:flutter/material.dart';

/// Palet Warna Resmi Aplikasi Mobile RAPI-Sekolah
/// Disesuaikan dengan identitas akademik kejuruan (SMK) modern & Material 3.
class AppColors {
  AppColors._();

  // Warna Utama (Primary: Deep Indigo / Navy Sekolah)
  static const Color primary = Color(0xFF1E3A8A);        // Blue 900
  static const Color primaryLight = Color(0xFF3B82F6);   // Blue 500
  static const Color primaryDark = Color(0xFF172554);    // Blue 950
  static const Color primaryContainer = Color(0xFFDBEAFE); // Blue 100

  // Warna Sekunder / Aksen (Teal & Cyan)
  static const Color secondary = Color(0xFF0D9488);      // Teal 600
  static const Color secondaryLight = Color(0xFF14B8A6); // Teal 500
  static const Color secondaryContainer = Color(0xFFCCFBF1);

  // Status Warna (Sesuai Logika Status UKK)
  // 1. Approved / Sudah Diperbaiki -> Emerald
  static const Color success = Color(0xFF10B981);
  static const Color successContainer = Color(0xFFD1FAE5);
  static const Color textSuccess = Color(0xFF065F46);

  // 2. Pending / Menunggu Spare Part -> Amber
  static const Color warning = Color(0xFFF59E0B);
  static const Color warningContainer = Color(0xFFFEF3C7);
  static const Color textWarning = Color(0xFF92400E);

  // 3. Rejected / Belum Diperbaiki / Bentrok -> Rose Red
  static const Color error = Color(0xFFEF4444);
  static const Color errorContainer = Color(0xFFFEE2E2);
  static const Color textError = Color(0xFF991B1B);

  // Netral & Background (Light Mode)
  static const Color background = Color(0xFFF8FAFC);     // Slate 50
  static const Color surface = Color(0xFFFFFFFF);        // Putih Bersih
  static const Color surfaceVariant = Color(0xFFF1F5F9); // Slate 100
  static const Color border = Color(0xFFE2E8F0);         // Slate 200
  static const Color divider = Color(0xFFCBD5E1);        // Slate 300

  // Netral & Background (Dark Mode)
  static const Color darkBackground = Color(0xFF0F172A); // Slate 900
  static const Color darkSurface = Color(0xFF1E293B);    // Slate 800
  static const Color darkBorder = Color(0xFF334155);     // Slate 700

  // Tipografi Teks
  static const Color textPrimary = Color(0xFF0F172A);    // Slate 900
  static const Color textSecondary = Color(0xFF64748B);  // Slate 500
  static const Color textMuted = Color(0xFF94A3B8);      // Slate 400
  static const Color textWhite = Color(0xFFFFFFFF);

  // Status Badge Helper Color Mapping
  static Color getStatusColor(String? status) {
    switch (status?.toLowerCase()) {
      case 'approved':
      case 'sudah diperbaiki':
        return success;
      case 'pending':
      case 'menunggu spare part':
        return warning;
      case 'rejected':
      case 'belum diperbaiki':
        return error;
      default:
        return textSecondary;
    }
  }

  static Color getStatusContainerColor(String? status) {
    switch (status?.toLowerCase()) {
      case 'approved':
      case 'sudah diperbaiki':
        return successContainer;
      case 'pending':
      case 'menunggu spare part':
        return warningContainer;
      case 'rejected':
      case 'belum diperbaiki':
        return errorContainer;
      default:
        return surfaceVariant;
    }
  }
}

