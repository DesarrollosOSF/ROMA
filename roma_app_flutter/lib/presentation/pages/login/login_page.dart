import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:roma_app_flutter/core/config/env.dart';
import 'package:roma_app_flutter/presentation/bloc/auth/auth_cubit.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key, this.errorMessage, this.isSubmitting = false});

  final String? errorMessage;
  final bool isSubmitting;

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _checkingServer = true;
  bool? _serverReachable;

  @override
  void initState() {
    super.initState();
    _bootstrapForm();
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final message = widget.errorMessage;
      if (message != null && message.isNotEmpty) {
        final messenger = ScaffoldMessenger.of(context);
        // ignore: cascade_invocations
        messenger.clearSnackBars();
        // ignore: cascade_invocations
        messenger.showSnackBar(
          SnackBar(
            content: Text(message),
          ),
        );
      }
    });

    final isLoading = widget.isSubmitting;

    return Scaffold(
      body: GestureDetector(
        onTap: () => FocusScope.of(context).unfocus(),
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 24),
            child: DecoratedBox(
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.05),
                    blurRadius: 24,
                    offset: const Offset(0, 12),
                  ),
                ],
              ),
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.manage_accounts_outlined,
                      size: 56,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'Ingresar a ROMA',
                      style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                            fontWeight: FontWeight.w600,
                          ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Utiliza tus credenciales corporativas',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: Theme.of(context).colorScheme.onSurfaceVariant,
                          ),
                    ),
                    const SizedBox(height: 24),
                    _ConnectionStatusBanner(
                      checking: _checkingServer,
                      reachable: _serverReachable,
                      baseUrl: EnvConfig.instance.apiBaseUrl,
                    ),
                    const SizedBox(height: 16),
                    Form(
                      key: _formKey,
                      child: Column(
                        children: [
                          TextFormField(
                            controller: _emailController,
                            keyboardType: TextInputType.emailAddress,
                            autofillHints: const [AutofillHints.username, AutofillHints.email],
                            enabled: !isLoading,
                            decoration: const InputDecoration(
                              labelText: 'Correo electrónico',
                              border: OutlineInputBorder(),
                            ),
                            validator: (value) {
                              final text = value?.trim() ?? '';
                              if (text.isEmpty) {
                                return 'El correo es obligatorio';
                              }
                              if (!text.contains('@')) {
                                return 'Ingresa un correo válido';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 16),
                          TextFormField(
                            controller: _passwordController,
                            obscureText: _obscurePassword,
                            autofillHints: const [AutofillHints.password],
                            enabled: !isLoading,
                            decoration: InputDecoration(
                              labelText: 'Contraseña',
                              border: const OutlineInputBorder(),
                              suffixIcon: IconButton(
                                icon: Icon(
                                  _obscurePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                                ),
                                onPressed: isLoading
                                    ? null
                                    : () {
                                        setState(() {
                                          _obscurePassword = !_obscurePassword;
                                        });
                                      },
                              ),
                            ),
                            validator: (value) {
                              final text = value ?? '';
                              if (text.isEmpty) {
                                return 'La contraseña es obligatoria';
                              }
                              if (text.length < 4) {
                                return 'La contraseña es muy corta';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 24),
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              onPressed: isLoading ? null : _onSubmit,
                              icon: isLoading
                                  ? SizedBox(
                                      width: 18,
                                      height: 18,
                                      child: CircularProgressIndicator(
                                        color: Theme.of(context).colorScheme.onPrimary,
                                        strokeWidth: 2,
                                      ),
                                    )
                                  : const Icon(Icons.login),
                              label: Text(isLoading ? 'Verificando...' : 'Ingresar'),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _onSubmit() {
    final form = _formKey.currentState;
    if (form == null) {
      return;
    }
    if (!form.validate()) {
      return;
    }
    final email = _emailController.text.trim();
    final password = _passwordController.text;

    context.read<AuthCubit>()
      ..rememberEmail(email)
      ..login(email, password);
  }

  Future<void> _bootstrapForm() async {
    final authCubit = context.read<AuthCubit>();
    final rememberedEmail = await authCubit.getLastEmail();
    if (rememberedEmail != null && rememberedEmail.isNotEmpty && mounted) {
      _emailController.text = rememberedEmail;
    }

    final reachable = await authCubit.isServerReachable();
    if (!mounted) {
      return;
    }

    setState(() {
      _checkingServer = false;
      _serverReachable = reachable;
    });

    if (!reachable && mounted) {
      final messenger = ScaffoldMessenger.of(context);
      // ignore: cascade_invocations
      messenger.clearSnackBars();
      // ignore: cascade_invocations
      messenger.showSnackBar(
        SnackBar(
          content: Text('No se pudo conectar con ${EnvConfig.instance.apiBaseUrl}. Verifica tu conexión.'),
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }
}

class _ConnectionStatusBanner extends StatelessWidget {
  const _ConnectionStatusBanner({
    required this.checking,
    required this.baseUrl,
    this.reachable,
  });

  final bool checking;
  final bool? reachable;
  final String baseUrl;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    IconData icon;
    Color color;
    String label;

    if (checking) {
      icon = Icons.sync;
      color = theme.colorScheme.primary;
      label = 'Verificando conexión con $baseUrl...';
    } else if (reachable == true) {
      icon = Icons.cloud_done_outlined;
      color = theme.colorScheme.primary;
      label = 'Conectado a $baseUrl';
    } else {
      icon = Icons.cloud_off_outlined;
      color = theme.colorScheme.error;
      label = 'Sin conexión con $baseUrl';
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Icon(icon, color: color),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              label,
              style: theme.textTheme.bodyMedium?.copyWith(color: color),
            ),
          ),
          if (checking)
            SizedBox(
              height: 18,
              width: 18,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                color: color,
              ),
            ),
        ],
      ),
    );
  }
}


