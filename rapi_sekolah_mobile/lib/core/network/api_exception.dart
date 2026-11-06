import 'dart:io';
import 'package:dio/dio.dart';

/// Exception Khusus REST API RAPI-Sekolah
/// Menangani parsing error HTTP dari Laravel: no internet, timeout, 401, 403, 422 (validasi/bentrok), dan 500.
class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final Map<String, List<String>>? validationErrors;
  final List<dynamic>? conflicts;
  final bool isConflict;

  ApiException({
    required this.message,
    this.statusCode,
    this.validationErrors,
    this.conflicts,
    this.isConflict = false,
  });

  /// Factory untuk mengonversi DioException menjadi ApiException yang rapi & berbahasa Indonesia
  factory ApiException.fromDioException(DioException error) {
    switch (error.type) {
      case DioExceptionType.connectionTimeout:
        return ApiException(
          message: 'Koneksi ke server sekolah batas waktu (Connection Timeout). Periksa jaringan Wi-Fi Anda.',
          statusCode: 408,
        );

      case DioExceptionType.sendTimeout:
        return ApiException(
          message: 'Batas waktu pengiriman data terlampaui. Silakan coba kembali.',
          statusCode: 408,
        );

      case DioExceptionType.receiveTimeout:
        return ApiException(
          message: 'Server sekolah membutuhkan waktu terlalu lama untuk merespons (Receive Timeout).',
          statusCode: 408,
        );

      case DioExceptionType.badCertificate:
        return ApiException(
          message: 'Sertifikat SSL server tidak valid.',
          statusCode: 495,
        );

      case DioExceptionType.connectionError:
        return ApiException(
          message: 'Gagal terhubung ke server Laravel. Pastikan server lokal menyala (php artisan serve) dan URL sudah tepat.',
          statusCode: 503,
        );

      case DioExceptionType.cancel:
        return ApiException(
          message: 'Permintaan dibatalkan.',
          statusCode: 499,
        );

      case DioExceptionType.badResponse:
        return _handleBadResponse(error.response);

      case DioExceptionType.unknown:
      default:
        final inner = error.error;
        if (inner is SocketException) {
          return ApiException(
            message: 'Tidak ada koneksi internet. Periksa Wi-Fi atau data seluler Anda.',
            statusCode: 0,
          );
        }
        if (inner is HttpException) {
          return ApiException(
            message: 'Koneksi ke server terputus: ${inner.message}',
            statusCode: 500,
          );
        }
        return ApiException(
          message: error.message ?? (inner != null ? inner.toString() : 'Terjadi kesalahan tidak terduga pada pengiriman data.'),
          statusCode: 500,
        );
    }
  }

  /// Penanganan respon HTTP dari Laravel (401, 403, 404, 422, 500)
  static ApiException _handleBadResponse(Response? response) {
    if (response == null) {
      return ApiException(
        message: 'Respon kosong dari server backend sekolah.',
        statusCode: 500,
      );
    }

    final int status = response.statusCode ?? 500;
    final dynamic data = response.data;

    // Ambil pesan utama dari respon backend JSON jika ada
    String message = 'Terjadi kesalahan pada server ($status).';
    Map<String, List<String>>? validationErrors;
    List<dynamic>? conflicts;
    bool isConflict = false;

    if (data is Map<String, dynamic>) {
      if (data.containsKey('message') && data['message'] != null) {
        message = data['message'].toString();
      }

      // 1. Ekstraksi Validasi Form Laravel (Field Errors)
      if (data.containsKey('errors') && data['errors'] is Map) {
        validationErrors = {};
        (data['errors'] as Map).forEach((key, value) {
          if (value is List) {
            validationErrors![key.toString()] =
                value.map((e) => e.toString()).toList();
          } else if (value is String) {
            validationErrors![key.toString()] = [value];
          }
        });
      }

      // 2. Ekstraksi Bentrok Jadwal (Conflict Prevention) pada pengajuan agenda
      if (data.containsKey('conflicts') && data['conflicts'] is List) {
        conflicts = data['conflicts'] as List<dynamic>;
        isConflict = true;
      }
    }

    switch (status) {
      case 400:
        return ApiException(
          message: message.isNotEmpty ? message : 'Permintaan tidak valid (Bad Request).',
          statusCode: 400,
        );

      case 401:
        return ApiException(
          message: message.isNotEmpty ? message : 'Sesi Anda telah berakhir. Silakan login kembali.',
          statusCode: 401,
        );

      case 403:
        // Khusus Role Admin / Waka yang ditolak login via mobile
        return ApiException(
          message: message.isNotEmpty
              ? message
              : 'Akses ditolak. Anda tidak memiliki izin untuk fitur ini di aplikasi mobile.',
          statusCode: 403,
        );

      case 404:
        return ApiException(
          message: message.isNotEmpty ? message : 'Data yang diminta tidak ditemukan di server.',
          statusCode: 404,
        );

      case 422:
        return ApiException(
          message: message.isNotEmpty ? message : 'Data yang dikirim tidak lolos validasi server.',
          statusCode: 422,
          validationErrors: validationErrors,
          conflicts: conflicts,
          isConflict: isConflict,
        );

      case 500:
        return ApiException(
          message: 'Terjadi kegagalan internal pada server Laravel sekolah (500).',
          statusCode: 500,
        );

      default:
        return ApiException(
          message: message,
          statusCode: status,
        );
    }
  }

  /// Helper untuk mengambil pesan error pertama dari field tertentu
  String? getFirstErrorFor(String fieldName) {
    if (validationErrors == null || !validationErrors!.containsKey(fieldName)) {
      return null;
    }
    final fieldErrors = validationErrors![fieldName];
    return (fieldErrors != null && fieldErrors.isNotEmpty) ? fieldErrors.first : null;
  }

  @override
  String toString() => message;
}

