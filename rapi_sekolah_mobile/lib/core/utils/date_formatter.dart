import 'package:intl/intl.dart';

/// Helper Pemformat Tanggal & Waktu Lokal Indonesia (id_ID)
class DateFormatter {
  DateFormatter._();

  /// Format lengkap: "Senin, 02 November 2026"
  static String formatFullDate(DateTime date) {
    return DateFormat('EEEE, dd MMMM yyyy', 'id_ID').format(date);
  }

  /// Format ringkas: "02 Nov 2026"
  static String formatShortDate(DateTime date) {
    return DateFormat('dd MMM yyyy', 'id_ID').format(date);
  }

  /// Format standar tanggal API Laravel: "YYYY-MM-DD"
  static String formatApiDate(DateTime date) {
    return DateFormat('yyyy-MM-dd').format(date);
  }

  /// Format jam: "08:00 WIB"
  static String formatTime(String time) {
    // Jika time dari database berupa "08:00:00", potong menjadi "08:00"
    final cleanTime = time.length >= 5 ? time.substring(0, 5) : time;
    return '$cleanTime WIB';
  }

  /// Format lengkap tanggal dan jam: "02 Nov 2026, 14:30 WIB"
  static String formatDateTime(DateTime dateTime) {
    final date = DateFormat('dd MMM yyyy', 'id_ID').format(dateTime);
    final time = DateFormat('HH:mm').format(dateTime);
    return '$date, $time WIB';
  }

  /// Parsing string tanggal dari Laravel menjadi objek DateTime
  static DateTime? parseDateTime(String? dateStr) {
    if (dateStr == null || dateStr.isEmpty) return null;
    try {
      return DateTime.parse(dateStr);
    } catch (_) {
      return null;
    }
  }
}

