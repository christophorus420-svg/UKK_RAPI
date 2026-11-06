import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../utils/secure_storage_helper.dart';

/// Interceptor Dio untuk Autentikasi Otomatis & Penanganan 401
/// 1. Menyisipkan header 'Authorization: Bearer <token>' pada setiap request terproteksi
/// 2. Menghapus sesi token dan memicu auto-logout jika server merespons 401 Unauthorized
class AuthInterceptor extends Interceptor {
  /// Callback opsional yang dipanggil saat sesi token kadaluarsa (401)
  final VoidCallback? onUnauthorized;

  AuthInterceptor({this.onUnauthorized});

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    // Selalu pastikan header Accept adalah application/json agar Laravel tidak me-redirect HTML
    options.headers['Accept'] = 'application/json';

    // Ambil Bearer token dari Secure Storage jika tersedia
    final token = await SecureStorageHelper.getToken();
    if (token != null && token.isNotEmpty) {
      options.headers['Authorization'] = 'Bearer $token';
    }

    // Jika request membawa FormData (multipart file upload), hapus Content-Type bawaan application/json
    // agar Dio secara otomatis menghasilkan header: multipart/form-data; boundary=--dio-boundary-...
    if (options.data is FormData) {
      options.headers.remove('Content-Type');
      options.headers.remove('content-type');
      options.contentType = null;
    }

    if (kDebugMode) {
      debugPrint('➡️ [DIO REQ] ${options.method} ${options.uri}');
      if (options.data != null) {
        debugPrint('📦 [DATA] ${options.data}');
      }
    }

    return handler.next(options);
  }

  @override
  void onResponse(Response response, ResponseInterceptorHandler handler) {
    if (kDebugMode) {
      debugPrint('⬅️ [DIO RES: ${response.statusCode}] ${response.requestOptions.uri}');
    }
    return handler.next(response);
  }

  @override
  Future<void> onError(
    DioException err,
    ErrorInterceptorHandler handler,
  ) async {
    if (kDebugMode) {
      debugPrint('❌ [DIO ERR: ${err.response?.statusCode}] ${err.requestOptions.uri}');
      debugPrint('💥 [ERR BODY] ${err.response?.data}');
      debugPrint('🔍 [ERR DETAIL] Type: ${err.type} | Msg: ${err.message} | Error: ${err.error}');
    }

    // Jika menerima 401 Unauthorized, hapus token lokal secara otomatis
    if (err.response?.statusCode == 401) {
      await SecureStorageHelper.clearAll();
      onUnauthorized?.call();
    }

    return handler.next(err);
  }
}

