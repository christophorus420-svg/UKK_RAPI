import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../data/models/complaint_model.dart';
import '../data/models/event_model.dart';
import '../data/models/user_model.dart';
import '../data/repositories/complaint_repository.dart';
import '../data/repositories/event_repository.dart';

/// Provider State Management Dashboard Guru & Notifikasi Pemindahan Ruangan
class DashboardProvider extends ChangeNotifier {
  final EventRepository _eventRepository;
  final ComplaintRepository _complaintRepository;

  List<EventModel> _todayEvents = [];
  List<EventModel> _movedEvents = [];
  EventModel? _pendingMovedNotification; // Agenda dipindah yang akan memicu pop-up dialog

  int _pendingBookingsCount = 0;
  int _activeComplaintsCount = 0;
  bool _isLoading = false;
  String? _errorMessage;

  DashboardProvider({
    EventRepository? eventRepository,
    ComplaintRepository? complaintRepository,
  })  : _eventRepository = eventRepository ?? EventRepository(),
        _complaintRepository = complaintRepository ?? ComplaintRepository();

  // Getters
  List<EventModel> get todayEvents => _todayEvents;
  List<EventModel> get movedEvents => _movedEvents;
  EventModel? get pendingMovedNotification => _pendingMovedNotification;
  int get pendingBookingsCount => _pendingBookingsCount;
  int get activeComplaintsCount => _activeComplaintsCount;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  /// Memuat Seluruh Data Dashboard Guru
  Future<void> fetchDashboardData(UserModel user) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final today = DateTime.now();
      final todayStart = DateTime(today.year, today.month, today.day);

      // Ambil agenda dan pengaduan secara paralel
      final results = await Future.wait([
        _eventRepository.getEvents(),
        _complaintRepository.getComplaints(),
      ]);

      final allEvents = results[0] as List<EventModel>;
      final allComplaints = results[1] as List<ComplaintModel>;

      // 1. Agenda Hari Ini (Approved)
      _todayEvents = allEvents.where((e) {
        final eventDate = DateTime(e.date.year, e.date.month, e.date.day);
        return eventDate.isAtSameMomentAs(todayStart) && e.isApproved;
      }).toList();

      // 2. Agenda Milik Guru Ini yang Dipindahkan oleh Waka Sarpras (move_reason != null)
      _movedEvents = allEvents.where((e) {
        final eventDate = DateTime(e.date.year, e.date.month, e.date.day);
        final isUpcomingOrToday = eventDate.isAfter(todayStart) || eventDate.isAtSameMomentAs(todayStart);
        return e.organizer.id == user.id && e.isMoved && isUpcomingOrToday;
      }).toList();

      // 3. Statistik Cepat
      _pendingBookingsCount = allEvents.where((e) => e.organizer.id == user.id && e.isPending).length;
      _activeComplaintsCount = allComplaints.where((c) => !c.isFinished).length;

      // 4. Periksa apakah ada agenda dipindah yang belum pernah dilihat di pop-up
      await _checkUnacknowledgedMovedEvent();

      _isLoading = false;
    } catch (e) {
      _errorMessage = 'Gagal memuat data dashboard: $e';
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Memeriksa ID agenda dipindahkan yang belum diakui (acknowledged) oleh user
  Future<void> _checkUnacknowledgedMovedEvent() async {
    if (_movedEvents.isEmpty) {
      _pendingMovedNotification = null;
      return;
    }

    try {
      final prefs = await SharedPreferences.getInstance();
      final seenIds = prefs.getStringList('seen_moved_event_ids') ?? [];

      for (final moved in _movedEvents) {
        if (!seenIds.contains(moved.id.toString())) {
          _pendingMovedNotification = moved;
          break; // Munculkan satu per satu pop-up dialog yang belum di-ack
        }
      }
    } catch (_) {
      // Jika shared_preferences gagal, tampilkan agenda pertama
      _pendingMovedNotification = _movedEvents.first;
    }
  }

  /// Menandai Bahwa User Sudah Membaca Notifikasi Pop-up Pemindahan Ruang
  Future<void> acknowledgeMovedNotification(int eventId) async {
    _pendingMovedNotification = null;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final seenIds = prefs.getStringList('seen_moved_event_ids') ?? [];
      if (!seenIds.contains(eventId.toString())) {
        seenIds.add(eventId.toString());
        await prefs.setStringList('seen_moved_event_ids', seenIds);
      }
    } catch (_) {}
  }
}

