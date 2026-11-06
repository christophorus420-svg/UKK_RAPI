import 'package:dio/dio.dart';
import '../config/app_config.dart';
import 'api_exception.dart';
import 'auth_interceptor.dart';

/// HTTP Client Utama RAPI-Sekolah berbasis Dio
/// Menyediakan enkapsulasi lengkap untuk GET, POST, PATCH, DELETE, dan Upload Multipart.
class ApiClient {
  late final Dio _dio;

  ApiClient({AuthInterceptor? authInterceptor}) {
    _dio = Dio(
      BaseOptions(
        baseUrl: AppConfig.baseUrl,
        connectTimeout: AppConfig.connectTimeout,
        receiveTimeout: AppConfig.receiveTimeout,
        sendTimeout: AppConfig.sendTimeout,
        headers: AppConfig.defaultHeaders,
        responseType: ResponseType.json,
      ),
    );

    // Pasang Interceptor untuk Token & Auto Logout
    _dio.interceptors.add(authInterceptor ?? AuthInterceptor());
  }

  /// Memperbarui Base URL secara dinamis jika diperlukan (misal saat switch IP LAN di HP)
  void updateBaseUrl(String newBaseUrl) {
    _dio.options.baseUrl = newBaseUrl;
  }

  /// Request HTTP GET
  Future<Response<T>> get<T>(
    String path, {
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      final response = await _dio.get<T>(
        path,
        queryParameters: queryParameters,
        options: options,
      );
      return response;
    } on DioException catch (e) {
      throw ApiException.fromDioException(e);
    } catch (e) {
      throw ApiException(message: 'Terjadi kesalahan sistem: $e');
    }
  }

  /// Request HTTP POST (JSON Body)
  Future<Response<T>> post<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      final response = await _dio.post<T>(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );
      return response;
    } on DioException catch (e) {
      throw ApiException.fromDioException(e);
    } catch (e) {
      throw ApiException(message: 'Terjadi kesalahan sistem: $e');
    }
  }

  /// Request HTTP PATCH (Misal Update Status PIC)
  Future<Response<T>> patch<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      final response = await _dio.patch<T>(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );
      return response;
    } on DioException catch (e) {
      throw ApiException.fromDioException(e);
    } catch (e) {
      throw ApiException(message: 'Terjadi kesalahan sistem: $e');
    }
  }

  /// Request HTTP DELETE
  Future<Response<T>> delete<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      final response = await _dio.delete<T>(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );
      return response;
    } on DioException catch (e) {
      throw ApiException.fromDioException(e);
    } catch (e) {
      throw ApiException(message: 'Terjadi kesalahan sistem: $e');
    }
  }

  /// Upload File Multipart (Khusus Pengaduan Sarpras)
  Future<Response<T>> uploadMultipart<T>(
    String path, {
    required FormData formData,
    ProgressCallback? onSendProgress,
  }) async {
    try {
      final response = await _dio.post<T>(
        path,
        data: formData,
        onSendProgress: onSendProgress,
        options: Options(
          contentType: null,
          headers: {
            'Accept': 'application/json',
          },
        ),
      );
      return response;
    } on DioException catch (e) {
      throw ApiException.fromDioException(e);
    } catch (e) {
      throw ApiException(message: 'Terjadi kesalahan sistem: $e');
    }
  }
}

