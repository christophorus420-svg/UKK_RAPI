import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/image_compressor.dart';
import '../../data/models/room_model.dart';
import '../../providers/booking_provider.dart';
import '../../providers/complaint_provider.dart';
import '../../widgets/common_button.dart';
import '../../widgets/custom_text_field.dart';
import 'my_complaints_screen.dart';

/// Formulir Pengaduan Kerusakan Sarpras Sekolah + Kamera & Kompresi
class ComplaintFormScreen extends StatefulWidget {
  final RoomModel? initialRoom;

  const ComplaintFormScreen({super.key, this.initialRoom});

  @override
  State<ComplaintFormScreen> createState() => _ComplaintFormScreenState();
}

class _ComplaintFormScreenState extends State<ComplaintFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _descController = TextEditingController();
  RoomModel? _selectedRoom;

  @override
  void initState() {
    super.initState();
    _selectedRoom = widget.initialRoom;

    WidgetsBinding.instance.addPostFrameCallback((_) {
      final bookingProvider = context.read<BookingProvider>();
      if (bookingProvider.rooms.isEmpty) {
        bookingProvider.loadRooms().then((_) {
          if (mounted && _selectedRoom == null && bookingProvider.rooms.isNotEmpty) {
            setState(() {
              _selectedRoom = bookingProvider.rooms.first;
            });
          }
        });
      } else if (_selectedRoom == null) {
        setState(() {
          _selectedRoom = bookingProvider.rooms.first;
        });
      }
    });
  }

  @override
  void dispose() {
    _descController.dispose();
    super.dispose();
  }

  Future<void> _handleSubmit() async {
    if (!_formKey.currentState!.validate()) return;

    if (_selectedRoom == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Silakan tentukan ruangan fasilitas yang rusak.'),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    FocusScope.of(context).unfocus();
    final provider = context.read<ComplaintProvider>();

    final success = await provider.submitComplaint(
      roomId: _selectedRoom!.id,
      description: _descController.text,
    );

    if (!mounted) return;

    if (success) {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Row(
            children: const [
              Icon(Icons.check_circle_outline, color: AppColors.success, size: 28),
              SizedBox(width: 10),
              Text('Laporan Terkirim!'),
            ],
          ),
          content: const Text(
            'Laporan kerusakan berhasil diteruskan ke PIC Ruangan terkait untuk diverifikasi dan ditindaklanjuti.',
          ),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.pop(ctx);
                Navigator.pop(context);
              },
              child: const Text('Tutup'),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.pop(ctx);
                Navigator.pushReplacement(
                  context,
                  MaterialPageRoute(builder: (_) => const MyComplaintsScreen()),
                );
              },
              child: const Text('Lihat Riwayat Aduan'),
            ),
          ],
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
    final complaintProvider = context.watch<ComplaintProvider>();
    final bookingProvider = context.watch<BookingProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Lapor Kerusakan Sarpras'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 20.0, vertical: 16.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // 1. Pilih Ruangan Fasilitas
              const Text(
                '1. Lokasi Ruangan yang Bermasalah',
                style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.border, width: 1.2),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<RoomModel>(
                    value: _selectedRoom,
                    isExpanded: true,
                    hint: const Text('Pilih Ruangan Terkait'),
                    items: bookingProvider.rooms.map((room) {
                      return DropdownMenuItem<RoomModel>(
                        value: room,
                        child: Text(
                          '${room.roomName} (${room.location})',
                          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
                        ),
                      );
                    }).toList(),
                    onChanged: (room) {
                      setState(() => _selectedRoom = room);
                    },
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // 2. Deskripsi Kerusakan (Min 10 Karakter)
              const Text(
                '2. Deskripsi Kerusakan Fasilitas',
                style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 4),
              const Text(
                'Jelaskan kerusakan secara spesifik (minimal 10 karakter)',
                style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
              ),
              const SizedBox(height: 8),
              CustomTextField(
                controller: _descController,
                label: 'Rincian Kerusakan',
                hint: 'Cth: AC unit 2 bocor dan menetes ke stopkontak deret C.',
                prefixIcon: Icons.description_outlined,
                maxLines: 4,
                validator: (val) {
                  if (val == null || val.trim().isEmpty) {
                    return 'Deskripsi kerusakan wajib diisi';
                  }
                  if (val.trim().length < 10) {
                    return 'Deskripsi terlalu pendek (minimal 10 karakter)';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 24),

              // 3. Bukti Foto & Kompresi Otomatis
              const Text(
                '3. Foto Bukti Kerusakan (Opsional tapi Disarankan)',
                style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 8),

              // Tampilan Bila Foto Telah Dipilih
              if (complaintProvider.selectedPhoto != null) ...[
                Stack(
                  children: [
                    Container(
                      width: double.infinity,
                      height: 220,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.border),
                        image: DecorationImage(
                          image: FileImage(complaintProvider.selectedPhoto!),
                          fit: BoxFit.cover,
                        ),
                      ),
                    ),
                    Positioned(
                      top: 10,
                      right: 10,
                      child: CircleAvatar(
                        backgroundColor: Colors.black.withValues(alpha: 0.6),
                        radius: 18,
                        child: IconButton(
                          icon: const Icon(Icons.delete_outline, color: AppColors.textWhite, size: 18),
                          onPressed: () => complaintProvider.clearPhoto(),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),

                // Lencana Informasi Kompresi
                if (complaintProvider.compressionResult != null)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: AppColors.successContainer.withValues(alpha: 0.7),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: AppColors.success.withValues(alpha: 0.3)),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.speed_rounded, size: 16, color: AppColors.textSuccess),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Kompresi Sukses: '
                            '${CompressionResult.formatBytes(complaintProvider.compressionResult!.originalSizeBytes)} ➔ '
                            '${CompressionResult.formatBytes(complaintProvider.compressionResult!.compressedSizeBytes)} '
                            '(Hemat ${complaintProvider.compressionResult!.savingsPercentage})',
                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.textSuccess),
                          ),
                        ),
                      ],
                    ),
                  ),
              ] else ...[
                // Pilihan Sumber Gambar Kamera / Galeri
                Row(
                  children: [
                    Expanded(
                      child: InkWell(
                        onTap: () => complaintProvider.pickFromCamera(),
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          padding: const EdgeInsets.symmetric(vertical: 24),
                          decoration: BoxDecoration(
                            color: AppColors.primaryContainer.withValues(alpha: 0.4),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: AppColors.primaryLight.withValues(alpha: 0.4), style: BorderStyle.solid),
                          ),
                          child: Column(
                            children: const [
                              Icon(Icons.camera_alt_rounded, size: 32, color: AppColors.primary),
                              SizedBox(height: 8),
                              Text(
                                'Ambil Foto Kamera',
                                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.primary),
                              ),
                              SizedBox(height: 2),
                              Text(
                                'Kompresi Otomatis',
                                style: TextStyle(fontSize: 10, color: AppColors.textSecondary),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: InkWell(
                        onTap: () => complaintProvider.pickFromGallery(),
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          padding: const EdgeInsets.symmetric(vertical: 24),
                          decoration: BoxDecoration(
                            color: AppColors.surfaceVariant,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: AppColors.border),
                          ),
                          child: Column(
                            children: const [
                              Icon(Icons.photo_library_outlined, size: 32, color: AppColors.textSecondary),
                              SizedBox(height: 8),
                              Text(
                                'Pilih dari Galeri',
                                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.textPrimary),
                              ),
                              SizedBox(height: 2),
                              Text(
                                'JPG, PNG, WebP',
                                style: TextStyle(fontSize: 10, color: AppColors.textSecondary),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
              const SizedBox(height: 32),

              // Tombol Kirim Pengaduan
              CommonButton(
                text: 'Kirim Laporan Kerusakan',
                icon: Icons.send_rounded,
                isLoading: complaintProvider.isSubmitting,
                onPressed: _handleSubmit,
              ),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }
}

