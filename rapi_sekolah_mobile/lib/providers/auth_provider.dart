import 'package:flutter/foundation.dart';
import '../core/network/api_exception.dart';
import '../data/models/user_model.dart';
import '../data/repositories/auth_repository.dart';

enum AuthStatus {
  initial,
  unauthenticated,
  authenticating,
  authenticated,
  error,
}

/// Provider State Management Autentikasi Pengguna
class AuthProvider extends ChangeNotifier {
  final AuthRepository _repository;

  AuthStatus _status = AuthStatus.initial;
  UserModel? _user;
  String? _errorMessage;

  // Khusus Pencegatan Role Admin (403)
  bool _isAdminBlocked = false;
  String? _adminBlockedMessage;

  AuthProvider({AuthRepository? repository})
      : _repository = repository ?? AuthRepository();

  // Getters
  AuthStatus get status => _status;
  UserModel? get user => _user;
  String? get errorMessage => _errorMessage;
  bool get isAdminBlocked => _isAdminBlocked;
  String? get adminBlockedMessage => _adminBlockedMessage;

  bool get isAuthenticated =>
      _status == AuthStatus.authenticated && _user != null;
  bool get isLoading => _status == AuthStatus.authenticating;

  // Role Shortcut
  bool get isPic => _user?.isPic ?? false;
  bool get isGuru => _user?.isGuru ?? false;
  bool get isGuest => _user?.isGuest ?? false;

  /// Memeriksa Keberadaan Sesi Token saat Aplikasi Pertama Kali Dibuka
  Future<void> checkAuthStatus() async {
    final hasToken = await _repository.hasSavedToken();

    if (!hasToken) {
      _status = AuthStatus.unauthenticated;
      notifyListeners();
      return;
    }

    try {
      // Ambil data terbaru dari server
      _user = await _repository.getProfile();
      _status = AuthStatus.authenticated;
    } on ApiException catch (e) {
      if (e.statusCode == 401) {
        // Token sudah expired
        await _repository.logout();
        _status = AuthStatus.unauthenticated;
      } else {
        // Masalah offline: gunakan cache lokal jika ada
        final cached = await _repository.getCachedUser();
        if (cached != null) {
          _user = cached;
          _status = AuthStatus.authenticated;
        } else {
          _status = AuthStatus.unauthenticated;
        }
      }
    } catch (_) {
      _status = AuthStatus.unauthenticated;
    }

    notifyListeners();
  }

  /// Proses Login ke Backend Laravel
  Future<bool> login({
    required String username,
    required String password,
  }) async {
    _status = AuthStatus.authenticating;
    _errorMessage = null;
    _isAdminBlocked = false;
    _adminBlockedMessage = null;
    notifyListeners();

    try {
      _user = await _repository.login(username: username, password: password);
      _status = AuthStatus.authenticated;
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      if (e.statusCode == 403) {
        // Akun Admin / Waka ditolak secara resmi oleh backend mobile
        _isAdminBlocked = true;
        _adminBlockedMessage = e.message;
        _status = AuthStatus.unauthenticated;
      } else {
        _errorMessage = e.message;
        _status = AuthStatus.error;
      }
      notifyListeners();
      return false;
    } catch (e) {
      _errorMessage = 'Gagal melakukan autentikasi: $e';
      _status = AuthStatus.error;
      notifyListeners();
      return false;
    }
  }

  /// Logout Sesi
  Future<void> logout() async {
    await _repository.logout();
    _user = null;
    _status = AuthStatus.unauthenticated;
    _errorMessage = null;
    _isAdminBlocked = false;
    notifyListeners();
  }

  /// Mengajukan Token Lupa Password
  Future<Map<String, dynamic>?> forgotPassword({
    required String username,
    required String phoneNumber,
  }) async {
    _errorMessage = null;
    try {
      return await _repository.forgotPassword(
        username: username,
        phoneNumber: phoneNumber,
      );
    } on ApiException catch (e) {
      _errorMessage = e.message;
      notifyListeners();
      return null;
    } catch (e) {
      _errorMessage = 'Terjadi kesalahan: $e';
      notifyListeners();
      return null;
    }
  }

  /// Reset Password Baru
  Future<bool> resetPassword({
    required String username,
    required String token,
    required String password,
    required String passwordConfirmation,
  }) async {
    _errorMessage = null;
    try {
      await _repository.resetPassword(
        username: username,
        token: token,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );
      return true;
    } on ApiException catch (e) {
      _errorMessage = e.message;
      notifyListeners();
      return false;
    } catch (e) {
      _errorMessage = 'Gagal memperbarui password: $e';
      notifyListeners();
      return false;
    }
  }

  /// Bersihkan Pesan Error
  void clearErrors() {
    _errorMessage = null;
    _isAdminBlocked = false;
    _adminBlockedMessage = null;
    notifyListeners();
  }
}

