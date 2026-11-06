/// Daftar Lengkap Path Endpoint REST API Backend Laravel RAPI-Sekolah
class ApiEndpoints {
  ApiEndpoints._();

  // Autentikasi & Akun
  static const String login = '/login';
  static const String logout = '/logout';
  static const String me = '/me';
  static const String forgotPassword = '/forgot-password';
  static const String resetPassword = '/reset-password';

  // Katalog Ruangan
  static const String rooms = '/ruangan';
  static String roomDetail(int id) => '/ruangan/$id';

  // Agenda & Peminjaman Ruangan
  static const String agenda = '/agenda';
  static String agendaDetail(int id) => '/agenda/$id';

  // Pengaduan Kerusakan Sarpras
  static const String complaints = '/pengaduan';
  static String updateComplaintStatus(int id) => '/pengaduan/$id/status';
}

