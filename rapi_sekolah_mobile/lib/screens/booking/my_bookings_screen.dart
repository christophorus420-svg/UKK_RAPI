import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/date_formatter.dart';
import '../../data/models/event_model.dart';
import '../../providers/auth_provider.dart';
import '../../providers/booking_provider.dart';
import '../../widgets/empty_state_widget.dart';
import '../../widgets/loading_indicator.dart';
import 'booking_form_screen.dart';

/// Layar Riwayat Pengajuan Peminjaman Ruangan Milik Sendiri
class MyBookingsScreen extends StatefulWidget {
  const MyBookingsScreen({super.key});

  @override
  State<MyBookingsScreen> createState() => _MyBookingsScreenState();
}

class _MyBookingsScreenState extends State<MyBookingsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _loadBookings();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _loadBookings() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final user = context.read<AuthProvider>().user;
      if (user != null) {
        context.read<BookingProvider>().fetchMyBookings(user.id);
      }
    });
  }

  List<EventModel> _filterByTab(List<EventModel> all, int tabIndex) {
    switch (tabIndex) {
      case 1: // Disetujui
        return all.where((e) => e.isApproved).toList();
      case 2: // Menunggu
        return all.where((e) => e.isPending).toList();
      case 3: // Ditolak
        return all.where((e) => e.isRejected).toList();
      case 0: // Semua
      default:
        return all;
    }
  }

  @override
  Widget build(BuildContext context) {
    final bookingProvider = context.watch<BookingProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Riwayat Peminjaman Saya'),
        bottom: TabBar(
          controller: _tabController,
          labelColor: AppColors.primary,
          unselectedLabelColor: AppColors.textSecondary,
          indicatorColor: AppColors.primary,
          tabs: const [
            Tab(text: 'Semua'),
            Tab(text: 'Disetujui'),
            Tab(text: 'Menunggu'),
            Tab(text: 'Ditolak'),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const BookingFormScreen()),
          ).then((_) => _loadBookings());
        },
        icon: const Icon(Icons.add, color: AppColors.textWhite),
        label: const Text('Ajukan Lagi', style: TextStyle(color: AppColors.textWhite, fontWeight: FontWeight.bold)),
        backgroundColor: AppColors.primary,
      ),
      body: bookingProvider.isLoadingMyBookings
          ? const LoadingIndicator(message: 'Memuat riwayat peminjaman...')
          : TabBarView(
              controller: _tabController,
              children: [
                _buildList(_filterByTab(bookingProvider.myBookings, 0)),
                _buildList(_filterByTab(bookingProvider.myBookings, 1)),
                _buildList(_filterByTab(bookingProvider.myBookings, 2)),
                _buildList(_filterByTab(bookingProvider.myBookings, 3)),
              ],
            ),
    );
  }

  Widget _buildList(List<EventModel> items) {
    if (items.isEmpty) {
      return const EmptyStateWidget(
        icon: Icons.event_note_outlined,
        title: 'Belum Ada Peminjaman',
        message: 'Tidak ada riwayat pengajuan peminjaman ruangan pada kategori ini.',
      );
    }

    return RefreshIndicator(
      onRefresh: () async => _loadBookings(),
      child: ListView.builder(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        itemCount: items.length,
        itemBuilder: (ctx, index) {
          final event = items[index];
          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Judul & Badge Status
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(
                          event.eventName,
                          style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textPrimary,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppColors.getStatusContainerColor(event.status),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          event.isApproved
                              ? 'Disetujui'
                              : (event.isPending ? 'Menunggu Waka' : 'Ditolak'),
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: AppColors.getStatusColor(event.status),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),

                  // Waktu & Tanggal
                  Row(
                    children: [
                      const Icon(Icons.calendar_today_outlined, size: 14, color: AppColors.textSecondary),
                      const SizedBox(width: 6),
                      Text(
                        DateFormatter.formatShortDate(event.date),
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
                      ),
                      const SizedBox(width: 14),
                      const Icon(Icons.access_time_rounded, size: 14, color: AppColors.textSecondary),
                      const SizedBox(width: 6),
                      Text(
                        event.timeRange,
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),

                  // Ruangan
                  Row(
                    children: [
                      const Icon(Icons.meeting_room_outlined, size: 14, color: AppColors.textSecondary),
                      const SizedBox(width: 6),
                      Text(
                        '${event.room.name} (${event.room.location})',
                        style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                      ),
                    ],
                  ),

                  // Banner Alasan Penolakan oleh Waka (Jika status = rejected)
                  if (event.isRejected && event.rejectionReason != null) ...[
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: AppColors.errorContainer.withValues(alpha: 0.6),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Icon(Icons.cancel_outlined, color: AppColors.textError, size: 16),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Alasan Penolakan: "${event.rejectionReason}"',
                              style: const TextStyle(fontSize: 11, color: AppColors.textError, fontWeight: FontWeight.w500),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],

                  // Banner Notifikasi Pemindahan Ruangan oleh Waka (Jika dipindahkan)
                  if (event.isMoved) ...[
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: AppColors.warningContainer.withValues(alpha: 0.7),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Icon(Icons.info_outline, color: AppColors.textWarning, size: 16),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Dialihkan Waka ke: ${event.room.name}\nAlasan: "${event.moveReason}"',
                              style: const TextStyle(fontSize: 11, color: AppColors.textWarning, fontWeight: FontWeight.w500),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

