import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../data/models/room_model.dart';
import '../../data/repositories/room_repository.dart';
import '../../widgets/empty_state_widget.dart';
import '../../widgets/error_state_widget.dart';
import '../../widgets/loading_indicator.dart';

/// Layar Katalog Ruangan Sekolah & Fasilitas (Publik)
class RoomCatalogScreen extends StatefulWidget {
  const RoomCatalogScreen({super.key});

  @override
  State<RoomCatalogScreen> createState() => _RoomCatalogScreenState();
}

class _RoomCatalogScreenState extends State<RoomCatalogScreen> {
  final RoomRepository _roomRepository = RoomRepository();
  List<RoomModel> _rooms = [];
  List<RoomModel> _filteredRooms = [];
  bool _isLoading = true;
  String? _errorMessage;
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchRooms();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _fetchRooms() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final list = await _roomRepository.getRooms();
      setState(() {
        _rooms = list;
        _filteredRooms = list;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  void _filterRooms(String query) {
    final q = query.toLowerCase().trim();
    setState(() {
      if (q.isEmpty) {
        _filteredRooms = _rooms;
      } else {
        _filteredRooms = _rooms.where((room) {
          final matchName = room.roomName.toLowerCase().contains(q);
          final matchLocation = room.location.toLowerCase().contains(q);
          final matchPic = room.pic?.fullname.toLowerCase().contains(q) ?? false;
          return matchName || matchLocation || matchPic;
        }).toList();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Katalog Ruangan Sekolah'),
      ),
      body: Column(
        children: [
          // Bar Pencarian Ruangan
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
            child: TextField(
              controller: _searchController,
              onChanged: _filterRooms,
              decoration: InputDecoration(
                hintText: 'Cari nama ruangan, gedung, atau PIC...',
                prefixIcon: const Icon(Icons.search, size: 20, color: AppColors.textSecondary),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear, size: 18),
                        onPressed: () {
                          _searchController.clear();
                          _filterRooms('');
                        },
                      )
                    : null,
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              ),
            ),
          ),

          // Konten Utama
          Expanded(
            child: _buildBody(),
          ),
        ],
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const LoadingIndicator(message: 'Memuat katalog ruangan...');
    }

    if (_errorMessage != null) {
      return ErrorStateWidget(
        customMessage: _errorMessage,
        onRetry: _fetchRooms,
      );
    }

    if (_filteredRooms.isEmpty) {
      return const EmptyStateWidget(
        icon: Icons.meeting_room_outlined,
        title: 'Ruangan Tidak Ditemukan',
        message: 'Tidak ada ruangan yang sesuai dengan kata kunci pencarian.',
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchRooms,
      child: ListView.builder(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        itemCount: _filteredRooms.length,
        itemBuilder: (ctx, index) {
          final room = _filteredRooms[index];
          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: AppColors.primaryContainer.withValues(alpha: 0.6),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(
                          Icons.meeting_room_rounded,
                          color: AppColors.primary,
                          size: 24,
                        ),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              room.roomName,
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: AppColors.textPrimary,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                const Icon(Icons.location_on_outlined, size: 14, color: AppColors.textSecondary),
                                const SizedBox(width: 4),
                                Expanded(
                                  child: Text(
                                    room.location,
                                    style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      // Badge Kapasitas
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppColors.surfaceVariant,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          room.capacityLabel,
                          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.primary),
                        ),
                      ),
                    ],
                  ),
                  const Divider(height: 24),

                  // Informasi PIC Ruangan
                  Row(
                    children: [
                      const Icon(Icons.support_agent_rounded, size: 16, color: AppColors.textSecondary),
                      const SizedBox(width: 6),
                      Text(
                        'PIC Ruang: ${room.pic?.fullname ?? "Staf Sarpras"}',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500, color: AppColors.textPrimary),
                      ),
                      const Spacer(),
                      if (room.pic?.whatsapp != null && room.pic!.whatsapp!.isNotEmpty)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: AppColors.successContainer,
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Row(
                            children: const [
                              Icon(Icons.chat_outlined, size: 12, color: AppColors.textSuccess),
                              SizedBox(width: 4),
                              Text(
                                'WA Siaga',
                                style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppColors.textSuccess),
                              ),
                            ],
                          ),
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

