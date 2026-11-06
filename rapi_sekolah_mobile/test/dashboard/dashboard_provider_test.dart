import 'package:flutter_test/flutter_test.dart';
import 'package:rapi_sekolah_mobile/data/models/complaint_model.dart';
import 'package:rapi_sekolah_mobile/data/models/event_model.dart';
import 'package:rapi_sekolah_mobile/data/models/user_model.dart';
import 'package:rapi_sekolah_mobile/data/repositories/complaint_repository.dart';
import 'package:rapi_sekolah_mobile/data/repositories/event_repository.dart';
import 'package:rapi_sekolah_mobile/providers/dashboard_provider.dart';

class MockDashboardEventRepo extends EventRepository {
  @override
  Future<List<EventModel>> getEvents({int? roomId, String? date, int? month, int? year, String? status}) async {
    final today = DateTime.now();

    return [
      // 1. Agenda normal hari ini
      EventModel(
        id: 1,
        eventName: 'Agenda Normal Hari Ini',
        date: today,
        dateFormatted: 'Hari Ini',
        startTime: '08:00',
        endTime: '10:00',
        status: 'approved',
        room: const EventRoomModel(id: 1, name: 'Lab RPL 1', location: 'Gedung B', capacity: 36),
        organizer: const EventOrganizerModel(id: 4, fullname: 'Guru Andi', role: 'guru'),
      ),
      // 2. Agenda milik Guru Andi yang dipindahkan oleh Waka Sarpras
      EventModel(
        id: 2,
        eventName: 'Pelatihan IoT Siswa',
        date: today.add(const Duration(days: 1)),
        dateFormatted: 'Besok',
        startTime: '10:00',
        endTime: '12:00',
        status: 'approved',
        room: const EventRoomModel(id: 3, name: 'Bengkel IoT', location: 'Gedung C', capacity: 30),
        organizer: const EventOrganizerModel(id: 4, fullname: 'Guru Andi', role: 'guru'),
        moveReason: 'Lab RPL 1 digunakan darurat untuk ANBK Dinas Pendidikan',
      ),
      // 3. Agenda berstatus pending
      EventModel(
        id: 3,
        eventName: 'Bimbingan LKS',
        date: today.add(const Duration(days: 2)),
        dateFormatted: 'Lusa',
        startTime: '13:00',
        endTime: '15:00',
        status: 'pending',
        room: const EventRoomModel(id: 1, name: 'Lab RPL 1', location: 'Gedung B', capacity: 36),
        organizer: const EventOrganizerModel(id: 4, fullname: 'Guru Andi', role: 'guru'),
      ),
    ];
  }
}

class MockDashboardComplaintRepo extends ComplaintRepository {
  @override
  Future<List<ComplaintModel>> getComplaints({int? roomId, String? status}) async {
    return [
      ComplaintModel(
        id: 1,
        description: 'Proyektor mati indikator merah',
        statusRepair: 'Belum Diperbaiki',
        createdAt: DateTime.now(),
        room: const ComplaintRoomModel(id: 1, name: 'Lab RPL 1', location: 'Gedung B'),
        reporter: const ComplaintReporterModel(id: 4, fullname: 'Guru Andi'),
      ),
    ];
  }
}

void main() {
  group('Pengujian Modul 6: Dashboard Guru & Notifikasi Pemindahan Ruang', () {
    const dummyUser = UserModel(
      id: 4,
      username: 'guru_andi',
      fullname: 'Andi Pratama, S.Pd',
      role: 'guru',
      roleLabel: 'Guru Produktif RPL',
    );

    test('Memuat statistik peminjaman pending dan aduan aktif', () async {
      final provider = DashboardProvider(
        eventRepository: MockDashboardEventRepo(),
        complaintRepository: MockDashboardComplaintRepo(),
      );

      await provider.fetchDashboardData(dummyUser);

      expect(provider.todayEvents.length, 1);
      expect(provider.pendingBookingsCount, 1);
      expect(provider.activeComplaintsCount, 1);
    });

    test('Mendeteksi agenda dipindahkan oleh Waka Sarpras & memicu pop-up dialog', () async {
      final provider = DashboardProvider(
        eventRepository: MockDashboardEventRepo(),
        complaintRepository: MockDashboardComplaintRepo(),
      );

      await provider.fetchDashboardData(dummyUser);

      // Harus menemukan 1 agenda dipindah
      expect(provider.movedEvents.length, 1);
      expect(provider.movedEvents.first.eventName, 'Pelatihan IoT Siswa');
      expect(provider.movedEvents.first.isMoved, true);
      expect(provider.movedEvents.first.moveReason, contains('ANBK Dinas Pendidikan'));

      // Agenda ini harus menjadi pending notification pop-up
      expect(provider.pendingMovedNotification, isNotNull);
      expect(provider.pendingMovedNotification!.id, 2);

      // Setelah diakui (acknowledged), pending notification harus null
      await provider.acknowledgeMovedNotification(2);
      expect(provider.pendingMovedNotification, isNull);
    });
  });
}

