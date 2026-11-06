import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';
import 'package:path_provider/path_provider.dart' as path_provider;

/// Hasil Kompresi Gambar
class CompressionResult {
  final File file;
  final int originalSizeBytes;
  final int compressedSizeBytes;

  const CompressionResult({
    required this.file,
    required this.originalSizeBytes,
    required this.compressedSizeBytes,
  });

  /// Persentase penghematan ukuran file (cth: "78%")
  String get savingsPercentage {
    if (originalSizeBytes <= 0) return '0%';
    final saved = ((originalSizeBytes - compressedSizeBytes) / originalSizeBytes) * 100;
    return '${saved.clamp(0, 100).toStringAsFixed(0)}%';
  }

  /// Format ukuran ramah manusia (cth: "1.2 MB")
  static String formatBytes(int bytes) {
    if (bytes <= 0) return '0 B';
    if (bytes < 1024) return '$bytes B';
    if (bytes < 1024 * 1024) return '${(bytes / 1024).toStringAsFixed(1)} KB';
    return '${(bytes / (1024 * 1024)).toStringAsFixed(2)} MB';
  }
}

/// Helper Kompresor Foto Sarpras Sebelum Diunggah ke Server
class ImageCompressor {
  ImageCompressor._();

  /// Mengompresi file foto menjadi format JPEG dengan kualitas optimal (75%)
  /// dan resolusi maksimal 1280x720 untuk menghemat kuota dan memori server.
  static Future<CompressionResult> compressImage(File originalFile) async {
    final originalSize = await originalFile.length();

    try {
      final tempDir = await path_provider.getTemporaryDirectory();
      final targetPath = '${tempDir.path}/compressed_${DateTime.now().millisecondsSinceEpoch}.jpg';

      final XFile? compressedXFile = await FlutterImageCompress.compressAndGetFile(
        originalFile.absolute.path,
        targetPath,
        quality: 75,
        minWidth: 1280,
        minHeight: 720,
        format: CompressFormat.jpeg,
      );

      if (compressedXFile != null) {
        final compressedFile = File(compressedXFile.path);
        final compressedSize = await compressedFile.length();

        if (kDebugMode) {
          debugPrint('📸 [KOMPRESI FOTO]');
          debugPrint('   • Sebelum : ${CompressionResult.formatBytes(originalSize)}');
          debugPrint('   • Sesudah : ${CompressionResult.formatBytes(compressedSize)}');
        }

        return CompressionResult(
          file: compressedFile,
          originalSizeBytes: originalSize,
          compressedSizeBytes: compressedSize,
        );
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint('⚠️ Kompresi gagal, menggunakan file asli: $e');
      }
    }

    // Fallback jika kompresi gagal: gunakan file asli
    return CompressionResult(
      file: originalFile,
      originalSizeBytes: originalSize,
      compressedSizeBytes: originalSize,
    );
  }
}

