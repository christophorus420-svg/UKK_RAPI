import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/date_formatter.dart';
import '../../providers/auth_provider.dart';
import '../../providers/dashboard_provider.dart';
import '../../widgets/empty_state_widget.dart';
import '../../widgets/loading_indicator.dart';
import '../booking/booking_form_screen.dart';
import '../booking/my_bookings_screen.dart';
import '../calendar/widgets/event_card.dart';
import '../complaint/complaint_form_screen.dart';
import '../complaint/my_complaints_screen.dart';
import '../rooms/room_catalog_screen.dart';
import 'widgets/moved_room_alert_dialog.dart';

/// Layar Utama Dashboard Guru / Pembina Organisasi
class GuruDashboardScreen extends StatefulWidget {
  final Function(int)? onSwitchTab;

  const GuruDashboardScreen({super.key, this.onSwitchTab});

  @override
  State<GuruDashboardScreen> createState() => _GuruDashboardScreenState();
}

class _GuruDashboardScreenState extends State<GuruDashboardScreen> {
  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  void _loadDashboard() {
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final user = context.read<AuthProvider>().user;
      if (user != null) {
        final dashboard = context.read<DashboardProvider>();
        await dashboard.fetchDashboardData(user);

        // Jika ada agenda yang dipindahkan oleh Waka & belum pernah dilihat, picu pop-up
        if (mounted && dashboard.pendingMovedNotification != null) {
          final movedEvent = dashboard.pendingMovedNotification!;
          MovedRoomAlertDialog.show(
            context,
            movedEvent,
            () => dashboard.acknowledgeMovedNotification(movedEvent.id),
          );
        }
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final dashboard = context.watch<DashboardProvider>();
    final user = auth.user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard RAPI-Sekolah'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'Segarkan',
            onPressed: _loadDashboard,
          ),
        ],
      ),
      body: dashboard.isLoading
          ? const LoadingIndicator(message: 'Memuat data dashboard...')
          : RefreshIndicator(
              onRefresh: () async => _loadDashboard(),
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.symmetric(horizontal: 18.0, vertical: 14.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // 1. Kartu Sambutan Guru
                    Container(
                      padding: const EdgeInsets.all(18),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [AppColors.primary, AppColors.primaryLight],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.primary.withValues(alpha: 0.25),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: Colors.white.withValues(alpha: 0.2),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: const Icon(Icons.school, color: Colors.white, size: 24),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      'Selamat Datang,',
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Colors.white.withValues(alpha: 0.8),
                                      ),
                                    ),
                                    Text(
                                      user?.fullname ?? 'Bapak/Ibu Guru',
                                      style: const TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.bold,
                                        color: Colors.white,
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 14),
                          Text(
                            DateFormatter.formatFullDate(DateTime.now()),
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.white.withValues(alpha: 0.9),
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),

                    // 2. Banner Notifikasi Pemindahan Ruangan oleh Waka (Jika Ada)
                    if (dashboard.movedEvents.isNotEmpty) ...[
                      InkWell(
                        onTap: () {
                          final event = dashboard.movedEvents.first;
                          MovedRoomAlertDialog.show(
                            context,
                            event,
                            () => dashboard.acknowledgeMovedNotification(event.id),
                          );
                        },
                        borderRadius: BorderRadius.circular(14),
                        child: Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: AppColors.warningContainer,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: AppColors.warning),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.warning_amber_rounded, color: AppColors.textWarning, size: 26),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Text(
                                      'Perhatian: Ruangan Dipindahkan Waka!',
                                      style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppColors.textWarning),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      'Ada ${dashboard.movedEvents.length} agenda Anda dialihkan ke ruangan lain. Ketuk untuk rincian.',
                                      style: const TextStyle(fontSize: 11, color: AppColors.textPrimary),
                                    ),
                                  ],
                                ),
                              ),
                              const Icon(Icons.chevron_right_rounded, color: AppColors.textWarning),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],

                    // 3. Ringkasan Statistik Cepat
                    Row(
                      children: [
                        Expanded(
                          child: _buildStatCard(
                            title: 'Peminjaman Menunggu',
                            count: '${dashboard.pendingBookingsCount}',
                            icon: Icons.hourglass_top_rounded,
                            color: AppColors.warning,
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(builder: (_) => const MyBookingsScreen()),
                              );
                            },
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _buildStatCard(
                            title: 'Aduan Ditangani',
                            count: '${dashboard.activeComplaintsCount}',
                            icon: Icons.build_circle_outlined,
                            color: AppColors.secondary,
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(builder: (_) => const MyComplaintsScreen()),
                              );
                            },
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),

                    // 4. Menu Aksi Cepat (Quick Actions)
                    const Text(
                      'Layanan & Aksi Cepat',
                      style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: _buildActionCard(
                            title: 'Pinjam Ruang',
                            subtitle: 'Pilih sesi jam',
                            icon: Icons.add_to_photos_rounded,
                            color: AppColors.primary,
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(builder: (_) => const BookingFormScreen()),
                              ).then((_) => _loadDashboard());
                            },
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _buildActionCard(
                            title: 'Lapor Sarpras',
                            subtitle: 'Kamera & kompres',
                            icon: Icons.camera_alt_outlined,
                            color: AppColors.secondary,
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(builder: (_) => const ComplaintFormScreen()),
                              ).then((_) => _loadDashboard());
                            },
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: _buildActionCard(
                            title: 'Katalog Ruangan',
                            subtitle: 'Fasilitas & PIC',
                            icon: Icons.meeting_room_outlined,
                            color: const Color(0xFF6366F1),
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(builder: (_) => const RoomCatalogScreen()),
                              );
                            },
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _buildActionCard(
                            title: 'Kalender Bulanan',
                            subtitle: 'Jadwal sekolah',
                            icon: Icons.calendar_month_outlined,
                            color: const Color(0xFF0EA5E9),
                            onTap: () => widget.onSwitchTab?.call(1),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 28),

                    // 5. Agenda Sekolah Hari Ini
                    Row(
                      children: [
                        const Expanded(
                          child: Text(
                            'Agenda Sekolah Hari Ini',
                            style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                          ),
                        ),
                        TextButton(
                          onPressed: () => widget.onSwitchTab?.call(1),
                          child: const Text('Lihat Kalender', style: TextStyle(fontSize: 12)),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),

                    if (dashboard.todayEvents.isEmpty)
                      const EmptyStateWidget(
                        icon: Icons.event_available,
                        title: 'Tidak Ada Agenda Hari Ini',
                        message: 'Ruangan sekolah bebas dari kegiatan terjadwal hari ini.',
                      )
                    else
                      ...dashboard.todayEvents.map((e) => EventCard(event: e)),
                    const SizedBox(height: 24),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildStatCard({
    required String title,
    required String count,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.border),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, size: 20, color: color),
                const Spacer(),
                Text(
                  count,
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: color),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Text(
              title,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActionCard({
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: color, size: 22),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                  ),
                  Text(
                    subtitle,
                    style: const TextStyle(fontSize: 10, color: AppColors.textSecondary),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

