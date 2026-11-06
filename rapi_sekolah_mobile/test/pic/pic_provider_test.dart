import 'package:flutter_test/flutter_test.dart';
import 'package:rapi_sekolah_mobile/data/models/complaint_model.dart';
import 'package:rapi_sekolah_mobile/data/repositories/complaint_repository.dart';
import 'package:rapi_sekolah_mobile/providers/pic_provider.dart';

class MockPicComplaintRepository extends ComplaintRepository {
  final List<ComplaintModel> mockComplaints = [
    ComplaintModel(
      id: 1,
      description: 'AC unit 2 bocor dan menetes ke dekat stopkontak',
      statusRepair: 'Belum Diperbaiki',
      createdAt: DateTime.now(),
      room: const ComplaintRoomModel(id: 1, name: 'Lab RPL 1', location: 'Gedung B'),
      reporter: const ComplaintReporterModel(id: 4, fullname: 'Guru Andi'),
    ),
    ComplaintModel(
      id: 2,
      description: 'Mouse komputer nomor 5 tombol klik kiri macet',
      statusRepair: 'Menunggu Spare Part',
      createdAt: DateTime.now(),
      room: const ComplaintRoomModel(id: 1, name: 'Lab RPL 1', location: 'Gedung B'),
      reporter: const ComplaintReporterModel(id: 4, fullname: 'Guru Andi'),
    ),
    ComplaintModel(
      id: 3,
      description: 'Kabel LAN PC 12 putus terjepit kaki meja',
      statusRepair: 'Sudah Diperbaiki',
      createdAt: DateTime.now(),
      room: const ComplaintRoomModel(id: 1, name: 'Lab RPL 1', location: 'Gedung B'),
      reporter: const ComplaintReporterModel(id: 4, fullname: 'Guru Andi'),
    ),
  ];

  @override
  Future<List<ComplaintModel>> getComplaints({int? roomId, String? status}) async {
    return mockComplaints;
  }

  @override
  Future<ComplaintModel> updateStatus({required int complaintId, required String status}) async {
    return ComplaintModel(
      id: complaintId,
      description: 'Deskripsi terupdate',
      statusRepair: status,
      createdAt: DateTime.now(),
      room: const ComplaintRoomModel(id: 1, name: 'Lab RPL 1', location: 'Gedung B'),
      reporter: const ComplaintReporterModel(id: 4, fullname: 'Guru Andi'),
    );
  }
}

void main() {
  group('Pengujian Modul 7: Panel PIC Ruangan & Siklus Perbaikan', () {
    test('Kalkulasi jumlah status siklus perbaikan', () async {
      final provider = PicProvider(complaintRepository: MockPicComplaintRepository());

      await provider.fetchAssignedComplaints();

      expect(provider.assignedComplaints.length, 3);
      expect(provider.notRepairedCount, 1);
      expect(provider.waitingPartCount, 1);
      expect(provider.finishedCount, 1);
    });

    test('Validasi hanya menerima 3 nilai status baku', () {
      expect(RepairStatusOptions.isValid('Belum Diperbaiki'), true);
      expect(RepairStatusOptions.isValid('Menunggu Spare Part'), true);
      expect(RepairStatusOptions.isValid('Sudah Diperbaiki'), true);
      expect(RepairStatusOptions.isValid('Status Asal'), false);
    });

    test('Menolak pembaruan status jika nilai status tidak valid', () async {
      final provider = PicProvider(complaintRepository: MockPicComplaintRepository());
      await provider.fetchAssignedComplaints();

      final success = await provider.updateStatus(
        complaintId: 1,
        newStatus: 'Status Ngawur',
      );

      expect(success, false);
      expect(provider.errorMessage, contains('Status tidak valid'));
    });

    test('Berhasil memperbarui status perbaikan sarpras menjadi "Sudah Diperbaiki"', () async {
      final provider = PicProvider(complaintRepository: MockPicComplaintRepository());
      await provider.fetchAssignedComplaints();

      final success = await provider.updateStatus(
        complaintId: 1,
        newStatus: RepairStatusOptions.sudahDiperbaiki,
      );

      expect(success, true);
      expect(provider.assignedComplaints.first.statusRepair, 'Sudah Diperbaiki');
    });
  });
}

