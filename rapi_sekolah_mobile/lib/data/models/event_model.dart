import '../../core/utils/date_formatter.dart';

/// Model Data Agenda / Kegiatan Sekolah (EventModel)
/// Sinkron dengan respon API `/api/agenda` dan `/api/agenda/{id}`
class EventModel {
  final int id;
  final String eventName;
  final DateTime date;
  final DateTime? endDate;
  final String dateFormatted;
  final String startTime; // "08:00"
  final String endTime;   // "11:30"
  final String status;    // 'approved', 'pending', 'rejected'
  final EventRoomModel room;
  final EventOrganizerModel organizer;
  final String? approver;
  final String? moveReason; // Catatan pemindahan ruangan oleh Waka Sarpras
  final String? rejectionReason; // Alasan penolakan jika status = rejected

  const EventModel({
    required this.id,
    required this.eventName,
    required this.date,
    this.endDate,
    required this.dateFormatted,
    required this.startTime,
    required this.endTime,
    required this.status,
    required this.room,
    required this.organizer,
    this.approver,
    this.moveReason,
    this.rejectionReason,
  });

  factory EventModel.fromJson(Map<String, dynamic> json) {
    DateTime parsedDate;
    try {
      parsedDate = DateTime.parse(json['date']?.toString() ?? '');
    } catch (_) {
      parsedDate = DateTime.now();
    }

    DateTime? parsedEndDate;
    if (json['end_date'] != null && json['end_date'].toString().isNotEmpty) {
      try {
        parsedEndDate = DateTime.parse(json['end_date'].toString());
      } catch (_) {}
    }

    return EventModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      eventName: json['event_name']?.toString() ?? '',
      date: parsedDate,
      endDate: parsedEndDate,
      dateFormatted: json['date_formatted']?.toString() ?? DateFormatter.formatFullDate(parsedDate),
      startTime: _cleanTime(json['start_time']?.toString() ?? '00:00'),
      endTime: _cleanTime(json['end_time']?.toString() ?? '00:00'),
      status: json['status']?.toString().toLowerCase() ?? 'pending',
      room: EventRoomModel.fromJson(json['room'] as Map<String, dynamic>? ?? {}),
      organizer: EventOrganizerModel.fromJson(json['organizer'] as Map<String, dynamic>? ?? {}),
      approver: json['approver']?.toString(),
      moveReason: json['move_reason']?.toString(),
      rejectionReason: json['alasan_penolakan']?.toString() ?? json['rejection_reason']?.toString(),
    );
  }

  static String _cleanTime(String time) {
    if (time.length >= 5) {
      return time.substring(0, 5);
    }
    return time;
  }

  // Getters Helper
  bool get isApproved => status == 'approved';
  bool get isPending => status == 'pending';
  bool get isRejected => status == 'rejected';

  /// Mendeteksi apakah agenda ini dipindahkan ruangannya oleh Waka
  bool get isMoved => moveReason != null && moveReason!.trim().isNotEmpty;

  /// Memeriksa apakah agenda ini berlangsung pada hari tertentu (mendukung multi-hari)
  bool occursOn(DateTime day) {
    final target = DateTime(day.year, day.month, day.day);
    final start = DateTime(date.year, date.month, date.day);
    final end = endDate != null
        ? DateTime(endDate!.year, endDate!.month, endDate!.day)
        : start;
    return (target.isAfter(start) || target.isAtSameMomentAs(start)) &&
        (target.isBefore(end) || target.isAtSameMomentAs(end));
  }

  /// Rentang jam kegiatan ("08:00 - 11:30 WIB")
  String get timeRange => '$startTime - $endTime WIB';
}

/// Model Sub-objek Ruangan pada Agenda
class EventRoomModel {
  final int id;
  final String name;
  final String location;
  final int capacity;

  const EventRoomModel({
    required this.id,
    required this.name,
    required this.location,
    required this.capacity,
  });

  factory EventRoomModel.fromJson(Map<String, dynamic> json) {
    return EventRoomModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '') ?? 0,
      name: json['name']?.toString() ?? json['room_name']?.toString() ?? 'Ruangan',
      location: json['location']?.toString() ?? '',
      capacity: json['capacity'] is int ? json['capacity'] : int.tryParse(json['capacity']?.toString() ?? '') ?? 0,
    );
  }
}

/// Model Sub-objek Pemohon / Penanggung Jawab Agenda
class EventOrganizerModel {
  final int id;
  final String fullname;
  final String role;

  const EventOrganizerModel({
    required this.id,
    required this.fullname,
    required this.role,
  });

  factory EventOrganizerModel.fromJson(Map<String, dynamic> json) {
    return EventOrganizerModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '') ?? 0,
      fullname: json['fullname']?.toString() ?? 'Pengguna',
      role: json['role']?.toString() ?? 'guru',
    );
  }
}

