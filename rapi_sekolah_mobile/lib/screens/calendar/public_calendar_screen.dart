import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:table_calendar/table_calendar.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/date_formatter.dart';
import '../../providers/auth_provider.dart';
import '../../providers/calendar_provider.dart';
import '../../widgets/empty_state_widget.dart';
import '../../widgets/error_state_widget.dart';
import '../../widgets/loading_indicator.dart';
import '../auth/login_screen.dart';
import '../profile/profile_screen.dart';
import '../rooms/room_catalog_screen.dart';
import 'widgets/event_card.dart';

/// Layar Kalender Interaktif & Agenda Sekolah (Publik / Tamu)
class PublicCalendarScreen extends StatefulWidget {
  const PublicCalendarScreen({super.key});

  @override
  State<PublicCalendarScreen> createState() => _PublicCalendarScreenState();
}

class _PublicCalendarScreenState extends State<PublicCalendarScreen> {
  final TextEditingController _searchController = TextEditingController();
  bool _isSearchVisible = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CalendarProvider>().fetchInitialData();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final calendarProvider = context.watch<CalendarProvider>();
    final authProvider = context.watch<AuthProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Agenda Sekolah'),
        actions: [
          // Tombol Toggle Pencarian
          IconButton(
            icon: Icon(_isSearchVisible ? Icons.search_off : Icons.search),
            tooltip: 'Cari Agenda',
            onPressed: () {
              setState(() {
                _isSearchVisible = !_isSearchVisible;
                if (!_isSearchVisible) {
                  _searchController.clear();
                  calendarProvider.setSearchQuery('');
                }
              });
            },
          ),
          // Tombol Buka Katalog Ruangan
          IconButton(
            icon: const Icon(Icons.meeting_room_outlined),
            tooltip: 'Katalog Ruangan',
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const RoomCatalogScreen()),
              );
            },
          ),
          // Tombol Profil / Login
          if (authProvider.isAuthenticated)
            IconButton(
              icon: CircleAvatar(
                radius: 14,
                backgroundColor: AppColors.primaryContainer,
                child: Text(
                  authProvider.user?.fullname.isNotEmpty == true
                      ? authProvider.user!.fullname[0].toUpperCase()
                      : 'U',
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.primary),
                ),
              ),
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const ProfileScreen()),
                );
              },
            )
          else
            TextButton.icon(
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const LoginScreen()),
                );
              },
              icon: const Icon(Icons.login_rounded, size: 16),
              label: const Text('Masuk'),
            ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => calendarProvider.fetchInitialData(),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            // Kolom Pencarian (Bila aktif)
            if (_isSearchVisible)
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
                  child: TextField(
                    controller: _searchController,
                    autofocus: true,
                    onChanged: (val) => calendarProvider.setSearchQuery(val),
                    decoration: InputDecoration(
                      hintText: 'Cari agenda kegiatan atau guru...',
                      prefixIcon: const Icon(Icons.search, size: 20),
                      suffixIcon: _searchController.text.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear, size: 18),
                              onPressed: () {
                                _searchController.clear();
                                calendarProvider.setSearchQuery('');
                              },
                            )
                          : null,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    ),
                  ),
                ),
              ),

            // Kalender Bulanan Interaktif (TableCalendar dengan Warna Khusus Event & Hari Penting)
            SliverToBoxAdapter(
              child: Container(
                color: AppColors.surface,
                child: TableCalendar(
                  locale: 'id_ID',
                  firstDay: DateTime.utc(2025, 1, 1),
                  lastDay: DateTime.utc(2030, 12, 31),
                  focusedDay: calendarProvider.focusedDay,
                  selectedDayPredicate: (day) => isSameDay(calendarProvider.selectedDay, day),
                  onDaySelected: (selDay, focDay) {
                    calendarProvider.onDaySelected(selDay, focDay);
                  },
                  onPageChanged: (focDay) {
                    calendarProvider.onPageChanged(focDay);
                  },
                  eventLoader: (day) => calendarProvider.getEventsForDay(day),
                  calendarFormat: CalendarFormat.month,
                  availableCalendarFormats: const {
                    CalendarFormat.month: 'Bulan',
                  },
                  rowHeight: 44.0,
                  daysOfWeekHeight: 22.0,
                  calendarStyle: const CalendarStyle(
                    outsideDaysVisible: false,
                  ),
                  calendarBuilders: CalendarBuilders(
                    defaultBuilder: (context, day, focusedDay) {
                      return _buildDayCell(context, day, isSelected: false, isToday: false, provider: calendarProvider);
                    },
                    todayBuilder: (context, day, focusedDay) {
                      return _buildDayCell(context, day, isSelected: false, isToday: true, provider: calendarProvider);
                    },
                    selectedBuilder: (context, day, focusedDay) {
                      return _buildDayCell(context, day, isSelected: true, isToday: isSameDay(day, DateTime.now()), provider: calendarProvider);
                    },
                    outsideBuilder: (context, day, focusedDay) {
                      return const SizedBox.shrink();
                    },
                    markerBuilder: (context, day, events) {
                      return const SizedBox.shrink(); // Ditangani langsung di dalam _buildDayCell
                    },
                  ),
                  headerStyle: const HeaderStyle(
                    titleCentered: true,
                    formatButtonVisible: false,
                    titleTextStyle: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            ),
            const SliverToBoxAdapter(
              child: Divider(height: 1),
            ),

            // Bilah Legenda Keterangan Warna Kalender
            SliverToBoxAdapter(
              child: _buildColorLegend(),
            ),

            // Bilah Filter Ruangan (Chips Horizontal)
            SliverToBoxAdapter(
              child: _buildRoomFilterChips(calendarProvider),
            ),

            // Banner Keterangan Jika Tanggal Terpilih Merupakan Hari Penting
            if (calendarProvider.selectedDayImportantName != null)
              SliverToBoxAdapter(
                child: Container(
                  margin: const EdgeInsets.fromLTRB(16, 2, 16, 6),
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFF1F2),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFFDA4AF)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.bookmark_rounded, size: 16, color: Color(0xFFE11D48)),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Hari Penting: ${calendarProvider.selectedDayImportantName}',
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF9F1239),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),

            // Header Daftar Agenda Tanggal Terpilih
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 6),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        DateFormatter.formatFullDate(calendarProvider.selectedDay),
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: AppColors.textPrimary,
                        ),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: AppColors.surfaceVariant,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        '${calendarProvider.selectedDayEvents.length} Agenda',
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.textSecondary),
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // Sliver Daftar Agenda Kegiatan
            _buildEventListSliver(calendarProvider),

            // Jarak aman dasar bawah agar tidak tertutup bottom bar
            const SliverToBoxAdapter(
              child: SizedBox(height: 24),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRoomFilterChips(CalendarProvider provider) {
    if (provider.rooms.isEmpty) return const SizedBox.shrink();

    return SizedBox(
      height: 48,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        itemCount: provider.rooms.length + 1,
        itemBuilder: (ctx, index) {
          if (index == 0) {
            final isSelected = provider.selectedRoomId == null;
            return Padding(
              padding: const EdgeInsets.only(right: 8.0),
              child: ChoiceChip(
                label: const Text('Semua Ruangan'),
                selected: isSelected,
                onSelected: (_) => provider.setRoomFilter(null),
              ),
            );
          }

          final room = provider.rooms[index - 1];
          final isSelected = provider.selectedRoomId == room.id;

          return Padding(
            padding: const EdgeInsets.only(right: 8.0),
            child: ChoiceChip(
              label: Text(room.roomName),
              selected: isSelected,
              onSelected: (_) => provider.setRoomFilter(room.id),
            ),
          );
        },
      ),
    );
  }

  Widget _buildEventListSliver(CalendarProvider provider) {
    if (provider.isLoading && provider.rooms.isEmpty) {
      return const SliverToBoxAdapter(
        child: Padding(
          padding: EdgeInsets.symmetric(vertical: 36),
          child: LoadingIndicator(message: 'Memuat agenda...'),
        ),
      );
    }

    if (provider.errorMessage != null && provider.rooms.isEmpty) {
      return SliverToBoxAdapter(
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 36),
          child: ErrorStateWidget(
            customMessage: provider.errorMessage,
            onRetry: () => provider.fetchInitialData(),
          ),
        ),
      );
    }

    final events = provider.selectedDayEvents;

    if (events.isEmpty) {
      return const SliverToBoxAdapter(
        child: Padding(
          padding: EdgeInsets.symmetric(vertical: 24),
          child: EmptyStateWidget(
            icon: Icons.event_available_rounded,
            title: 'Tidak Ada Agenda',
            message: 'Tidak ada peminjaman atau kegiatan resmi pada tanggal dan ruangan yang dipilih.',
          ),
        ),
      );
    }

    return SliverPadding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      sliver: SliverList(
        delegate: SliverChildBuilderDelegate(
          (ctx, index) => EventCard(event: events[index]),
          childCount: events.length,
        ),
      ),
    );
  }

  /// Widget Render Kotak Tanggal Khusus dengan Warna Event & Hari Penting
  Widget _buildDayCell(
    BuildContext context,
    DateTime day, {
    required bool isSelected,
    required bool isToday,
    required CalendarProvider provider,
  }) {
    final events = provider.getEventsForDay(day);
    final eventCount = events.length;
    final holidayName = CalendarProvider.getImportantDayFor(day);
    final isSunday = day.weekday == DateTime.sunday;

    Color backgroundColor;
    Color borderColor;
    Color textColor;
    bool hasIndicator = false;
    Color indicatorColor = Colors.transparent;

    if (isSelected) {
      if (eventCount >= 2) {
        backgroundColor = const Color(0xFFFEF3C7); // amber-100
        borderColor = const Color(0xFFD97706); // amber-600
        textColor = const Color(0xFF92400E);
        hasIndicator = true;
        indicatorColor = const Color(0xFFD97706);
      } else if (eventCount == 1) {
        backgroundColor = const Color(0xFFE0E7FF); // indigo-100
        borderColor = const Color(0xFF4F46E5); // indigo-600
        textColor = const Color(0xFF3730A3);
        hasIndicator = true;
        indicatorColor = const Color(0xFF4F46E5);
      } else if (holidayName != null) {
        backgroundColor = const Color(0xFFFFE4E6); // rose-100
        borderColor = const Color(0xFFE11D48); // rose-600
        textColor = const Color(0xFF9F1239);
        hasIndicator = true;
        indicatorColor = const Color(0xFFE11D48);
      } else {
        backgroundColor = AppColors.primary;
        borderColor = AppColors.primary;
        textColor = Colors.white;
      }
    } else if (isToday) {
      backgroundColor = const Color(0xFFEFF6FF); // blue-50
      borderColor = const Color(0xFF2563EB); // blue-600
      textColor = const Color(0xFF1D4ED8);
      if (eventCount > 0) {
        hasIndicator = true;
        indicatorColor = eventCount >= 2 ? const Color(0xFFD97706) : const Color(0xFF4F46E5);
      } else if (holidayName != null) {
        hasIndicator = true;
        indicatorColor = const Color(0xFFE11D48);
      }
    } else if (holidayName != null) {
      backgroundColor = const Color(0xFFFFF1F2); // rose-50
      borderColor = const Color(0xFFFDA4AF); // rose-300
      textColor = const Color(0xFFBE123C); // rose-700
      if (eventCount > 0) {
        hasIndicator = true;
        indicatorColor = eventCount >= 2 ? const Color(0xFFD97706) : const Color(0xFF4F46E5);
      }
    } else if (eventCount >= 2) {
      backgroundColor = const Color(0xFFFFFBEB); // amber-50
      borderColor = const Color(0xFFFCD34D); // amber-300
      textColor = const Color(0xFFB45309); // amber-700
      hasIndicator = true;
      indicatorColor = const Color(0xFFD97706);
    } else if (eventCount == 1) {
      backgroundColor = const Color(0xFFEEF2FF); // indigo-50
      borderColor = const Color(0xFFA5B4FC); // indigo-300
      textColor = const Color(0xFF4338CA); // indigo-700
      hasIndicator = true;
      indicatorColor = const Color(0xFF4F46E5);
    } else {
      backgroundColor = Colors.transparent;
      borderColor = Colors.transparent;
      textColor = isSunday ? const Color(0xFFE11D48) : AppColors.textPrimary;
    }

    return Container(
      margin: const EdgeInsets.all(1.5),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
          color: borderColor,
          width: isSelected ? 2.0 : (borderColor == Colors.transparent ? 0 : 1.0),
        ),
      ),
      child: Stack(
        alignment: Alignment.center,
        children: [
          Text(
            '${day.day}',
            style: TextStyle(
              fontSize: 13,
              fontWeight: (isSelected || isToday || eventCount > 0 || holidayName != null)
                  ? FontWeight.bold
                  : FontWeight.normal,
              color: textColor,
            ),
          ),
          if (holidayName != null && !isSelected)
            Positioned(
              top: 2,
              right: 2,
              child: Container(
                width: 5,
                height: 5,
                decoration: const BoxDecoration(
                  color: Color(0xFFE11D48),
                  shape: BoxShape.circle,
                ),
              ),
            ),
          if (hasIndicator && !isSelected)
            Positioned(
              bottom: 3,
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 5,
                    height: 5,
                    decoration: BoxDecoration(
                      color: indicatorColor,
                      shape: BoxShape.circle,
                    ),
                  ),
                  if (eventCount >= 2) ...[
                    const SizedBox(width: 2),
                    Container(
                      width: 5,
                      height: 5,
                      decoration: BoxDecoration(
                        color: indicatorColor,
                        shape: BoxShape.circle,
                      ),
                    ),
                  ],
                ],
              ),
            ),
        ],
      ),
    );
  }

  /// Bilah Legenda Keterangan Warna Kalender
  Widget _buildColorLegend() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.palette_outlined, size: 13, color: AppColors.primary),
              SizedBox(width: 5),
              Text(
                'Legenda Warna Kalender & Hari Penting:',
                style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
            ],
          ),
          const SizedBox(height: 6),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _buildLegendItem(
                  color: const Color(0xFFEFF6FF),
                  borderColor: const Color(0xFF2563EB),
                  label: 'Hari Ini',
                  textColor: const Color(0xFF1D4ED8),
                ),
                const SizedBox(width: 12),
                _buildLegendItem(
                  color: const Color(0xFFEEF2FF),
                  borderColor: const Color(0xFFA5B4FC),
                  label: '1 Agenda',
                  textColor: const Color(0xFF4338CA),
                ),
                const SizedBox(width: 12),
                _buildLegendItem(
                  color: const Color(0xFFFFFBEB),
                  borderColor: const Color(0xFFFCD34D),
                  label: 'Padat (≥2)',
                  textColor: const Color(0xFFB45309),
                ),
                const SizedBox(width: 12),
                _buildLegendItem(
                  color: const Color(0xFFFFF1F2),
                  borderColor: const Color(0xFFFDA4AF),
                  label: 'Hari Penting / Libur',
                  textColor: const Color(0xFFBE123C),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLegendItem({
    required Color color,
    required Color borderColor,
    required String label,
    required Color textColor,
  }) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 12,
          height: 12,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(3),
            border: Border.all(color: borderColor, width: 1.2),
          ),
        ),
        const SizedBox(width: 4),
        Text(
          label,
          style: TextStyle(
            fontSize: 10.5,
            fontWeight: FontWeight.w600,
            color: textColor,
          ),
        ),
      ],
    );
  }
}

