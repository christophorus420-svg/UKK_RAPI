import '../../core/network/api_client.dart';
import '../../core/network/api_endpoints.dart';
import '../models/event_model.dart';

/// Repository untuk Agenda Sekolah & Kalender
class EventRepository {
  final ApiClient _apiClient;

  EventRepository({ApiClient? apiClient}) : _apiClient = apiClient ?? ApiClient();

  /// Mengambil Daftar Agenda Kalender
  /// Parameter opsional:
  /// - [roomId]: filter berdasarkan ID ruangan
  /// - [date]: filter tanggal tertentu ("YYYY-MM-DD")
  /// - [month]: filter bulan (1 - 12)
  /// - [year]: filter tahun (cth: 2026)
  /// - [status]: 'approved' (default), 'pending', atau 'rejected'
  Future<List<EventModel>> getEvents({
    int? roomId,
    String? date,
    int? month,
    int? year,
    String? status,
  }) async {
    final queryParams = <String, dynamic>{};

    if (roomId != null) queryParams['room_id'] = roomId;
    if (date != null && date.isNotEmpty) queryParams['date'] = date;
    if (month != null) queryParams['month'] = month;
    if (year != null) queryParams['year'] = year;
    if (status != null && status.isNotEmpty) queryParams['status'] = status;

    final response = await _apiClient.get(
      ApiEndpoints.agenda,
      queryParameters: queryParams,
    );

    final data = response.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>? ?? [];

    return list.map((json) => EventModel.fromJson(json as Map<String, dynamic>)).toList();
  }

  /// Mengambil Detail Agenda Lengkap Berdasarkan ID
  Future<EventModel> getEventDetail(int id) async {
    final response = await _apiClient.get(ApiEndpoints.agendaDetail(id));
    final data = response.data as Map<String, dynamic>;
    final eventJson = data['data'] as Map<String, dynamic>;

    return EventModel.fromJson(eventJson);
  }

  /// Mengajukan Agenda / Peminjaman Baru (Akan dipakai di Modul 4)
  Future<EventModel> createEvent({
    required int roomId,
    required String eventName,
    required String date,
    required String startTime,
    required String endTime,
  }) async {
    final response = await _apiClient.post(
      ApiEndpoints.agenda,
      data: {
        'room_id': roomId,
        'event_name': eventName.trim(),
        'date': date,
        'start_time': startTime,
        'end_time': endTime,
      },
    );

    final data = response.data as Map<String, dynamic>;
    final eventJson = data['data'] as Map<String, dynamic>;

    return EventModel.fromJson(eventJson);
  }
}

