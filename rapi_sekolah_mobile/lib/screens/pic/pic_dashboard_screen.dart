import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/theme/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../../providers/pic_provider.dart';
import '../../widgets/empty_state_widget.dart';
import '../../widgets/error_state_widget.dart';
import '../../widgets/loading_indicator.dart';
import 'widgets/pic_complaint_card.dart';

/// Layar Utama Khusus PIC Penanggung Jawab Ruangan
class PicDashboardScreen extends StatefulWidget {
  const PicDashboardScreen({super.key});

  @override
  State<PicDashboardScreen> createState() => _PicDashboardScreenState();
}

class _PicDashboardScreenState extends State<PicDashboardScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<PicProvider>().fetchAssignedComplaints();
    });
  }

  Future<void> _handleUpdateStatus(int complaintId, String newStatus) async {
    final provider = context.read<PicProvider>();
    final success = await provider.updateStatus(
      complaintId: complaintId,
      newStatus: newStatus,
    );

    if (!mounted) return;

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(newStatus == 'Sudah Diperbaiki'
              ? 'Status perbaikan berhasil diubah ke: "Sudah Diperbaiki". Laporan akan otomatis terhapus setelah 24 jam.'
              : 'Status perbaikan berhasil diperbarui ke: "$newStatus"'),
          backgroundColor: AppColors.success,
        ),
      );
    } else if (provider.errorMessage != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(provider.errorMessage!),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  Future<void> _handleDeleteComplaint(int complaintId) async {
    final provider = context.read<PicProvider>();
    final success = await provider.deleteComplaint(complaintId);

    if (!mounted) return;

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Laporan pengaduan sarpras berhasil dihapus dari sistem.'),
          backgroundColor: AppColors.success,
        ),
      );
    } else if (provider.errorMessage != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(provider.errorMessage!),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final pic = context.watch<PicProvider>();
    final user = auth.user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Panel PIC Ruangan'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'Segarkan',
            onPressed: () => pic.fetchAssignedComplaints(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => pic.fetchAssignedComplaints(),
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.symmetric(horizontal: 18.0, vertical: 14.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // 1. Banner PIC Penanggung Jawab
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF0F766E), Color(0xFF14B8A6)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF0F766E).withValues(alpha: 0.25),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(Icons.support_agent_rounded, color: Colors.white, size: 28),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'PIC Ruang Sarpras',
                            style: TextStyle(fontSize: 12, color: Colors.white70),
                          ),
                          Text(
                            user?.fullname ?? 'Petugas PIC',
                            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 2),
                          const Text(
                            'Kelola siklus perbaikan fasilitas ruangan binaan Anda',
                            style: TextStyle(fontSize: 11, color: Colors.white),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 18),

              // 2. Tiga Kartu Statistik Siklus Perbaikan
              Row(
                children: [
                  Expanded(
                    child: _buildMetricCard(
                      title: 'Belum Diperbaiki',
                      count: '${pic.notRepairedCount}',
                      color: AppColors.error,
                      isActive: pic.selectedFilterStatus == RepairStatusOptions.belumDiperbaiki,
                      onTap: () {
                        pic.setFilterStatus(
                          pic.selectedFilterStatus == RepairStatusOptions.belumDiperbaiki
                              ? null
                              : RepairStatusOptions.belumDiperbaiki,
                        );
                      },
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: _buildMetricCard(
                      title: 'Spare Part',
                      count: '${pic.waitingPartCount}',
                      color: AppColors.warning,
                      isActive: pic.selectedFilterStatus == RepairStatusOptions.menungguSparePart,
                      onTap: () {
                        pic.setFilterStatus(
                          pic.selectedFilterStatus == RepairStatusOptions.menungguSparePart
                              ? null
                              : RepairStatusOptions.menungguSparePart,
                        );
                      },
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: _buildMetricCard(
                      title: 'Selesai',
                      count: '${pic.finishedCount}',
                      color: AppColors.success,
                      isActive: pic.selectedFilterStatus == RepairStatusOptions.sudahDiperbaiki,
                      onTap: () {
                        pic.setFilterStatus(
                          pic.selectedFilterStatus == RepairStatusOptions.sudahDiperbaiki
                              ? null
                              : RepairStatusOptions.sudahDiperbaiki,
                        );
                      },
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              // 3. Filter Status Chips Horizontal
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: [
                    ChoiceChip(
                      label: const Text('Semua Laporan'),
                      selected: pic.selectedFilterStatus == null,
                      onSelected: (_) => pic.setFilterStatus(null),
                    ),
                    const SizedBox(width: 8),
                    ChoiceChip(
                      label: const Text('Belum Diperbaiki'),
                      selected: pic.selectedFilterStatus == RepairStatusOptions.belumDiperbaiki,
                      onSelected: (_) => pic.setFilterStatus(RepairStatusOptions.belumDiperbaiki),
                    ),
                    const SizedBox(width: 8),
                    ChoiceChip(
                      label: const Text('Menunggu Spare Part'),
                      selected: pic.selectedFilterStatus == RepairStatusOptions.menungguSparePart,
                      onSelected: (_) => pic.setFilterStatus(RepairStatusOptions.menungguSparePart),
                    ),
                    const SizedBox(width: 8),
                    ChoiceChip(
                      label: const Text('Sudah Diperbaiki'),
                      selected: pic.selectedFilterStatus == RepairStatusOptions.sudahDiperbaiki,
                      onSelected: (_) => pic.setFilterStatus(RepairStatusOptions.sudahDiperbaiki),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),

              // 4. Daftar Tugas Pengaduan Khusus PIC
              if (pic.isLoading)
                const LoadingIndicator(message: 'Memuat tugas pengaduan PIC...')
              else if (pic.errorMessage != null && pic.assignedComplaints.isEmpty)
                ErrorStateWidget(
                  customMessage: pic.errorMessage,
                  onRetry: () => pic.fetchAssignedComplaints(),
                )
              else if (pic.filteredComplaints.isEmpty)
                const EmptyStateWidget(
                  icon: Icons.task_alt_rounded,
                  title: 'Tidak Ada Tugas Aduan',
                  message: 'Tidak ada laporan kerusakan sarpras pada ruangan binaan Anda di kategori ini.',
                )
              else
                ...pic.filteredComplaints.map(
                  (complaint) => PicComplaintCard(
                    complaint: complaint,
                    onUpdateStatus: (newStatus) => _handleUpdateStatus(complaint.id, newStatus),
                    onDelete: () => _handleDeleteComplaint(complaint.id),
                  ),
                ),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMetricCard({
    required String title,
    required String count,
    required Color color,
    required bool isActive,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
        decoration: BoxDecoration(
          color: isActive ? color.withValues(alpha: 0.12) : AppColors.surface,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isActive ? color : AppColors.border,
            width: isActive ? 2 : 1,
          ),
        ),
        child: Column(
          children: [
            Text(
              count,
              style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: color),
            ),
            const SizedBox(height: 4),
            Text(
              title,
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: isActive ? color : AppColors.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

