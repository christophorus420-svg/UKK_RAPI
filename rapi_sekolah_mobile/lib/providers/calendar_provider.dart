import 'package:flutter/foundation.dart';
import '../core/network/api_exception.dart';
import '../data/models/event_model.dart';
import '../data/models/room_model.dart';
import '../data/repositories/event_repository.dart';
import '../data/repositories/room_repository.dart';

/// Provider State Management Kalender Publik & Agenda Sekolah
class CalendarProvider extends ChangeNotifier {
  final EventRepository _eventRepository;
  final RoomRepository _roomRepository;

  DateTime _focusedDay = DateTime.now();
  DateTime _selectedDay = DateTime.now();

  List<EventModel> _allMonthEvents = [];
  List<RoomModel> _rooms = [];
  int? _selectedRoomId; // Filter ruangan (null = Semua Ruangan)
  String _searchQuery = '';

  bool _isLoading = false;
  String? _errorMessage;

  CalendarProvider({
    EventRepository? eventRepository,
    RoomRepository? roomRepository,
  })  : _eventRepository = eventRepository ?? EventRepository(),
        _roomRepository = roomRepository ?? RoomRepository();

  // Getters
  DateTime get focusedDay => _focusedDay;
  DateTime get selectedDay => _selectedDay;
  List<RoomModel> get rooms => _rooms;
  int? get selectedRoomId => _selectedRoomId;
  String get searchQuery => _searchQuery;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  /// Daftar Agenda pada Tanggal yang Sedang Dipilih (Setelah Terfilter)
  List<EventModel> get selectedDayEvents => getEventsForDay(_selectedDay);

  /// Inisialisasi Data Awal (Daftar Ruangan & Agenda Bulan Berjalan)
  Future<void> fetchInitialData() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      // Ambil ruangan dan agenda bulan ini secara paralel
      final results = await Future.wait([
        _roomRepository.getRooms(),
        _eventRepository.getEvents(
          month: _focusedDay.month,
          year: _focusedDay.year,
        ),
      ]);

      _rooms = results[0] as List<RoomModel>;
      _allMonthEvents = results[1] as List<EventModel>;
      _isLoading = false;
    } on ApiException catch (e) {
      _errorMessage = e.message;
      _isLoading = false;
    } catch (e) {
      _errorMessage = 'Gagal memuat agenda kalender: $e';
      _isLoading = false;
    }

    notifyListeners();
  }

  /// Memuat Agenda untuk Bulan Tertentu (Dipanggil saat geser bulan kalender)
  Future<void> fetchMonthEvents(DateTime month) async {
    _isLoading = true;
    _errorMessage = null;
    _focusedDay = month;
    notifyListeners();

    try {
      _allMonthEvents = await _eventRepository.getEvents(
        month: month.month,
        year: month.year,
      );
      _isLoading = false;
    } on ApiException catch (e) {
      _errorMessage = e.message;
      _isLoading = false;
    } catch (e) {
      _errorMessage = 'Gagal memuat agenda: $e';
      _isLoading = false;
    }

    notifyListeners();
  }

  /// Memilih Tanggal pada Kalender
  void onDaySelected(DateTime selectedDay, DateTime focusedDay) {
    _selectedDay = selectedDay;
    _focusedDay = focusedDay;
    notifyListeners();
  }

  /// Mengganti Bulan Kalender
  void onPageChanged(DateTime focusedDay) {
    _focusedDay = focusedDay;
    fetchMonthEvents(focusedDay);
  }

  /// Filter Berdasarkan Ruangan
  void setRoomFilter(int? roomId) {
    _selectedRoomId = roomId;
    notifyListeners();
  }

  /// Pencarian Judul Agenda
  void setSearchQuery(String query) {
    _searchQuery = query.trim().toLowerCase();
    notifyListeners();
  }

  String? get selectedDayImportantName => getImportantDayFor(_selectedDay);

  /// Daftar Hari Penting Nasional & Sekolah
  static const Map<String, String> importantDates = {
    '01-01': 'Tahun Baru Masehi',
    '05-01': 'Hari Buruh Internasional',
    '05-02': 'Hari Pendidikan Nasional (Hardiknas)',
    '05-20': 'Hari Kebangkitan Nasional',
    '06-01': 'Hari Lahir Pancasila',
    '08-17': 'Hari Kemerdekaan RI',
    '10-01': 'Hari Kesaktian Pancasila',
    '10-28': 'Hari Sumpah Pemuda',
    '11-10': 'Hari Pahlawan Nasional',
    '11-25': 'Hari Guru Nasional',
    '12-25': 'Hari Raya Natal',
  };

  /// Mengembalikan nama hari penting jika ada pada tanggal yang diberikan
  static String? getImportantDayFor(DateTime day) {
    final key = '${day.month.toString().padLeft(2, '0')}-${day.day.toString().padLeft(2, '0')}';
    return importantDates[key];
  }

  /// Mengambil Daftar Agenda pada Tanggal Tertentu (untuk indikator warna & list)
  List<EventModel> getEventsForDay(DateTime day) {
    return _allMonthEvents.where((event) {
      if (!event.occursOn(day)) return false;

      // Filter Ruangan
      if (_selectedRoomId != null && event.room.id != _selectedRoomId) {
        return false;
      }

      // Filter Pencarian
      if (_searchQuery.isNotEmpty) {
        final matchTitle = event.eventName.toLowerCase().contains(_searchQuery);
        final matchRoom = event.room.name.toLowerCase().contains(_searchQuery);
        final matchOrganizer = event.organizer.fullname.toLowerCase().contains(_searchQuery);
        return matchTitle || matchRoom || matchOrganizer;
      }

      return true;
    }).toList();
  }
}

