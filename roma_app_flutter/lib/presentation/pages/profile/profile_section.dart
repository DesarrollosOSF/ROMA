import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:roma_app_flutter/domain/entities/auth_session.dart';
import 'package:roma_app_flutter/presentation/bloc/auth/auth_cubit.dart';
import 'package:roma_app_flutter/presentation/bloc/auth/auth_state.dart';

class ProfileSection extends StatelessWidget {
  const ProfileSection({
    super.key,
    required this.session,
  });

  final AuthSession session;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AuthCubit, AuthState>(
      builder: (context, state) {
        final currentSession = state.session ?? session;
        final theme = Theme.of(context);
        return RefreshIndicator(
          onRefresh: () async => context.read<AuthCubit>().refreshProfile(),
          child: ListView(
            padding: EdgeInsets.fromLTRB(
              16,
              8,
              16,
              MediaQuery.of(context).padding.bottom + 16,
            ),
            children: [
              Card(
                child: Container(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [
                        theme.colorScheme.primaryContainer,
                        theme.colorScheme.primaryContainer.withValues(alpha: 0.5),
                      ],
                    ),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(4),
                          decoration: BoxDecoration(
                            color: theme.colorScheme.surface,
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(
                                color: theme.colorScheme.primary.withValues(alpha: 0.2),
                                blurRadius: 8,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: CircleAvatar(
                            radius: 28,
                            backgroundColor: theme.colorScheme.primaryContainer,
                            child: Text(
                              currentSession.user.nombre.isNotEmpty
                                  ? currentSession.user.nombre[0].toUpperCase()
                                  : '?',
                              style: TextStyle(
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                                color: theme.colorScheme.onPrimaryContainer,
                              ),
                            ),
                          ),
                        ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              currentSession.user.nombre,
                              style: theme.textTheme.titleLarge?.copyWith(
                                fontWeight: FontWeight.w600,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 4),
                            Text(
                              currentSession.user.email,
                              style: theme.textTheme.bodyMedium?.copyWith(
                                color: theme.colorScheme.onSurfaceVariant,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                'Información personal',
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              Card(
                child: Column(
                  children: [
                    _InfoTile(label: 'Rol', value: currentSession.user.rol.toUpperCase()),
                    const Divider(height: 1),
                    _InfoTile(label: 'Área', value: currentSession.user.area ?? '—'),
                    const Divider(height: 1),
                    _InfoTile(label: 'Cargo', value: currentSession.user.cargo ?? '—'),
                    const Divider(height: 1),
                    _InfoTile(label: 'Teléfono', value: currentSession.user.telefono ?? '—'),
                    if (currentSession.user.fechaCreacion != null) ...[
                      const Divider(height: 1),
                      _InfoTile(
                        label: 'Fecha de creación',
                        value: currentSession.user.fechaCreacion!.toLocal().toString().split('.').first,
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 24),
              Text(
                'Permisos',
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: currentSession.user.permisos.entries
                        .where((entry) => entry.value)
                        .map(
                          (entry) => Chip(
                            avatar: Icon(
                              ProfileSection._getPermissionIcon(entry.key),
                              size: 16,
                              color: theme.colorScheme.onPrimaryContainer,
                            ),
                            label: Text(entry.key.replaceAll('_', ' ')),
                            backgroundColor: theme.colorScheme.primaryContainer,
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          ),
                        )
                        .toList(),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  static IconData _getPermissionIcon(String permission) {
    final perm = permission.toLowerCase();
    if (perm.contains('crear') || perm.contains('create')) {
      return Icons.add_circle_outline;
    } else if (perm.contains('editar') || perm.contains('edit')) {
      return Icons.edit_outlined;
    } else if (perm.contains('eliminar') || perm.contains('delete')) {
      return Icons.delete_outline;
    } else if (perm.contains('ver') || perm.contains('view') || perm.contains('read')) {
      return Icons.visibility_outlined;
    } else if (perm.contains('aprobar') || perm.contains('approve')) {
      return Icons.check_circle_outline;
    } else if (perm.contains('reporte') || perm.contains('report')) {
      return Icons.assessment_outlined;
    }
    return Icons.verified_outlined;
  }
}

class _InfoTile extends StatelessWidget {
  const _InfoTile({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final icon = _getIconForLabel(label);
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: theme.colorScheme.primaryContainer.withValues(alpha: 0.3),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(
              icon,
              size: 18,
              color: theme.colorScheme.primary,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  static IconData _getIconForLabel(String label) {
    final lowerLabel = label.toLowerCase();
    if (lowerLabel.contains('rol')) {
      return Icons.badge_outlined;
    } else if (lowerLabel.contains('área') || lowerLabel.contains('area')) {
      return Icons.business_outlined;
    } else if (lowerLabel.contains('cargo')) {
      return Icons.work_outline;
    } else if (lowerLabel.contains('teléfono') || lowerLabel.contains('telefono')) {
      return Icons.phone_outlined;
    } else if (lowerLabel.contains('fecha')) {
      return Icons.calendar_today_outlined;
    }
    return Icons.info_outline;
  }
}
