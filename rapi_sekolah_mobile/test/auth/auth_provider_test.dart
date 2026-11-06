import 'package:flutter_test/flutter_test.dart';
import 'package:rapi_sekolah_mobile/core/network/api_exception.dart';
import 'package:rapi_sekolah_mobile/data/models/user_model.dart';
import 'package:rapi_sekolah_mobile/data/repositories/auth_repository.dart';
import 'package:rapi_sekolah_mobile/providers/auth_provider.dart';

// Mock AuthRepository untuk Pengujian Unit
class MockAuthRepository extends AuthRepository {
  final bool shouldFailWith403Admin;
  final bool shouldFailWith401;

  MockAuthRepository({
    this.shouldFailWith403Admin = false,
    this.shouldFailWith401 = false,
  });

  @override
  Future<UserModel> login({
    required String username,
    required String password,
    String deviceName = 'flutter_mobile_app',
  }) async {
    if (shouldFailWith403Admin) {
      throw ApiException(
        message: 'Akses aplikasi mobile hanya diperuntukkan bagi PIC, Guru, dan Siswa. Akun Admin / Waka Sarpras wajib menggunakan browser desktop/komputer.',
        statusCode: 403,
      );
    }

    if (shouldFailWith401) {
      throw ApiException(
        message: 'Kredensial tidak valid. Username atau password salah.',
        statusCode: 401,
      );
    }

    return UserModel(
      id: 4,
      username: username,
      fullname: 'Andi Pratama, S.Pd',
      role: 'guru',
      roleLabel: 'Guru / Pembina',
      phoneNumber: '082133445566',
    );
  }

  @override
  Future<bool> hasSavedToken() async => false;
}

void main() {
  group('Pengujian Modul 2: Model & State Management Autentikasi', () {
    test('UserModel mem-parse JSON dengan benar & mengenali role Guru', () {
      final json = {
        'id': 4,
        'username': 'guru_andi',
        'fullname': 'Andi Pratama, S.Pd',
        'role': 'guru',
        'role_label': 'Guru Produktif RPL',
        'phone_number': '082133445566',
      };

      final user = UserModel.fromJson(json);

      expect(user.id, 4);
      expect(user.username, 'guru_andi');
      expect(user.isGuru, true);
      expect(user.isPic, false);
      expect(user.isAdmin, false);
      expect(user.canBookRoom, true);
    });

    test('UserModel mengenali role PIC Ruangan', () {
      final json = {
        'id': 2,
        'username': 'pic_budi',
        'fullname': 'Budi Santoso, S.Kom',
        'role': 'pic',
        'role_label': 'Teknisi Lab',
      };

      final user = UserModel.fromJson(json);

      expect(user.isPic, true);
      expect(user.canManageComplaints, true);
      expect(user.isAdmin, false);
    });

    test('AuthProvider menangkap error 403 saat Admin mencoba login di mobile', () async {
      final mockRepo = MockAuthRepository(shouldFailWith403Admin: true);
      final provider = AuthProvider(repository: mockRepo);

      final success = await provider.login(username: 'admin', password: 'password123');

      expect(success, false);
      expect(provider.isAdminBlocked, true);
      expect(provider.adminBlockedMessage, contains('Waka Sarpras'));
      expect(provider.isAuthenticated, false);
    });

    test('AuthProvider menangani 401 saat kredensial salah', () async {
      final mockRepo = MockAuthRepository(shouldFailWith401: true);
      final provider = AuthProvider(repository: mockRepo);

      final success = await provider.login(username: 'guru_andi', password: 'salah');

      expect(success, false);
      expect(provider.isAdminBlocked, false);
      expect(provider.errorMessage, contains('Kredensial tidak valid'));
      expect(provider.status, AuthStatus.error);
    });

    test('AuthProvider berhasil login sebagai Guru', () async {
      final mockRepo = MockAuthRepository();
      final provider = AuthProvider(repository: mockRepo);

      final success = await provider.login(username: 'guru_andi', password: 'password123');

      expect(success, true);
      expect(provider.isAuthenticated, true);
      expect(provider.user?.isGuru, true);
      expect(provider.user?.fullname, 'Andi Pratama, S.Pd');
    });
  });
}

