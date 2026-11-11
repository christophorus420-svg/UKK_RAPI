import '../../core/config/app_config.dart';
import '../../core/utils/date_formatter.dart';

/// Model Data Pengaduan Kerusakan Sarpras Sekolah (ComplaintModel)
/// Sinkron dengan respon API `/api/pengaduan` dan model Complaint Laravel
class ComplaintModel {
  final int id;
  final String description;
  final String statusRepair; // 'Belum Diperbaiki', 'Menunggu Spare Part', 'Sudah Diperbaiki'
  final String? photoUrl;
  final bool isOverdue;
  final DateTime createdAt;
  final DateTime? repairedAt;
  final String? autoDeleteRemaining;
  final bool canDelete;
  final ComplaintRoomModel room;
  final ComplaintReporterModel reporter;

  const ComplaintModel({
    required this.id,
    required this.description,
    required this.statusRepair,
    this.photoUrl,
    this.isOverdue = false,
    required this.createdAt,
    this.repairedAt,
    this.autoDeleteRemaining,
    this.canDelete = false,
    required this.room,
    required this.reporter,
  });

  factory ComplaintModel.fromJson(Map<String, dynamic> json) {
    DateTime parsedDate;
    try {
      parsedDate = DateTime.parse(json['created_at']?.toString() ?? '');
    } catch (_) {
      parsedDate = DateTime.now();
    }

    DateTime? parsedRepairedAt;
    if (json['repaired_at'] != null) {
      try {
        parsedRepairedAt = DateTime.parse(json['repaired_at'].toString());
      } catch (_) {}
    }

    final rawPhotoUrl = json['photo_url']?.toString();
    final rawPhotoPath = json['photo_path']?.toString();

    return ComplaintModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      description: json['description']?.toString() ?? '',
      statusRepair: json['status_repair']?.toString() ?? (json['status']?.toString() ?? 'Belum Diperbaiki'),
      photoUrl: _resolvePhotoUrl(rawPhotoUrl, rawPhotoPath),
      isOverdue: json['is_overdue'] == true || json['is_overdue'] == 1,
      createdAt: parsedDate,
      repairedAt: parsedRepairedAt,
      autoDeleteRemaining: json['auto_delete_remaining']?.toString(),
      canDelete: json['can_delete'] == true || json['can_delete'] == 1,
      room: ComplaintRoomModel.fromJson(json['room'] as Map<String, dynamic>? ?? {}),
      reporter: ComplaintReporterModel.fromJson(json['reporter'] as Map<String, dynamic>? ?? {}),
    );
  }

  static String? _resolvePhotoUrl(String? rawUrl, String? rawPath) {
    String? url = (rawUrl != null && rawUrl.trim().isNotEmpty) ? rawUrl.trim() : null;

    if (url == null && rawPath != null && rawPath.trim().isNotEmpty) {
      final cleanPath = rawPath.trim().replaceFirst(RegExp(r'^/+'), '');
      url = cleanPath.startsWith('storage/') ? cleanPath : 'storage/$cleanPath';
    }

    if (url == null || url.isEmpty) {
      return null;
    }

    final baseUri = Uri.tryParse(AppConfig.baseUrl);
    final origin = baseUri != null
        ? '${baseUri.scheme}://${baseUri.host}${baseUri.hasPort ? ':${baseUri.port}' : ''}'
        : '';

    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      final clean = url.startsWith('/') ? url : '/$url';
      return origin.isNotEmpty ? '$origin$clean' : clean;
    }

    final parsed = Uri.tryParse(url);
    if (parsed != null && (parsed.host == 'localhost' || parsed.host == '127.0.0.1') && origin.isNotEmpty) {
      final portSuffix = parsed.hasPort ? ':${parsed.port}' : '';
      return url.replaceFirst('${parsed.scheme}://${parsed.host}$portSuffix', origin);
    }

    return url;
  }

  // Getters Status Siklus Perbaikan
  bool get isNotRepaired => statusRepair == 'Belum Diperbaiki';
  bool get isWaitingPart => statusRepair == 'Menunggu Spare Part';
  bool get isFinished => statusRepair == 'Sudah Diperbaiki';

  bool get hasPhoto => photoUrl != null && photoUrl!.trim().isNotEmpty;
  String get formattedDate => DateFormatter.formatDateTime(createdAt);
}

/// Model Sub-objek Ruangan Terkait Pengaduan
class ComplaintRoomModel {
  final int id;
  final String name;
  final String location;
  final String? picName;
  final String? picWhatsapp;

  const ComplaintRoomModel({
    required this.id,
    required this.name,
    required this.location,
    this.picName,
    this.picWhatsapp,
  });

  factory ComplaintRoomModel.fromJson(Map<String, dynamic> json) {
    return ComplaintRoomModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '') ?? 0,
      name: json['name']?.toString() ?? json['room_name']?.toString() ?? 'Ruangan',
      location: json['location']?.toString() ?? '',
      picName: json['pic_name']?.toString() ?? json['pic']?['fullname']?.toString(),
      picWhatsapp: json['pic_whatsapp']?.toString() ?? json['pic']?['whatsapp']?.toString(),
    );
  }
}

/// Model Sub-objek Pelapor Pengaduan
class ComplaintReporterModel {
  final int id;
  final String fullname;

  const ComplaintReporterModel({
    required this.id,
    required this.fullname,
  });

  factory ComplaintReporterModel.fromJson(Map<String, dynamic> json) {
    return ComplaintReporterModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '') ?? 0,
      fullname: json['fullname']?.toString() ?? 'Pelapor',
    );
  }
}

