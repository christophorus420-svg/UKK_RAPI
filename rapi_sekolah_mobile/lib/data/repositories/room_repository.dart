import '../../core/network/api_client.dart';
import '../../core/network/api_endpoints.dart';
import '../models/room_model.dart';

/// Repository untuk Katalog Ruangan Sekolah
class RoomRepository {
  final ApiClient _apiClient;

  RoomRepository({ApiClient? apiClient}) : _apiClient = apiClient ?? ApiClient();

  /// Mengambil Semua Daftar Ruangan Aktif
  Future<List<RoomModel>> getRooms() async {
    final response = await _apiClient.get(ApiEndpoints.rooms);
    final data = response.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>? ?? [];

    return list.map((json) => RoomModel.fromJson(json as Map<String, dynamic>)).toList();
  }

  /// Mengambil Detail Ruangan Tertentu Beserta Jadwal Mendatang
  Future<RoomModel> getRoomDetail(int id) async {
    final response = await _apiClient.get(ApiEndpoints.roomDetail(id));
    final data = response.data as Map<String, dynamic>;
    final roomJson = data['data'] as Map<String, dynamic>;

    return RoomModel.fromJson(roomJson);
  }
}

