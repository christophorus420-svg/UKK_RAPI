import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/theme/app_colors.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/common_button.dart';
import '../../widgets/custom_text_field.dart';
import 'reset_password_screen.dart';

/// Layar Pengajuan Token Pemulihan Sandi (Lupa Password)
class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _phoneController = TextEditingController();
  bool _isLoading = false;

  @override
  void dispose() {
    _usernameController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _handleForgotPassword() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);
    FocusScope.of(context).unfocus();

    final authProvider = context.read<AuthProvider>();
    final result = await authProvider.forgotPassword(
      username: _usernameController.text,
      phoneNumber: _phoneController.text,
    );

    setState(() => _isLoading = false);

    if (!mounted) return;

    if (result != null && result['success'] == true) {
      final token = result['reset_token']?.toString() ?? '';
      final username = result['username']?.toString() ?? _usernameController.text;

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message'] ?? 'Token pemulihan berhasil diterbitkan.'),
          backgroundColor: AppColors.success,
        ),
      );

      // Lanjut ke Halaman Reset Password dengan token terisi otomatis
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (ctx) => ResetPasswordScreen(
            prefilledUsername: username,
            prefilledToken: token,
          ),
        ),
      );
    } else {
      final errorMsg = authProvider.errorMessage ?? 'Nomor telepon tidak cocok dengan akun terdaftar.';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(errorMsg),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Lupa Kata Sandi'),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 20.0),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const SizedBox(height: 10),
                const Icon(
                  Icons.lock_reset_rounded,
                  size: 64,
                  color: AppColors.primary,
                ),
                const SizedBox(height: 16),
                const Text(
                  'Pemulihan Kata Sandi',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Masukkan username dan nomor telepon yang terdaftar pada akun sekolah Anda untuk memverifikasi identitas.',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 13, color: AppColors.textSecondary, height: 1.4),
                ),
                const SizedBox(height: 32),

                CustomTextField(
                  controller: _usernameController,
                  label: 'Username Akun',
                  hint: 'Masukkan username terdaftar (cth: guru_andi)',
                  prefixIcon: Icons.person_outline,
                  validator: (val) {
                    if (val == null || val.trim().isEmpty) return 'Username wajib diisi';
                    return null;
                  },
                ),
                const SizedBox(height: 18),

                CustomTextField(
                  controller: _phoneController,
                  label: 'Nomor WhatsApp / Telepon Terdaftar',
                  hint: 'Cth: 082133445566 (atau 628...)',
                  prefixIcon: Icons.phone_outlined,
                  keyboardType: TextInputType.phone,
                  validator: (val) {
                    if (val == null || val.trim().isEmpty) return 'Nomor telepon wajib diisi';
                    if (val.length < 8) return 'Nomor telepon minimal 8 digit';
                    return null;
                  },
                ),
                const SizedBox(height: 28),

                CommonButton(
                  text: 'Verifikasi & Terbitkan Token',
                  icon: Icons.vpn_key_outlined,
                  isLoading: _isLoading,
                  onPressed: _handleForgotPassword,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

