/// Model Data Ruangan Sekolah (RoomModel)
/// Sinkron dengan respon API `/api/ruangan` dan `/api/ruangan/{id}`
class RoomModel {
  final int id;
  final String roomName;
  final String location;
  final int capacity;
  final bool isActive;
  final RoomPicModel? pic;

  const RoomModel({
    required this.id,
    required this.roomName,
    required this.location,
    required this.capacity,
    this.isActive = true,
    this.pic,
  });

  factory RoomModel.fromJson(Map<String, dynamic> json) {
    return RoomModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      roomName: json['room_name']?.toString() ?? '',
      location: json['location']?.toString() ?? '',
      capacity: json['capacity'] is int ? json['capacity'] : int.tryParse(json['capacity'].toString()) ?? 0,
      isActive: json['is_active'] == null ? true : (json['is_active'] == true || json['is_active'] == 1),
      pic: json['pic'] != null && json['pic'] is Map<String, dynamic>
          ? RoomPicModel.fromJson(json['pic'] as Map<String, dynamic>)
          : null,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'room_name': roomName,
    'location': location,
    'capacity': capacity,
    'is_active': isActive,
    'pic': pic?.toJson(),
  };

  String get capacityLabel => '$capacity Orang';
}

/// Model PIC Penanggung Jawab Ruangan
class RoomPicModel {
  final int? id;
  final String fullname;
  final String? phone;
  final String? whatsapp;

  const RoomPicModel({
    this.id,
    required this.fullname,
    this.phone,
    this.whatsapp,
  });

  factory RoomPicModel.fromJson(Map<String, dynamic> json) {
    return RoomPicModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
      fullname: json['fullname']?.toString() ?? 'PIC Ruangan',
      phone: json['phone']?.toString() ?? json['phone_number']?.toString(),
      whatsapp: json['whatsapp']?.toString() ?? json['whatsapp_number']?.toString(),
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'fullname': fullname,
    'phone': phone,
    'whatsapp': whatsapp,
  };
}

