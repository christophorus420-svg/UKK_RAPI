import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:rapi_sekolah_mobile/core/network/api_exception.dart';

void main() {
  group('Pengujian Modul 1: ApiException Parsing Respon Laravel', () {
    test('Mengonversi 401 Unauthorized menjadi pesan ramah sesi berakhir', () {
      final dioException = DioException(
        requestOptions: RequestOptions(path: '/api/me'),
        response: Response(
          requestOptions: RequestOptions(path: '/api/me'),
          statusCode: 401,
          data: {'message': 'Unauthenticated.'},
        ),
        type: DioExceptionType.badResponse,
      );

      final apiException = ApiException.fromDioException(dioException);

      expect(apiException.statusCode, 401);
      expect(apiException.message, 'Unauthenticated.');
    });

    test('Mengonversi 403 Role Admin ditolak di mobile', () {
      final dioException = DioException(
        requestOptions: RequestOptions(path: '/api/login'),
        response: Response(
          requestOptions: RequestOptions(path: '/api/login'),
          statusCode: 403,
          data: {
            'success': false,
            'message': 'Akses aplikasi mobile hanya diperuntukkan bagi PIC, Guru, dan Siswa. Akun Admin / Waka Sarpras wajib menggunakan browser desktop/komputer.',
          },
        ),
        type: DioExceptionType.badResponse,
      );

      final apiException = ApiException.fromDioException(dioException);

      expect(apiException.statusCode, 403);
      expect(apiException.message, contains('Waka Sarpras wajib menggunakan browser desktop'));
    });

    test('Mengonversi 422 Bentrok Jadwal Peminjaman (Conflict Prevention)', () {
      final dioException = DioException(
        requestOptions: RequestOptions(path: '/api/agenda'),
        response: Response(
          requestOptions: RequestOptions(path: '/api/agenda'),
          statusCode: 422,
          data: {
            'success': false,
            'message': 'Jadwal ruangan bentrok dengan agenda lain.',
            'conflicts': [
              {
                'event_name': 'Pelatihan Cloud Computing',
                'time': '08:00 - 11:30',
                'status': 'approved',
              }
            ],
          },
        ),
        type: DioExceptionType.badResponse,
      );

      final apiException = ApiException.fromDioException(dioException);

      expect(apiException.statusCode, 422);
      expect(apiException.isConflict, true);
      expect(apiException.conflicts, isNotNull);
      expect(apiException.conflicts!.length, 1);
    });

    test('Mengonversi 422 Validasi Form per Field', () {
      final dioException = DioException(
        requestOptions: RequestOptions(path: '/api/pengaduan'),
        response: Response(
          requestOptions: RequestOptions(path: '/api/pengaduan'),
          statusCode: 422,
          data: {
            'message': 'Data tidak valid.',
            'errors': {
              'description': ['Deskripsi laporan minimal 10 karakter.'],
              'room_id': ['Ruangan wajib dipilih.'],
            },
          },
        ),
        type: DioExceptionType.badResponse,
      );

      final apiException = ApiException.fromDioException(dioException);

      expect(apiException.statusCode, 422);
      expect(apiException.getFirstErrorFor('description'), 'Deskripsi laporan minimal 10 karakter.');
      expect(apiException.getFirstErrorFor('room_id'), 'Ruangan wajib dipilih.');
    });

    test('Menangani Connection Timeout', () {
      final dioException = DioException(
        requestOptions: RequestOptions(path: '/api/ruangan'),
        type: DioExceptionType.connectionTimeout,
      );

      final apiException = ApiException.fromDioException(dioException);

      expect(apiException.statusCode, 408);
      expect(apiException.message, contains('Connection Timeout'));
    });
  });
}

