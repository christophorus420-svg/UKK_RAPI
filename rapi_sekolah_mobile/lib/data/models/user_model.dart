/// Model Data Pengguna (User) RAPI-Sekolah
/// Sinkron dengan model User Laravel dan respon API `/api/login` & `/api/me`.
class UserModel {
  final int id;
  final String username;
  final String fullname;
  final String role; // 'admin', 'pic', 'guru', 'guest'
  final String roleLabel;
  final String? phoneNumber;
  final String? whatsappNumber;

  const UserModel({
    required this.id,
    required this.username,
    required this.fullname,
    required this.role,
    required this.roleLabel,
    this.phoneNumber,
    this.whatsappNumber,
  });

  /// Factory untuk membuat objek UserModel dari JSON respon backend Laravel
  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      username: json['username']?.toString() ?? '',
      fullname: json['fullname']?.toString() ?? '',
      role: json['role']?.toString().toLowerCase() ?? 'guest',
      roleLabel: json['role_label']?.toString() ?? _defaultRoleLabel(json['role']?.toString()),
      phoneNumber: json['phone_number']?.toString(),
      whatsappNumber: json['whatsapp_number']?.toString(),
    );
  }

  /// Mengubah objek UserModel kembali menjadi Map JSON untuk disimpan di cache lokal
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'username': username,
      'fullname': fullname,
      'role': role,
      'role_label': roleLabel,
      'phone_number': phoneNumber,
      'whatsapp_number': whatsappNumber,
    };
  }

  // Helper Hak Akses Berdasarkan Role
  bool get isAdmin => role == 'admin';
  bool get isPic => role == 'pic';
  bool get isGuru => role == 'guru';
  bool get isGuest => role == 'guest';

  /// Izin mengajukan agenda/peminjaman ruangan (Guru & Organisasi/Siswa)
  bool get canBookRoom => isGuru || isGuest;

  /// Izin mengajukan laporan kerusakan sarpras
  bool get canReportComplaint => isGuru || isGuest || isPic;

  /// Izin khusus PIC untuk mengubah status perbaikan (3 tahap siklus)
  bool get canManageComplaints => isPic;

  static String _defaultRoleLabel(String? role) {
    switch (role?.toLowerCase()) {
      case 'admin':
        return 'Waka Sarpras / Admin';
      case 'pic':
        return 'PIC Penanggung Jawab Ruang';
      case 'guru':
        return 'Guru / Pembina Organisasi';
      case 'guest':
        return 'Siswa / Tamu Sekolah';
      default:
        return 'Pengguna';
    }
  }

  UserModel copyWith({
    int? id,
    String? username,
    String? fullname,
    String? role,
    String? roleLabel,
    String? phoneNumber,
    String? whatsappNumber,
  }) {
    return UserModel(
      id: id ?? this.id,
      username: username ?? this.username,
      fullname: fullname ?? this.fullname,
      role: role ?? this.role,
      roleLabel: roleLabel ?? this.roleLabel,
      phoneNumber: phoneNumber ?? this.phoneNumber,
      whatsappNumber: whatsappNumber ?? this.whatsappNumber,
    );
  }
}

