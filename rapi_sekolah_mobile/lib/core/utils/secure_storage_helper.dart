import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Helper Penyimpanan Data Sensitif (Bearer Token & Cache User)
/// Menggunakan FlutterSecureStorage dengan enkripsi Keystore di Android.
class SecureStorageHelper {
  static const FlutterSecureStorage _storage = FlutterSecureStorage(
    aOptions: AndroidOptions(
      encryptedSharedPreferences: true,
    ),
  );

  static const String _keyToken = 'rapi_bearer_token';
  static const String _keyUserData = 'rapi_user_data';

  // --- Operasi Token Bearer ---

  /// Menyimpan token autentikasi API
  static Future<void> saveToken(String token) async {
    await _storage.write(key: _keyToken, value: token);
  }

  /// Membaca token autentikasi
  static Future<String?> getToken() async {
    return await _storage.read(key: _keyToken);
  }

  /// Mengecek apakah user memiliki token tersimpan
  static Future<bool> hasToken() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }

  /// Menghapus token
  static Future<void> deleteToken() async {
    await _storage.delete(key: _keyToken);
  }

  // --- Operasi User Cache Data ---

  /// Menyimpan data profil user dalam format JSON string
  static Future<void> saveUserData(Map<String, dynamic> userMap) async {
    final jsonString = jsonEncode(userMap);
    await _storage.write(key: _keyUserData, value: jsonString);
  }

  /// Membaca data profil user tersimpan
  static Future<Map<String, dynamic>?> getUserData() async {
    final jsonString = await _storage.read(key: _keyUserData);
    if (jsonString == null || jsonString.isEmpty) return null;
    try {
      return jsonDecode(jsonString) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  /// Menghapus semua sesi yang tersimpan (Logout)
  static Future<void> clearAll() async {
    await _storage.deleteAll();
  }
}

