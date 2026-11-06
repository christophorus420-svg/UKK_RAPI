import 'dart:io';
import 'package:dio/dio.dart';
import '../../core/network/api_client.dart';
import '../../core/network/api_endpoints.dart';
import '../models/complaint_model.dart';

/// Repository untuk Modul Pengaduan Kerusakan Sarpras Sekolah
class ComplaintRepository {
  final ApiClient _apiClient;

  ComplaintRepository({ApiClient? apiClient}) : _apiClient = apiClient ?? ApiClient();

  /// Mengambil Daftar Pengaduan
  /// - Jika role Guru/Siswa: otomatis mengembalikan laporan milik sendiri
  /// - Jika role PIC: otomatis mengembalikan laporan di ruangan yang didelegasikan padanya
  Future<List<ComplaintModel>> getComplaints({
    int? roomId,
    String? status,
  }) async {
    final queryParams = <String, dynamic>{};
    if (roomId != null) queryParams['room_id'] = roomId;
    if (status != null && status.isNotEmpty) queryParams['status'] = status;

    final response = await _apiClient.get(
      ApiEndpoints.complaints,
      queryParameters: queryParams,
    );

    final data = response.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>? ?? [];

    return list.map((json) => ComplaintModel.fromJson(json as Map<String, dynamic>)).toList();
  }

  /// Mengirim Laporan Pengaduan Kerusakan (Multipart / Form-Data dengan Foto)
  Future<ComplaintModel> createComplaint({
    required int roomId,
    required String description,
    File? photoFile,
  }) async {
    final formData = FormData();

    formData.fields.add(MapEntry('room_id', roomId.toString()));
    formData.fields.add(MapEntry('description', description.trim()));

    if (photoFile != null && await photoFile.exists()) {
      final fileName = photoFile.path.split('/').last.split('\\').last;
      formData.files.add(
        MapEntry(
          'photo',
          await MultipartFile.fromFile(
            photoFile.path,
            filename: fileName,
          ),
        ),
      );
    }

    final response = await _apiClient.uploadMultipart(
      ApiEndpoints.complaints,
      formData: formData,
    );

    final data = response.data as Map<String, dynamic>;
    final complaintJson = data['data'] as Map<String, dynamic>;

    return ComplaintModel.fromJson(complaintJson);
  }

  /// Memperbarui Status Siklus Perbaikan (Khusus PIC Ruangan Terkait)
  /// Status valid: "Belum Diperbaiki", "Menunggu Spare Part", "Sudah Diperbaiki"
  Future<ComplaintModel> updateStatus({
    required int complaintId,
    required String status,
  }) async {
    final response = await _apiClient.patch(
      ApiEndpoints.updateComplaintStatus(complaintId),
      data: {'status': status},
    );

    final data = response.data as Map<String, dynamic>;
    final complaintJson = data['data'] as Map<String, dynamic>;

    return ComplaintModel.fromJson(complaintJson);
  }

  /// Menghapus Laporan Pengaduan Secara Manual
  Future<bool> deleteComplaint(int complaintId) async {
    final response = await _apiClient.delete(
      '${ApiEndpoints.complaints}/$complaintId',
    );
    final data = response.data as Map<String, dynamic>;
    return data['success'] == true;
  }
}

