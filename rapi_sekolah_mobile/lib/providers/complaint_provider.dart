import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:image_picker/image_picker.dart';
import '../core/network/api_exception.dart';
import '../core/utils/image_compressor.dart';
import '../data/models/complaint_model.dart';
import '../data/repositories/complaint_repository.dart';

/// Provider State Management Pengaduan Kerusakan Sarpras & Kamera
class ComplaintProvider extends ChangeNotifier {
  final ComplaintRepository _repository;
  final ImagePicker _picker = ImagePicker();

  List<ComplaintModel> _complaints = [];
  bool _isLoading = false;
  bool _isSubmitting = false;
  String? _errorMessage;

  // State Foto Bukti & Kompresi
  File? _selectedPhoto;
  CompressionResult? _compressionResult;

  ComplaintProvider({ComplaintRepository? repository})
      : _repository = repository ?? ComplaintRepository();

  // Getters
  List<ComplaintModel> get complaints => _complaints;
  bool get isLoading => _isLoading;
  bool get isSubmitting => _isSubmitting;
  String? get errorMessage => _errorMessage;
  File? get selectedPhoto => _selectedPhoto;
  CompressionResult? get compressionResult => _compressionResult;

  /// Mengambil Foto Langsung dari Kamera Ponsel
  Future<void> pickFromCamera() async {
    await _pickAndCompress(ImageSource.camera);
  }

  /// Memilih Foto Bukti dari Galeri Ponsel
  Future<void> pickFromGallery() async {
    await _pickAndCompress(ImageSource.gallery);
  }

  Future<void> _pickAndCompress(ImageSource source) async {
    try {
      final XFile? pickedFile = await _picker.pickImage(
        source: source,
        maxWidth: 1920,
        maxHeight: 1080,
        imageQuality: 90,
      );

      if (pickedFile != null) {
        final originalFile = File(pickedFile.path);

        // Lakukan kompresi otomatis sebelum upload
        final result = await ImageCompressor.compressImage(originalFile);

        _selectedPhoto = result.file;
        _compressionResult = result;
        _errorMessage = null;
        notifyListeners();
      }
    } catch (e) {
      _errorMessage = 'Gagal mengambil gambar: $e';
      notifyListeners();
    }
  }

  /// Menghapus Foto yang Sedang Dipilih
  void clearPhoto() {
    _selectedPhoto = null;
    _compressionResult = null;
    notifyListeners();
  }

  /// Memuat Daftar Pengaduan Sarpras
  Future<void> fetchComplaints({int? roomId, String? statusFilter}) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _complaints = await _repository.getComplaints(
        roomId: roomId,
        status: statusFilter,
      );
      _isLoading = false;
    } on ApiException catch (e) {
      _errorMessage = e.message;
      _isLoading = false;
    } catch (e) {
      _errorMessage = 'Gagal memuat pengaduan: $e';
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Mengirim Laporan Pengaduan Kerusakan Baru
  Future<bool> submitComplaint({
    required int roomId,
    required String description,
  }) async {
    if (description.trim().length < 10) {
      _errorMessage = 'Deskripsi kerusakan minimal 10 karakter.';
      notifyListeners();
      return false;
    }

    _isSubmitting = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final newComplaint = await _repository.createComplaint(
        roomId: roomId,
        description: description.trim(),
        photoFile: _selectedPhoto,
      );

      // Tambahkan laporan baru ke urutan paling atas list lokal
      _complaints.insert(0, newComplaint);
      _isSubmitting = false;
      clearPhoto();
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      _isSubmitting = false;
      _errorMessage = e.getFirstErrorFor('description') ?? e.message;
      notifyListeners();
      return false;
    } catch (e) {
      _isSubmitting = false;
      _errorMessage = 'Gagal mengirim pengaduan: $e';
      notifyListeners();
      return false;
    }
  }

  /// Menghapus Pengaduan Secara Manual
  Future<bool> deleteComplaint(int complaintId) async {
    try {
      final success = await _repository.deleteComplaint(complaintId);
      if (success) {
        _complaints.removeWhere((c) => c.id == complaintId);
        notifyListeners();
        return true;
      }
      return false;
    } on ApiException catch (e) {
      _errorMessage = e.message;
      notifyListeners();
      return false;
    } catch (e) {
      _errorMessage = 'Gagal menghapus pengaduan: $e';
      notifyListeners();
      return false;
    }
  }
}

