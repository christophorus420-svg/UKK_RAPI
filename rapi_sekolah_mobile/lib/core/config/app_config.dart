/// Konfigurasi Global Aplikasi Mobile RAPI-Sekolah
/// Mengatur Base URL backend Laravel, batas waktu timeout jaringan, dan konstanta sistem.
class AppConfig {
  AppConfig._();

  /// Nama Resmi Aplikasi Proyek UKK
  static const String appName = 'RAPI-Sekolah';
  static const String appVersion = '1.0.0';

  /// Mode koneksi ke backend Laravel:
  /// - emulator: Menggunakan alias 'http://10.0.2.2:8000/api' bawaan Android Emulator
  /// - lan: Menggunakan alamat IP LAN Wi-Fi laptop untuk pengujian di HP Asli
  /// - localhost: Untuk pengujian di desktop Windows / Chrome
  static const String _emulatorUrl = 'http://10.0.2.2:8000/api';
  
  /// Ganti IP LAN berikut sesuai alamat IP Wi-Fi laptop kamu jika ingin uji coba di HP fisik
  /// Hasil ipconfig laptop kamu saat ini: 192.168.205.66
  static const String _lanUrl = 'http://192.168.205.66:8000/api';
  static const String _localUrl = 'http://127.0.0.1:8000/api';

  /// Pilih activeBaseUrl:
  /// Karena aplikasi dijalankan di HP fisik nyata, wajib menggunakan _lanUrl
  static const String baseUrl = _lanUrl;

  /// Batas waktu koneksi dan penerimaan respon (milidetik)
  static const Duration connectTimeout = Duration(seconds: 15);
  static const Duration receiveTimeout = Duration(seconds: 15);
  static const Duration sendTimeout = Duration(seconds: 20);

  /// Default headers yang wajib dikirim ke Laravel API
  static const Map<String, String> defaultHeaders = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  };
}

