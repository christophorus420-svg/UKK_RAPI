/// Model & Definisi 13 Sesi Standar Jam Pembelajaran Sekolah
/// Sinkron dengan migrasi database tabel `booking_sessions` di backend Laravel.
class SchoolSession {
  final int id;
  final String sessionName;
  final String startTime; // Format "HH:mm" misal "07:00"
  final String endTime;   // Format "HH:mm" misal "07:45"

  const SchoolSession({
    required this.id,
    required this.sessionName,
    required this.startTime,
    required this.endTime,
  });

  /// Rentang jam tampilan ramah (misal: "07:00 - 07:45 WIB")
  String get formattedTime => '$startTime - $endTime WIB';

  /// Label singkat untuk chip (misal: "Sesi 1\n07:00-07:45")
  String get chipLabel => '$sessionName ($startTime - $endTime)';
}

/// Daftar 13 Sesi Standar Sekolah RAPI-Sekolah
class BookingSessionsData {
  BookingSessionsData._();

  static const List<SchoolSession> standardSessions = [
    SchoolSession(
      id: 1,
      sessionName: 'Sesi 1 (Jam Ke-1)',
      startTime: '07:00',
      endTime: '07:45',
    ),
    SchoolSession(
      id: 2,
      sessionName: 'Sesi 2 (Jam Ke-2)',
      startTime: '07:45',
      endTime: '08:30',
    ),
    SchoolSession(
      id: 3,
      sessionName: 'Sesi 3 (Jam Ke-3)',
      startTime: '08:30',
      endTime: '09:15',
    ),
    SchoolSession(
      id: 4,
      sessionName: 'Sesi 4 (Jam Ke-4)',
      startTime: '09:15',
      endTime: '10:00',
    ),
    SchoolSession(
      id: 5,
      sessionName: 'Sesi 5 (Jam Ke-5)',
      startTime: '10:00',
      endTime: '10:45',
    ),
    SchoolSession(
      id: 6,
      sessionName: 'Sesi 6 (Jam Ke-6)',
      startTime: '10:45',
      endTime: '11:30',
    ),
    SchoolSession(
      id: 7,
      sessionName: 'Sesi 7 (Jam Ke-7)',
      startTime: '11:30',
      endTime: '12:15',
    ),
    SchoolSession(
      id: 8,
      sessionName: 'Sesi 8 (Jam Ke-8)',
      startTime: '12:15',
      endTime: '13:00',
    ),
    SchoolSession(
      id: 9,
      sessionName: 'Sesi 9 (Jam Ke-9)',
      startTime: '13:00',
      endTime: '13:45',
    ),
    SchoolSession(
      id: 10,
      sessionName: 'Sesi 10 (Jam Ke-10)',
      startTime: '13:45',
      endTime: '14:30',
    ),
    SchoolSession(
      id: 11,
      sessionName: 'Sesi 11 (Jam Ke-11)',
      startTime: '14:30',
      endTime: '15:15',
    ),
    SchoolSession(
      id: 12,
      sessionName: 'Sesi 12 (Jam Ke-12)',
      startTime: '15:15',
      endTime: '16:00',
    ),
    SchoolSession(
      id: 13,
      sessionName: 'Sesi 13 (Jam Ke-13)',
      startTime: '16:00',
      endTime: '16:45',
    ),
  ];

  /// Menemukan sesi berdasarkan ID
  static SchoolSession? findById(int id) {
    try {
      return standardSessions.firstWhere((s) => s.id == id);
    } catch (_) {
      return null;
    }
  }
}

