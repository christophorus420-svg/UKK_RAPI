import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/theme/app_colors.dart';
import '../../data/models/complaint_model.dart';
import '../../providers/complaint_provider.dart';
import '../../widgets/empty_state_widget.dart';
import '../../widgets/loading_indicator.dart';
import 'complaint_form_screen.dart';

/// Layar Riwayat Laporan Pengaduan Kerusakan Sarpras Milik Sendiri
class MyComplaintsScreen extends StatefulWidget {
  const MyComplaintsScreen({super.key});

  @override
  State<MyComplaintsScreen> createState() => _MyComplaintsScreenState();
}

class _MyComplaintsScreenState extends State<MyComplaintsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ComplaintProvider>().fetchComplaints();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  List<ComplaintModel> _filterByTab(List<ComplaintModel> all, int index) {
    switch (index) {
      case 1: // Belum Diperbaiki
        return all.where((c) => c.isNotRepaired).toList();
      case 2: // Menunggu Spare Part
        return all.where((c) => c.isWaitingPart).toList();
      case 3: // Sudah Diperbaiki
        return all.where((c) => c.isFinished).toList();
      case 0: // Semua
      default:
        return all;
    }
  }

  void _showImageDialog(BuildContext context, String imageUrl) {
    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(16),
        child: Stack(
          alignment: Alignment.topRight,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: CachedNetworkImage(
                imageUrl: imageUrl,
                fit: BoxFit.contain,
                placeholder: (_, __) => const Center(
                  child: CircularProgressIndicator(),
                ),
                errorWidget: (_, __, ___) => Container(
                  color: AppColors.surface,
                  padding: const EdgeInsets.all(24),
                  child: const Text('Gagal memuat gambar foto bukti.'),
                ),
              ),
            ),
            IconButton(
              icon: const CircleAvatar(
                backgroundColor: Colors.black54,
                child: Icon(Icons.close, color: Colors.white, size: 20),
              ),
              onPressed: () => Navigator.pop(ctx),
            ),
          ],
        ),
      ),
    );
  }

  void _confirmDelete(BuildContext context, ComplaintModel complaint) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: const [
            Icon(Icons.warning_amber_rounded, color: AppColors.error, size: 26),
            SizedBox(width: 10),
            Text('Hapus Pengaduan?'),
          ],
        ),
        content: const Text(
          'PERINGATAN: Apakah Anda yakin ingin menghapus data laporan pengaduan ini secara manual?\n\nTindakan ini bersifat permanen dan data laporan kerusakan beserta bukti fisik tidak dapat dipulihkan.',
          style: TextStyle(fontSize: 13, height: 1.4),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
            onPressed: () async {
              Navigator.pop(ctx);
              final provider = context.read<ComplaintProvider>();
              final success = await provider.deleteComplaint(complaint.id);
              if (context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(success ? 'Laporan pengaduan berhasil dihapus.' : (provider.errorMessage ?? 'Gagal menghapus pengaduan.')),
                    backgroundColor: success ? AppColors.success : AppColors.error,
                  ),
                );
              }
            },
            child: const Text('Ya, Hapus'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<ComplaintProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Pengaduan Sarpras Saya'),
        bottom: TabBar(
          controller: _tabController,
          labelColor: AppColors.primary,
          unselectedLabelColor: AppColors.textSecondary,
          indicatorColor: AppColors.primary,
          isScrollable: true,
          tabs: const [
            Tab(text: 'Semua'),
            Tab(text: 'Belum Diperbaiki'),
            Tab(text: 'Menunggu Spare Part'),
            Tab(text: 'Sudah Diperbaiki'),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const ComplaintFormScreen()),
          ).then((_) => context.read<ComplaintProvider>().fetchComplaints());
        },
        icon: const Icon(Icons.add_a_photo_outlined, color: AppColors.textWhite),
        label: const Text('Lapor Kerusakan', style: TextStyle(color: AppColors.textWhite, fontWeight: FontWeight.bold)),
        backgroundColor: AppColors.primary,
      ),
      body: provider.isLoading
          ? const LoadingIndicator(message: 'Memuat data pengaduan...')
          : TabBarView(
              controller: _tabController,
              children: [
                _buildList(_filterByTab(provider.complaints, 0)),
                _buildList(_filterByTab(provider.complaints, 1)),
                _buildList(_filterByTab(provider.complaints, 2)),
                _buildList(_filterByTab(provider.complaints, 3)),
              ],
            ),
    );
  }

  Widget _buildList(List<ComplaintModel> items) {
    if (items.isEmpty) {
      return const EmptyStateWidget(
        icon: Icons.build_circle_outlined,
        title: 'Tidak Ada Laporan',
        message: 'Belum ada pengaduan kerusakan sarpras pada kategori status ini.',
      );
    }

    return RefreshIndicator(
      onRefresh: () => context.read<ComplaintProvider>().fetchComplaints(),
      child: ListView.builder(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        itemCount: items.length,
        itemBuilder: (ctx, index) {
          final complaint = items[index];

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Lokasi Ruangan & Status Siklus Perbaikan
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: AppColors.primaryContainer.withValues(alpha: 0.5),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: const Icon(Icons.meeting_room_outlined, size: 20, color: AppColors.primary),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              complaint.room.name,
                              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                            ),
                            Text(
                              complaint.room.location,
                              style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                            ),
                          ],
                        ),
                      ),
                      // Badge Status Perbaikan & Tombol Hapus
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: AppColors.getStatusContainerColor(complaint.statusRepair),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Text(
                              complaint.statusRepair,
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: AppColors.getStatusColor(complaint.statusRepair),
                              ),
                            ),
                          ),
                          const SizedBox(width: 4),
                          IconButton(
                            icon: const Icon(Icons.delete_outline_rounded, size: 18, color: AppColors.error),
                            tooltip: 'Hapus Laporan',
                            constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                            padding: const EdgeInsets.all(4),
                            onPressed: () => _confirmDelete(context, complaint),
                          ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Deskripsi Kerusakan
                  Text(
                    complaint.description,
                    style: const TextStyle(fontSize: 13, height: 1.4, color: AppColors.textPrimary),
                  ),

                  // Banner Notifikasi Otomatis Terhapus Sistem 24 Jam
                  if (complaint.isFinished) ...[
                    const SizedBox(height: 10),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9), // slate-100
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: const Color(0xFFCBD5E1)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.timer_outlined, size: 14, color: Color(0xFF475569)),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              'Otomatis terhapus sistem dalam: ${complaint.autoDeleteRemaining ?? "24 jam"}',
                              style: const TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                                color: Color(0xFF334155),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],

                  // Thumbnail Foto Bukti (Jika Ada)
                  if (complaint.hasPhoto) ...[
                    const SizedBox(height: 12),
                    InkWell(
                      onTap: () => _showImageDialog(context, complaint.photoUrl!),
                      borderRadius: BorderRadius.circular(10),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(10),
                        child: Stack(
                          alignment: Alignment.center,
                          children: [
                            CachedNetworkImage(
                              imageUrl: complaint.photoUrl!,
                              height: 120,
                              width: double.infinity,
                              fit: BoxFit.cover,
                              placeholder: (_, __) => Container(
                                height: 120,
                                color: AppColors.surfaceVariant,
                                child: const Center(child: CircularProgressIndicator(strokeWidth: 2)),
                              ),
                              errorWidget: (_, __, ___) => Container(
                                height: 120,
                                color: AppColors.surfaceVariant,
                                child: const Center(
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(Icons.image_not_supported_outlined, color: AppColors.textMuted, size: 28),
                                      SizedBox(height: 4),
                                      Text('Foto tidak dapat dimuat', style: TextStyle(fontSize: 11, color: AppColors.textMuted)),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                              decoration: BoxDecoration(
                                color: Colors.black.withValues(alpha: 0.5),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: const [
                                  Icon(Icons.zoom_in, color: Colors.white, size: 16),
                                  SizedBox(width: 4),
                                  Text(
                                    'Ketuk untuk Memperbesar Foto',
                                    style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ] else ...[
                    const SizedBox(height: 10),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                      decoration: BoxDecoration(
                        color: AppColors.surfaceVariant.withValues(alpha: 0.5),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: AppColors.border.withValues(alpha: 0.5)),
                      ),
                      child: Row(
                        children: const [
                          Icon(Icons.no_photography_outlined, size: 16, color: AppColors.textMuted),
                          SizedBox(width: 6),
                          Text(
                            'Laporan ini dikirim tanpa lampiran foto bukti.',
                            style: TextStyle(fontSize: 11, color: AppColors.textSecondary),
                          ),
                        ],
                      ),
                    ),
                  ],
                  const Divider(height: 24),

                  // Info Tanggal & Staf PIC Ruangan
                  Row(
                    children: [
                      const Icon(Icons.access_time_rounded, size: 14, color: AppColors.textSecondary),
                      const SizedBox(width: 4),
                      Text(
                        complaint.formattedDate,
                        style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
                      ),
                      const Spacer(),
                      Text(
                        'PIC: ${complaint.room.picName ?? "Petugas Sarpras"}',
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.primary),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

