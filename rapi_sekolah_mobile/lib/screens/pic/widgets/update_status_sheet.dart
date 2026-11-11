import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import '../../../data/models/complaint_model.dart';
import '../../../providers/pic_provider.dart';
import '../../../widgets/common_button.dart';

/// Modal Lembar Bawah Pembaruan Siklus Status Perbaikan (Khusus PIC)
class UpdateStatusSheet extends StatefulWidget {
  final ComplaintModel complaint;
  final Function(String newStatus) onStatusUpdated;

  const UpdateStatusSheet({
    super.key,
    required this.complaint,
    required this.onStatusUpdated,
  });

  static void show({
    required BuildContext context,
    required ComplaintModel complaint,
    required Function(String newStatus) onStatusUpdated,
  }) {
    if (complaint.isFinished) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Pengaduan yang sudah diperbaiki tidak dapat diubah statusnya.'),
          backgroundColor: AppColors.warning,
        ),
      );
      return;
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => UpdateStatusSheet(
        complaint: complaint,
        onStatusUpdated: onStatusUpdated,
      ),
    );
  }

  @override
  State<UpdateStatusSheet> createState() => _UpdateStatusSheetState();
}

class _UpdateStatusSheetState extends State<UpdateStatusSheet> {
  late String _selectedStatus;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _selectedStatus = widget.complaint.statusRepair;
  }

  Future<void> _handleSave() async {
    if (widget.complaint.isFinished) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Pengaduan yang sudah diperbaiki tidak dapat diubah statusnya.'),
          backgroundColor: AppColors.error,
        ),
      );
      Navigator.pop(context);
      return;
    }

    if (_selectedStatus == widget.complaint.statusRepair) {
      Navigator.pop(context);
      return;
    }

    setState(() => _isLoading = true);
    await widget.onStatusUpdated(_selectedStatus);
    if (mounted) {
      setState(() => _isLoading = false);
      Navigator.pop(context);
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Container(
        decoration: const BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: ConstrainedBox(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.of(context).size.height * 0.85,
          ),
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Handle Bar
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    margin: const EdgeInsets.only(bottom: 16),
                    decoration: BoxDecoration(
                      color: AppColors.border,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),

                const Text(
                  'Ubah Status Siklus Perbaikan',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Laporan: "${widget.complaint.description}" (${widget.complaint.room.name})',
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                ),
                const SizedBox(height: 18),

                // 3 Pilihan Status Baku
                _buildStatusOption(
                  status: RepairStatusOptions.belumDiperbaiki,
                  title: 'Belum Diperbaiki',
                  subtitle: 'Kerusakan baru dilaporkan atau belum ada tindakan perbaikan.',
                  icon: Icons.cancel_outlined,
                  color: AppColors.error,
                ),
                const SizedBox(height: 10),

                _buildStatusOption(
                  status: RepairStatusOptions.menungguSparePart,
                  title: 'Menunggu Spare Part',
                  subtitle: 'Sedang menunggu pengadaan suku cadang / alat pengganti.',
                  icon: Icons.hourglass_top_rounded,
                  color: AppColors.warning,
                ),
                const SizedBox(height: 10),

                _buildStatusOption(
                  status: RepairStatusOptions.sudahDiperbaiki,
                  title: 'Sudah Diperbaiki',
                  subtitle: 'Fasilitas telah normal. Pengaduan akan otomatis dihapus oleh sistem setelah 24 jam.',
                  icon: Icons.check_circle_outline_rounded,
                  color: AppColors.success,
                ),
                const SizedBox(height: 24),

                // Tombol Simpan
                CommonButton(
                  text: 'Simpan Perubahan Status',
                  icon: Icons.save_outlined,
                  isLoading: _isLoading,
                  onPressed: _handleSave,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStatusOption({
    required String status,
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
  }) {
    final isSelected = _selectedStatus == status;

    return InkWell(
      onTap: () {
        setState(() => _selectedStatus = status);
      },
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isSelected ? color.withValues(alpha: 0.08) : AppColors.surface,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected ? color : AppColors.border,
            width: isSelected ? 1.8 : 1.0,
          ),
        ),
        child: Row(
          children: [
            Icon(icon, color: color, size: 24),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      color: isSelected ? color : AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    subtitle,
                    style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
                  ),
                ],
              ),
            ),
            Radio<String>(
              value: status,
              groupValue: _selectedStatus,
              activeColor: color,
              onChanged: (val) {
                if (val != null) setState(() => _selectedStatus = val);
              },
            ),
          ],
        ),
      ),
    );
  }
}

