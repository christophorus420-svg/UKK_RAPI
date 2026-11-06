import 'package:flutter_test/flutter_test.dart';
import 'package:rapi_sekolah_mobile/core/network/api_exception.dart';
import 'package:rapi_sekolah_mobile/data/models/event_model.dart';
import 'package:rapi_sekolah_mobile/data/models/room_model.dart';
import 'package:rapi_sekolah_mobile/data/repositories/event_repository.dart';
import 'package:rapi_sekolah_mobile/data/repositories/room_repository.dart';
import 'package:rapi_sekolah_mobile/providers/booking_provider.dart';

class MockBookingRoomRepository extends RoomRepository {
  @override
  Future<List<RoomModel>> getRooms() async {
    return const [
      RoomModel(id: 1, roomName: 'Lab RPL 1', location: 'Gedung B', capacity: 36),
    ];
  }
}

class MockBookingEventRepository extends EventRepository {
  final bool shouldTriggerConflict422;

  MockBookingEventRepository({this.shouldTriggerConflict422 = false});

  @override
  Future<List<EventModel>> getEvents({int? roomId, String? date, int? month, int? year, String? status}) async {
    // Simulasi ada event eksis di Lab RPL 1 jam 08:30 - 10:00 (Sesi 3 & 4)
    if (status == 'approved') {
      return [
        EventModel(
          id: 10,
          eventName: 'Praktikum Cloud',
          date: DateTime.now(),
          dateFormatted: 'Hari Ini',
          startTime: '08:30',
          endTime: '10:00',
          status: 'approved',
          room: const EventRoomModel(id: 1, name: 'Lab RPL 1', location: 'Gedung B', capacity: 36),
          organizer: const EventOrganizerModel(id: 4, fullname: 'Guru Andi', role: 'guru'),
        ),
      ];
    }
    return [];
  }

  @override
  Future<EventModel> createEvent({
    required int roomId,
    required String eventName,
    required String date,
    required String startTime,
    required String endTime,
  }) async {
    if (shouldTriggerConflict422) {
      throw ApiException(
        message: 'Jadwal ruangan bentrok dengan agenda lain.',
        statusCode: 422,
        isConflict: true,
        conflicts: [
          {
            'event_name': 'Praktikum Cloud',
            'time': '08:30 - 10:00',
            'status': 'approved',
          }
        ],
      );
    }

    return EventModel(
      id: 99,
      eventName: eventName,
      date: DateTime.parse(date),
      dateFormatted: 'Hari Uji',
      startTime: startTime,
      endTime: endTime,
      status: 'pending',
      room: const EventRoomModel(id: 1, name: 'Lab RPL 1', location: 'Gedung B', capacity: 36),
      organizer: const EventOrganizerModel(id: 4, fullname: 'Guru Penguji', role: 'guru'),
    );
  }
}

void main() {
  group('Pengujian Modul 4: Sistem Sesi & Peminjaman Ruang', () {
    test('Perhitungan jam mulai dan selesai otomatis dari pemilihan Sesi', () {
      final provider = BookingProvider(
        roomRepository: MockBookingRoomRepository(),
        eventRepository: MockBookingEventRepository(),
      );

      // Pilih Sesi 1 (07:00 - 07:45)
      provider.toggleSession(1);
      expect(provider.calculatedStartTime, '07:00');
      expect(provider.calculatedEndTime, '07:45');

      // Tambah Sesi 2 (07:45 - 08:30) berurutan
      provider.toggleSession(2);
      expect(provider.calculatedStartTime, '07:00');
      expect(provider.calculatedEndTime, '08:30');
      expect(provider.selectedSessionIds.length, 2);
    });

    test('Deteksi ketersediaan sesi real-time (Sesi 3 & 4 terpakai oleh agenda lain)', () async {
      final provider = BookingProvider(
        roomRepository: MockBookingRoomRepository(),
        eventRepository: MockBookingEventRepository(),
      );

      await provider.loadRooms();
      await provider.checkSessionAvailability();

      // Sesi 1 (07:00 - 07:45) harus bebas
      expect(provider.isSessionOccupied(1), false);

      // Sesi 3 (08:30 - 09:15) & Sesi 4 (09:15 - 10:00) harus terkunci / terpakai
      expect(provider.isSessionOccupied(3), true);
      expect(provider.isSessionOccupied(4), true);
      expect(provider.getSessionOccupant(3), 'Praktikum Cloud');
    });

    test('Penanganan respon 422 saat server mendeteksi bentrok jadwal', () async {
      final provider = BookingProvider(
        roomRepository: MockBookingRoomRepository(),
        eventRepository: MockBookingEventRepository(shouldTriggerConflict422: true),
      );

      await provider.loadRooms();
      provider.toggleSession(1);

      final success = await provider.submitBooking(eventName: 'Rapat Dadakan');

      expect(success, false);
      expect(provider.serverConflicts, isNotNull);
      expect(provider.serverConflicts!.length, 1);
      expect(provider.errorMessage, contains('bentrok'));
    });
  });
}

