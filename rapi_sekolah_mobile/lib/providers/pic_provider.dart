import 'package:flutter/foundation.dart';
import '../core/network/api_exception.dart';
import '../data/models/complaint_model.dart';
import '../data/repositories/complaint_repository.dart';

/// 3 Pilihan Status Baku Siklus Perbaikan Sarpras
class RepairStatusOptions {
  RepairStatusOptions._();

  static const String belumDiperbaiki = 'Belum Diperbaiki';
  static const String menungguSparePart = 'Menunggu Spare Part';
  static const String sudahDiperbaiki = 'Sudah Diperbaiki';

  static const List<String> all = [
    belumDiperbaiki,
    menungguSparePart,
    sudahDiperbaiki,
  ];

  static bool isValid(String status) => all.contains(status);
}

/// Provider State Management Khusus PIC Ruangan
class PicProvider extends ChangeNotifier {
  final ComplaintRepository _complaintRepository;

  List<ComplaintModel> _assignedComplaints = [];
  bool _isLoading = false;
  bool _isUpdating = false;
  String? _errorMessage;
  String? _selectedFilterStatus; // null = Semua Status

  PicProvider({ComplaintRepository? complaintRepository})
      : _complaintRepository = complaintRepository ?? ComplaintRepository();

  // Getters
  List<ComplaintModel> get assignedComplaints => _assignedComplaints;
  bool get isLoading => _isLoading;
  bool get isUpdating => _isUpdating;
  String? get errorMessage => _errorMessage;
  String? get selectedFilterStatus => _selectedFilterStatus;

  // Statistik Siklus Perbaikan untuk Dashboard PIC
  int get notRepairedCount =>
      _assignedComplaints.where((c) => c.statusRepair == RepairStatusOptions.belumDiperbaiki).length;

  int get waitingPartCount =>
      _assignedComplaints.where((c) => c.statusRepair == RepairStatusOptions.menungguSparePart).length;

  int get finishedCount =>
      _assignedComplaints.where((c) => c.statusRepair == RepairStatusOptions.sudahDiperbaiki).length;

  /// Daftar Pengaduan Setelah Terfilter oleh Tab / Chip
  List<ComplaintModel> get filteredComplaints {
    if (_selectedFilterStatus == null || _selectedFilterStatus!.isEmpty) {
      return _assignedComplaints;
    }
    return _assignedComplaints.where((c) => c.statusRepair == _selectedFilterStatus).toList();
  }

  /// Memuat Daftar Pengaduan yang Didelegasikan ke PIC Ini
  Future<void> fetchAssignedComplaints() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      // Backend Laravel secara otomatis memfilter pengaduan berdasarkan pic_id dari token Bearer
      _assignedComplaints = await _complaintRepository.getComplaints();
      _isLoading = false;
    } on ApiException catch (e) {
      _errorMessage = e.message;
      _isLoading = false;
    } catch (e) {
      _errorMessage = 'Gagal memuat tugas pengaduan PIC: $e';
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Mengatur Filter Status Tampilan
  void setFilterStatus(String? status) {
    _selectedFilterStatus = status;
    notifyListeners();
  }

  /// Memperbarui Status Siklus Perbaikan Sarpras
  /// Hanya boleh bernilai: "Belum Diperbaiki", "Menunggu Spare Part", "Sudah Diperbaiki"
  Future<bool> updateStatus({
    required int complaintId,
    required String newStatus,
  }) async {
    if (!RepairStatusOptions.isValid(newStatus)) {
      _errorMessage = 'Status tidak valid. Pilihan hanya boleh: ${RepairStatusOptions.all.join(", ")}';
      notifyListeners();
      return false;
    }

    _isUpdating = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final updated = await _complaintRepository.updateStatus(
        complaintId: complaintId,
        status: newStatus,
      );

      // Perbarui data pengaduan pada list lokal
      final index = _assignedComplaints.indexWhere((c) => c.id == complaintId);
      if (index != -1) {
        _assignedComplaints[index] = updated;
      }

      _isUpdating = false;
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      _isUpdating = false;
      _errorMessage = e.message;
      notifyListeners();
      return false;
    } catch (e) {
      _isUpdating = false;
      _errorMessage = 'Gagal memperbarui status perbaikan: $e';
      notifyListeners();
      return false;
    }
  }

  /// Menghapus Pengaduan Secara Manual (Khusus PIC Ruangan atau Admin)
  Future<bool> deleteComplaint(int complaintId) async {
    try {
      final success = await _complaintRepository.deleteComplaint(complaintId);
      if (success) {
        _assignedComplaints.removeWhere((c) => c.id == complaintId);
        notifyListeners();
        return true;
      }
      return false;
    } on ApiException catch (e) {
      _errorMessage = e.message;
      notifyListeners();
      return false;
    } catch (e) {
      _errorMessage = 'Gagal menghapus tugas pengaduan: $e';
      notifyListeners();
      return false;
    }
  }
}

