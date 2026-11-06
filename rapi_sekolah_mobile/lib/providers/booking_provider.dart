import 'package:flutter/foundation.dart';
import '../core/config/booking_sessions.dart';
import '../core/network/api_exception.dart';
import '../core/utils/date_formatter.dart';
import '../data/models/event_model.dart';
import '../data/models/room_model.dart';
import '../data/repositories/event_repository.dart';
import '../data/repositories/room_repository.dart';

/// Provider State Management Pengajuan Peminjaman & Sistem Sesi
class BookingProvider extends ChangeNotifier {
  final EventRepository _eventRepository;
  final RoomRepository _roomRepository;

  List<RoomModel> _rooms = [];
  RoomModel? _selectedRoom;
  DateTime _selectedDate = DateTime.now();

  // Set ID Sesi yang Dipilih Pengguna (misal {2, 3})
  final Set<int> _selectedSessionIds = {};

  // Pemetaan Ketersediaan Sesi Real-Time
  // Key: Session ID (1 - 13) -> Value: true jika sudah terpakai
  final Map<int, bool> _sessionOccupied = {};
  final Map<int, String> _sessionOccupiedBy = {};

  bool _isLoadingRooms = false;
  bool _isCheckingAvailability = false;
  bool _isSubmitting = false;
  String? _errorMessage;
  List<dynamic>? _serverConflicts; // Konflik dari balasan HTTP 422 backend

  // Riwayat Peminjaman Milik Sendiri
  List<EventModel> _myBookings = [];
  bool _isLoadingMyBookings = false;

  BookingProvider({
    EventRepository? eventRepository,
    RoomRepository? roomRepository,
  })  : _eventRepository = eventRepository ?? EventRepository(),
        _roomRepository = roomRepository ?? RoomRepository();

  // Getters
  List<RoomModel> get rooms => _rooms;
  RoomModel? get selectedRoom => _selectedRoom;
  DateTime get selectedDate => _selectedDate;
  Set<int> get selectedSessionIds => _selectedSessionIds;
  bool get isLoadingRooms => _isLoadingRooms;
  bool get isCheckingAvailability => _isCheckingAvailability;
  bool get isSubmitting => _isSubmitting;
  String? get errorMessage => _errorMessage;
  List<dynamic>? get serverConflicts => _serverConflicts;
  List<EventModel> get myBookings => _myBookings;
  bool get isLoadingMyBookings => _isLoadingMyBookings;

  /// Memeriksa apakah sesi tertentu terpakai di ruangan & tanggal yang dipilih
  bool isSessionOccupied(int sessionId) => _sessionOccupied[sessionId] ?? false;

  /// Mendapatkan nama kegiatan yang menggunakan sesi tersebut
  String? getSessionOccupant(int sessionId) => _sessionOccupiedBy[sessionId];

  /// Menghitung Jam Mulai Terpilih Berdasarkan Sesi Awal (Format "HH:mm")
  String? get calculatedStartTime {
    if (_selectedSessionIds.isEmpty) return null;
    final sorted = _selectedSessionIds.toList()..sort();
    final firstSession = BookingSessionsData.findById(sorted.first);
    return firstSession?.startTime;
  }

  /// Menghitung Jam Selesai Terpilih Berdasarkan Sesi Akhir (Format "HH:mm")
  String? get calculatedEndTime {
    if (_selectedSessionIds.isEmpty) return null;
    final sorted = _selectedSessionIds.toList()..sort();
    final lastSession = BookingSessionsData.findById(sorted.last);
    return lastSession?.endTime;
  }

  /// Ringkasan Sesi Terpilih (misal: "Sesi 2 - Sesi 3 (07:45 - 09:15 WIB)")
  String? get selectedTimeSummary {
    if (_selectedSessionIds.isEmpty) return null;
    final sorted = _selectedSessionIds.toList()..sort();
    if (sorted.length == 1) {
      final s = BookingSessionsData.findById(sorted.first);
      return '${s?.sessionName} (${s?.formattedTime})';
    } else {
      final first = BookingSessionsData.findById(sorted.first);
      final last = BookingSessionsData.findById(sorted.last);
      return 'Sesi ${first?.id} s/d Sesi ${last?.id} (${first?.startTime} - ${last?.endTime} WIB)';
    }
  }

