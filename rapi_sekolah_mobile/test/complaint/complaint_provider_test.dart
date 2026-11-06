import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:rapi_sekolah_mobile/core/utils/image_compressor.dart';
import 'package:rapi_sekolah_mobile/data/models/complaint_model.dart';
import 'package:rapi_sekolah_mobile/data/repositories/complaint_repository.dart';
import 'package:rapi_sekolah_mobile/providers/complaint_provider.dart';

class MockComplaintRepository extends ComplaintRepository {
  @override
  Future<List<ComplaintModel>> getComplaints({int? roomId, String? status}) async {
    return [
      ComplaintModel(
        id: 1,
        description: 'AC unit 2 bocor dan menetes ke dekat stopkontak deret C.',
        statusRepair: 'Belum Diperbaiki',
        photoUrl: 'https://example.com/photo.jpg',
        createdAt: DateTime.now(),
        room: const ComplaintRoomModel(id: 1, name: 'Lab RPL 1', location: 'Gedung B'),
        reporter: const ComplaintReporterModel(id: 4, fullname: 'Guru Andi'),
      ),
    ];
  }

  @override
  Future<ComplaintModel> createComplaint({
    required int roomId,
    required String description,
    File? photoFile,
  }) async {
    return ComplaintModel(
      id: 2,
      description: description,
      statusRepair: 'Belum Diperbaiki',
      createdAt: DateTime.now(),
      room: const ComplaintRoomModel(id: 1, name: 'Lab RPL 1', location: 'Gedung B'),
      reporter: const ComplaintReporterModel(id: 4, fullname: 'Guru Andi'),
    );
  }
}

void main() {
  group('Pengujian Modul 5: Pengaduan Kerusakan Sarpras & Kompresi', () {
    test('Kalkulasi persentase hemat kompresi foto', () {
      const result = CompressionResult(
        file: null as dynamic, // dummy
        originalSizeBytes: 2000000, // ~2 MB
        compressedSizeBytes: 400000,  // ~400 KB
      );

      expect(result.savingsPercentage, '80%');
      expect(CompressionResult.formatBytes(2000000), '1.91 MB');
      expect(CompressionResult.formatBytes(400000), '390.6 KB');
    });

    test('Validasi deskripsi kerusakan minimal 10 karakter', () async {
      final provider = ComplaintProvider(repository: MockComplaintRepository());

      // Kirim deskripsi kurang dari 10 karakter -> Harus Ditolak
      final success = await provider.submitComplaint(
        roomId: 1,
        description: 'AC Rusak',
      );

      expect(success, false);
      expect(provider.errorMessage, contains('minimal 10 karakter'));
    });

    test('Pengiriman pengaduan kerusakan berhasil jika deskripsi valid', () async {
      final provider = ComplaintProvider(repository: MockComplaintRepository());

      final success = await provider.submitComplaint(
        roomId: 1,
        description: 'Proyektor mati mendadak saat kegiatan praktikum siswa.',
      );

      expect(success, true);
      expect(provider.complaints.length, 1);
      expect(provider.complaints.first.description, contains('Proyektor mati'));
    });
  });
}

