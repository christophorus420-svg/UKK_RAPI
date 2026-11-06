import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../core/theme/app_colors.dart';
import '../providers/auth_provider.dart';
import 'auth/login_screen.dart';
import 'booking/my_bookings_screen.dart';
import 'calendar/public_calendar_screen.dart';
import 'complaint/my_complaints_screen.dart';
import 'dashboard/guru_dashboard_screen.dart';
import 'pic/pic_dashboard_screen.dart';
import 'profile/profile_screen.dart';
import 'rooms/room_catalog_screen.dart';

/// Kontainer Navigasi Utama Aplikasi Berdasarkan Role Pengguna
class MainNavigationScreen extends StatefulWidget {
  final int initialIndex;

  const MainNavigationScreen({super.key, this.initialIndex = 0});

  @override
  State<MainNavigationScreen> createState() => _MainNavigationScreenState();
}

class _MainNavigationScreenState extends State<MainNavigationScreen> {
  late int _currentIndex;

  @override
  void initState() {
    super.initState();
    _currentIndex = widget.initialIndex;
  }

  void _switchTab(int index) {
    setState(() {
      _currentIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final user = auth.user;

    // 1. Tampilan Pengguna Tamu / Belum Login
    if (user == null) {
      final guestPages = [
        const PublicCalendarScreen(),
        const RoomCatalogScreen(),
        LoginScreen(
          onLoginSuccess: () => _switchTab(0),
          onContinueAsGuest: () => _switchTab(0),
        ),
      ];

      return Scaffold(
        body: IndexedStack(
          index: _currentIndex > 2 ? 0 : _currentIndex,
          children: guestPages,
        ),
        bottomNavigationBar: BottomNavigationBar(
          currentIndex: _currentIndex > 2 ? 0 : _currentIndex,
          onTap: _switchTab,
          selectedItemColor: AppColors.primary,
          unselectedItemColor: AppColors.textSecondary,
          items: const [
            BottomNavigationBarItem(
              icon: Icon(Icons.calendar_month_outlined),
              activeIcon: Icon(Icons.calendar_month_rounded),
              label: 'Kalender',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.meeting_room_outlined),
              activeIcon: Icon(Icons.meeting_room_rounded),
              label: 'Katalog Ruang',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.login_rounded),
              label: 'Masuk Akun',
            ),
          ],
        ),
      );
    }

    // 2. Tampilan Khusus Role PIC Ruangan
    if (user.isPic) {
      final picPages = [
        const PicDashboardScreen(),
        const PublicCalendarScreen(),
        const RoomCatalogScreen(),
        const ProfileScreen(),
      ];

      return Scaffold(
        body: IndexedStack(
          index: _currentIndex > 3 ? 0 : _currentIndex,
          children: picPages,
        ),
        bottomNavigationBar: BottomNavigationBar(
          currentIndex: _currentIndex > 3 ? 0 : _currentIndex,
          onTap: _switchTab,
          selectedItemColor: const Color(0xFF0F766E), // Teal PIC
          unselectedItemColor: AppColors.textSecondary,
          type: BottomNavigationBarType.fixed,
          items: const [
            BottomNavigationBarItem(
              icon: Icon(Icons.support_agent_outlined),
              activeIcon: Icon(Icons.support_agent_rounded),
              label: 'Panel PIC',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.calendar_month_outlined),
              activeIcon: Icon(Icons.calendar_month_rounded),
              label: 'Kalender',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.meeting_room_outlined),
              activeIcon: Icon(Icons.meeting_room_rounded),
              label: 'Ruangan',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.person_outline_rounded),
              activeIcon: Icon(Icons.person_rounded),
              label: 'Profil',
            ),
          ],
        ),
      );
    }

    // 3. Tampilan Guru / Pembina Organisasi
    final guruPages = [
      GuruDashboardScreen(onSwitchTab: _switchTab),
      const PublicCalendarScreen(),
      const MyBookingsScreen(),
      const MyComplaintsScreen(),
      const ProfileScreen(),
    ];

    return Scaffold(
      body: IndexedStack(
        index: _currentIndex > 4 ? 0 : _currentIndex,
        children: guruPages,
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex > 4 ? 0 : _currentIndex,
        onTap: _switchTab,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: AppColors.textSecondary,
        type: BottomNavigationBarType.fixed,
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.dashboard_outlined),
            activeIcon: Icon(Icons.dashboard_rounded),
            label: 'Dashboard',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.calendar_month_outlined),
            activeIcon: Icon(Icons.calendar_month_rounded),
            label: 'Kalender',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.event_note_outlined),
            activeIcon: Icon(Icons.event_note_rounded),
            label: 'Peminjaman',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.build_circle_outlined),
            activeIcon: Icon(Icons.build_circle_rounded),
            label: 'Aduan',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.person_outline_rounded),
            activeIcon: Icon(Icons.person_rounded),
            label: 'Profil',
          ),
        ],
      ),
    );
  }
}