  /// Memuat Daftar Ruangan Aktif untuk Dropdown Pilihan
  Future<void> loadRooms() async {
    _isLoadingRooms = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _rooms = await _roomRepository.getRooms();
      if (_rooms.isNotEmpty && _selectedRoom == null) {
        _selectedRoom = _rooms.first;
        await checkSessionAvailability();
      }
      _isLoadingRooms = false;
    } catch (e) {
      _errorMessage = 'Gagal memuat ruangan: $e';
      _isLoadingRooms = false;
    }
    notifyListeners();
  }

  /// Memilih Ruangan
  void selectRoom(RoomModel room) {
    _selectedRoom = room;
    _selectedSessionIds.clear();
    checkSessionAvailability();
    notifyListeners();
  }

  /// Memilih Tanggal
  void selectDate(DateTime date) {
    _selectedDate = date;
    _selectedSessionIds.clear();
    checkSessionAvailability();
    notifyListeners();
  }

  /// Menyeleksi atau Membatalkan Pilihan Sesi Jam
  void toggleSession(int sessionId) {
    if (isSessionOccupied(sessionId)) return; // Jangan izinkan pilih sesi yang sudah terpakai

    if (_selectedSessionIds.contains(sessionId)) {
      _selectedSessionIds.remove(sessionId);
    } else {
      // Pastikan sesi yang dipilih berurutan (kontinu)
      if (_selectedSessionIds.isNotEmpty) {
        final sorted = _selectedSessionIds.toList()..sort();
        final minId = sorted.first;
        final maxId = sorted.last;

        if (sessionId == minId - 1 || sessionId == maxId + 1) {
          _selectedSessionIds.add(sessionId);
        } else {
          // Jika tidak berurutan, reset dan pilih sesi yang baru diklik
          _selectedSessionIds.clear();
          _selectedSessionIds.add(sessionId);
        }
      } else {
        _selectedSessionIds.add(sessionId);
      }
    }
    notifyListeners();
  }

  /// Pengecekan Ketersediaan 13 Sesi Secara Real-Time (Conflict Detection)
  Future<void> checkSessionAvailability() async {
    if (_selectedRoom == null) return;

    _isCheckingAvailability = true;
    _sessionOccupied.clear();
    _sessionOccupiedBy.clear();
    notifyListeners();

    try {
      final dateStr = DateFormatter.formatApiDate(_selectedDate);

      // Ambil agenda yang sudah disetujui (approved) dan yang masih diajukan (pending)
      // Karena backend menolak tabrakan baik dengan approved maupun pending
      final results = await Future.wait([
        _eventRepository.getEvents(
          roomId: _selectedRoom!.id,
          date: dateStr,
          status: 'approved',
        ),
        _eventRepository.getEvents(
          roomId: _selectedRoom!.id,
          date: dateStr,
          status: 'pending',
        ),
      ]);

      final activeEvents = [...results[0], ...results[1]];

      // Cek setiap 13 sesi terhadap rentang waktu agenda yang ada
      for (final session in BookingSessionsData.standardSessions) {
        for (final event in activeEvents) {
          // Logika tabrakan interval: (StartA < EndB) && (EndA > StartB)
          if (session.startTime.compareTo(event.endTime) < 0 &&
              session.endTime.compareTo(event.startTime) > 0) {
            _sessionOccupied[session.id] = true;
            _sessionOccupiedBy[session.id] = event.eventName;
            break;
          }
        }
      }
      _isCheckingAvailability = false;
    } catch (_) {
      _isCheckingAvailability = false;
    }
    notifyListeners();
  }

  /// Mengirim Pengajuan Peminjaman Ruangan ke Backend Laravel
  Future<bool> submitBooking({required String eventName}) async {
    if (_selectedRoom == null) {
      _errorMessage = 'Silakan pilih ruangan terlebih dahulu.';
      notifyListeners();
      return false;
    }

    if (_selectedSessionIds.isEmpty) {
      _errorMessage = 'Silakan pilih minimal 1 sesi waktu peminjaman.';
      notifyListeners();
      return false;
    }

    final startTime = calculatedStartTime;
    final endTime = calculatedEndTime;

    if (startTime == null || endTime == null) {
      _errorMessage = 'Format jam peminjaman tidak valid.';
      notifyListeners();
      return false;
    }

    _isSubmitting = true;
    _errorMessage = null;
    _serverConflicts = null;
    notifyListeners();

    try {
      await _eventRepository.createEvent(
        roomId: _selectedRoom!.id,
        eventName: eventName.trim(),
        date: DateFormatter.formatApiDate(_selectedDate),
        startTime: startTime,
        endTime: endTime,
      );

      _isSubmitting = false;
      _selectedSessionIds.clear();
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      _isSubmitting = false;
      _errorMessage = e.message;

      // Tangani respon bentrok server (422 Conflicts)
      if (e.isConflict || e.conflicts != null) {
        _serverConflicts = e.conflicts;
      }

      // Perbarui ketersediaan chip sesi terkini dari server
      await checkSessionAvailability();
      notifyListeners();
      return false;
    } catch (e) {
      _isSubmitting = false;
      _errorMessage = 'Gagal mengirim pengajuan: $e';
      notifyListeners();
      return false;
    }
  }

  /// Memuat Riwayat Pengajuan Agenda Milik Sendiri
  Future<void> fetchMyBookings(int currentUserId) async {
    _isLoadingMyBookings = true;
    notifyListeners();

    try {
      // Ambil agenda bulan ini, lalu filter yang pic_id / user_id nya sesuai akun login
      final all = await _eventRepository.getEvents();
      _myBookings = all.where((e) => e.organizer.id == currentUserId).toList();
      _isLoadingMyBookings = false;
    } catch (_) {
      _isLoadingMyBookings = false;
    }
    notifyListeners();
  }
}

