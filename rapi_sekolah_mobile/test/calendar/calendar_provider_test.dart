import 'package:flutter_test/flutter_test.dart';
import 'package:rapi_sekolah_mobile/data/models/event_model.dart';
import 'package:rapi_sekolah_mobile/data/models/room_model.dart';
import 'package:rapi_sekolah_mobile/data/repositories/event_repository.dart';
import 'package:rapi_sekolah_mobile/data/repositories/room_repository.dart';
import 'package:rapi_sekolah_mobile/providers/calendar_provider.dart';

// Mock Repositories
class MockRoomRepository extends RoomRepository {
  @override
  Future<List<RoomModel>> getRooms() async {
    return const [
      RoomModel(id: 1, roomName: 'Lab Komputer RPL 1', location: 'Gedung B', capacity: 36),
      RoomModel(id: 2, roomName: 'Aula Graha Wiyata', location: 'Gedung Utama', capacity: 300),
    ];
  }
}

class MockEventRepository extends EventRepository {
  @override
  Future<List<EventModel>> getEvents({int? roomId, String? date, int? month, int? year, String? status}) async {
    final today = DateTime.now();
    return [
      EventModel(
        id: 1,
        eventName: 'Pelatihan Cloud Computing',
        date: today,
        dateFormatted: 'Hari Ini',
        startTime: '08:00',
        endTime: '11:30',
        status: 'approved',
        room: const EventRoomModel(id: 1, name: 'Lab Komputer RPL 1', location: 'Gedung B', capacity: 36),
        organizer: const EventOrganizerModel(id: 4, fullname: 'Andi Pratama, S.Pd', role: 'guru'),
      ),
      EventModel(
        id: 2,
        eventName: 'Gladi Bersih Pentas Seni',
        date: today,
        dateFormatted: 'Hari Ini',
        startTime: '13:00',
        endTime: '16:00',
        status: 'approved',
        room: const EventRoomModel(id: 2, name: 'Aula Graha Wiyata', location: 'Gedung Utama', capacity: 300),
        organizer: const EventOrganizerModel(id: 5, fullname: 'OSIS SMK', role: 'guru'),
        moveReason: 'Dipindahkan karena ada rapat guru di ruang rapat',
      ),
    ];
  }
}

void main() {
  group('Pengujian Modul 3: Kalender Publik & Filter Ruangan', () {
    late CalendarProvider provider;

    setUp(() async {
      provider = CalendarProvider(
        roomRepository: MockRoomRepository(),
        eventRepository: MockEventRepository(),
      );
      await provider.fetchInitialData();
    });

    test('Memuat daftar ruangan dan agenda bulan berjalan dengan benar', () {
      expect(provider.rooms.length, 2);
      expect(provider.rooms[0].roomName, 'Lab Komputer RPL 1');
      expect(provider.selectedDayEvents.length, 2);
    });

    test('Filter agenda berdasarkan ruangan tertentu (Lab RPL 1)', () {
      provider.setRoomFilter(1);
      final filtered = provider.selectedDayEvents;

      expect(filtered.length, 1);
      expect(filtered.first.eventName, 'Pelatihan Cloud Computing');
      expect(filtered.first.room.id, 1);
    });

    test('Filter agenda berdasarkan pencarian teks ("Pentas Seni")', () {
      provider.setSearchQuery('pentas seni');
      final results = provider.selectedDayEvents;

      expect(results.length, 1);
      expect(results.first.eventName, contains('Pentas Seni'));
    });

    test('Mendeteksi status agenda yang dipindahkan oleh Waka (isMoved & moveReason)', () {
      final movedEvent = provider.selectedDayEvents.firstWhere((e) => e.id == 2);

      expect(movedEvent.isMoved, true);
      expect(movedEvent.moveReason, contains('Dipindahkan karena ada rapat'));
    });
  });
}

