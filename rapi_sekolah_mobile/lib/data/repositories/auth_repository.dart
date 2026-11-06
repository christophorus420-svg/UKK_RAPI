import '../../core/network/api_client.dart';
import '../../core/network/api_endpoints.dart';
import '../../core/utils/secure_storage_helper.dart';
import '../models/user_model.dart';

/// Repository untuk Menangani Operasi Autentikasi Pengguna
class AuthRepository {
  final ApiClient _apiClient;

  AuthRepository({ApiClient? apiClient}) : _apiClient = apiClient ?? ApiClient();

  /// Login Pengguna ke Backend Laravel
  /// Mengembalikan [UserModel] dan menyimpan token Bearer ke FlutterSecureStorage
  Future<UserModel> login({
    required String username,
    required String password,
    String deviceName = 'flutter_mobile_app',
  }) async {
    final response = await _apiClient.post(
      ApiEndpoints.login,
      data: {
        'username': username.trim(),
        'password': password,
        'device_name': deviceName,
      },
    );

    final data = response.data as Map<String, dynamic>;
    final String token = data['token']?.toString() ?? '';
    final userJson = data['user'] as Map<String, dynamic>;

    final user = UserModel.fromJson(userJson);

    // Simpan token dan data user secara aman
    await SecureStorageHelper.saveToken(token);
    await SecureStorageHelper.saveUserData(user.toJson());

    return user;
  }

  /// Mengambil Data Profil Terkini via Token Bearer
  Future<UserModel> getProfile() async {
    final response = await _apiClient.get(ApiEndpoints.me);
    final data = response.data as Map<String, dynamic>;
    final userJson = data['user'] as Map<String, dynamic>;

    final user = UserModel.fromJson(userJson);
    await SecureStorageHelper.saveUserData(user.toJson());

    return user;
  }

  /// Logout dan Mencabut Token di Server serta Menghapus Sesi Lokal
  Future<void> logout() async {
    try {
      await _apiClient.post(ApiEndpoints.logout);
    } catch (_) {
      // Abaikan jika server error saat logout, yang terpenting sesi lokal terhapus
    } finally {
      await SecureStorageHelper.clearAll();
    }
  }

  /// Mengajukan Token Pemulihan Password (Lupa Password)
  /// Mengembalikan token reset dan batas waktu kedaluwarsa
  Future<Map<String, dynamic>> forgotPassword({
    required String username,
    required String phoneNumber,
  }) async {
    final response = await _apiClient.post(
      ApiEndpoints.forgotPassword,
      data: {
        'username': username.trim(),
        'phone_number': phoneNumber.trim(),
      },
    );

    return response.data as Map<String, dynamic>;
  }

  /// Mereset Password Menggunakan Token Pemulihan
  Future<String> resetPassword({
    required String username,
    required String token,
    required String password,
    required String passwordConfirmation,
  }) async {
    final response = await _apiClient.post(
      ApiEndpoints.resetPassword,
      data: {
        'username': username.trim(),
        'token': token.trim(),
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
    );

    final data = response.data as Map<String, dynamic>;
    return data['message']?.toString() ?? 'Password berhasil diperbarui.';
  }

  /// Mengambil User dari Cache Lokal (jika offline atau start awal cepat)
  Future<UserModel?> getCachedUser() async {
    final userMap = await SecureStorageHelper.getUserData();
    if (userMap != null) {
      return UserModel.fromJson(userMap);
    }
    return null;
  }

  /// Mengecek Keberadaan Token di Storage
  Future<bool> hasSavedToken() async {
    return await SecureStorageHelper.hasToken();
  }
}

