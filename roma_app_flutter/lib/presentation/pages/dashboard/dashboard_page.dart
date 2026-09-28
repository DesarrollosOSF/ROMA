import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:roma_app_flutter/core/config/app_flavor.dart';
import 'package:roma_app_flutter/core/di/injector.dart';
import 'package:roma_app_flutter/domain/entities/auth_session.dart';
import 'package:roma_app_flutter/domain/entities/dashboard_data.dart';
import 'package:roma_app_flutter/domain/entities/order_summary.dart';
import 'package:roma_app_flutter/presentation/bloc/auth/auth_cubit.dart';
import 'package:roma_app_flutter/presentation/bloc/dashboard/dashboard_cubit.dart';
import 'package:roma_app_flutter/presentation/bloc/dashboard/dashboard_state.dart';

class DashboardPage extends StatelessWidget {
  const DashboardPage({
    super.key,
    required this.session,
    required this.flavor,
  });

  final AuthSession session;
  final AppFlavor flavor;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => DashboardCubit(injector())..loadDashboard(),
      child: DashboardView(
        session: session,
        flavor: flavor,
        embedInScaffold: true,
        onLogout: () => context.read<AuthCubit>().logout(),
      ),
    );
  }
}

class DashboardSection extends StatelessWidget {
  const DashboardSection({
    super.key,
    required this.session,
    required this.flavor,
  });

  final AuthSession session;
  final AppFlavor flavor;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => DashboardCubit(injector())..loadDashboard(),
      child: DashboardView(
        session: session,
        flavor: flavor,
        embedInScaffold: false,
      ),
    );
  }
}

class DashboardView extends StatelessWidget {
  const DashboardView({
    super.key,
    required this.session,
    required this.flavor,
    this.embedInScaffold = true,
    this.onLogout,
  });

  final AuthSession session;
  final AppFlavor flavor;
  final bool embedInScaffold;
  final VoidCallback? onLogout;

  @override
  Widget build(BuildContext context) {
    final content = BlocConsumer<DashboardCubit, DashboardState>(
      listener: (context, state) {
        final message = state.message;
        if (state.status == DashboardStatus.failure && message != null) {
          ScaffoldMessenger.of(context)
            ..clearSnackBars()
            ..showSnackBar(
              SnackBar(content: Text(message)),
            );
        }
      },
      builder: (context, state) {
        switch (state.status) {
          case DashboardStatus.initial:
          case DashboardStatus.loading:
            return const Center(child: CircularProgressIndicator());
          case DashboardStatus.failure:
            return _DashboardError(
              message: state.message ?? 'No fue posible cargar la información.',
              onRetry: () => context.read<DashboardCubit>().loadDashboard(),
            );
          case DashboardStatus.success:
            return _DashboardContent(data: state.data!);
        }
      },
    );

    if (!embedInScaffold) {
      return content;
    }

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Panel ROMA'),
            Text(
              session.user.nombre,
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ],
        ),
        actions: [
          Chip(
            avatar: const Icon(Icons.layers),
            label: Text(
              flavor.name.toUpperCase(),
              style: const TextStyle(letterSpacing: 0.8),
            ),
          ),
          const SizedBox(width: 12),
          IconButton(
            onPressed: onLogout,
            tooltip: 'Cerrar sesión',
            icon: const Icon(Icons.exit_to_app),
          ),
        ],
      ),
      body: content,
    );
  }
}

class _DashboardContent extends StatelessWidget {
  const _DashboardContent({required this.data});

  final DashboardData data;

  @override
  Widget build(BuildContext context) {
    final stats = data.stats;
    return RefreshIndicator(
      onRefresh: () => context.read<DashboardCubit>().refresh(),
      child: ListView(
        padding: EdgeInsets.fromLTRB(
          16,
          8,
          16,
          MediaQuery.of(context).padding.bottom + 24,
        ),
        children: [
          LayoutBuilder(
            builder: (context, constraints) {
              // Calcular el aspect ratio dinámicamente basado en el ancho disponible
              final screenWidth = MediaQuery.of(context).size.width;
              final cardWidth = (screenWidth - 32 - 12) / 2; // padding + spacing
              final cardHeight = cardWidth * 1.5; // Más altura para evitar overflow
              
              return GridView.count(
                crossAxisCount: 2,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
                childAspectRatio: cardWidth / cardHeight,
                children: [
              _StatCard(
                title: 'Activos registrados',
                value: stats.totalActivos.toString(),
                icon: Icons.precision_manufacturing_rounded,
                subtitle: '${stats.activosOperativos} operativos',
                color: const Color(0xFF0B57D0),
              ),
              _StatCard(
                title: 'Órdenes abiertas',
                value: stats.ordenesPendientes.toString(),
                icon: Icons.assignment_rounded,
                subtitle: '${stats.ordenesCriticas} críticas',
                color: const Color(0xFFFF6B35),
              ),
              _StatCard(
                title: 'Órdenes finalizadas (30d)',
                value: stats.ordenesFinalizadas.toString(),
                icon: Icons.task_alt_rounded,
                subtitle: stats.cierrePromedioHoras != null
                    ? 'Cierre promedio: ${stats.cierrePromedioHoras!.toStringAsFixed(1)} h'
                    : 'Sin cierres recientes',
                color: const Color(0xFF10B981),
              ),
              _StatCard(
                title: 'Mantenimientos próximos',
                value: stats.mantenimientosProximos.toString(),
                icon: Icons.build_rounded,
                subtitle: '${stats.activosVencidos} vencidos',
                color: const Color(0xFF3B82F6),
              ),
                ],
              );
            },
          ),
          const SizedBox(height: 20),
          if (data.ordenesCriticas.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 4),
              child: Text(
                'Órdenes críticas',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
              ),
            ),
            const SizedBox(height: 12),
            ...data.ordenesCriticas.map((orden) => _OrderTile(orden: orden, critical: true)),
            const SizedBox(height: 20),
          ],
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4),
            child: Text(
              'Órdenes recientes',
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
            ),
          ),
          const SizedBox(height: 12),
          if (data.ordenesRecientes.isEmpty)
            const _EmptyPlaceholder(
              icon: Icons.inbox_outlined,
              message: 'No hay órdenes recientes para mostrar.',
            )
          else
            ...data.ordenesRecientes.map((orden) => _OrderTile(orden: orden)),
          const SizedBox(height: 20),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4),
            child: Text(
              'Mantenimientos próximos',
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
            ),
          ),
          const SizedBox(height: 12),
          if (data.mantenimientosProximos.isEmpty)
            const _EmptyPlaceholder(
              icon: Icons.calendar_today_outlined,
              message: 'No se registran mantenimientos próximos.',
            )
          else
            ...data.mantenimientosProximos.map(
              (activo) => Card(
                child: ListTile(
                  leading: const Icon(Icons.calendar_month_outlined),
                  title: Text(activo.nombre),
                  subtitle: Text(
                    activo.fechaObjetivo != null
                        ? 'Programado para ${_formatDate(activo.fechaObjetivo!)}'
                        : 'Sin fecha definida',
                  ),
                  trailing: Text(
                    activo.estadoActual ?? '',
                    style: Theme.of(context).textTheme.labelMedium,
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  static String _formatDate(DateTime date) {
    return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
  }
}

class _OrderTile extends StatelessWidget {
  const _OrderTile({
    required this.orden,
    this.critical = false,
  });

  final OrderSummary orden;
  final bool critical;

  @override
  Widget build(BuildContext context) {
    final chipColor = critical
        ? Theme.of(context).colorScheme.error
        : Theme.of(context).colorScheme.secondaryContainer;
    final onChipColor = critical
        ? Theme.of(context).colorScheme.onError
        : Theme.of(context).colorScheme.onSecondaryContainer;

    final screenWidth = MediaQuery.of(context).size.width;
    final isSmallScreen = screenWidth < 360;
    
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () {
          // Navegar al detalle de la orden si es necesario
        },
        child: Padding(
          padding: EdgeInsets.all(isSmallScreen ? 12 : 14),
          child: Row(
            children: [
              Container(
                width: isSmallScreen ? 44 : 52,
                height: isSmallScreen ? 44 : 52,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      chipColor.withValues(alpha: critical ? 0.25 : 0.2),
                      chipColor.withValues(alpha: critical ? 0.15 : 0.1),
                    ],
                  ),
                  borderRadius: BorderRadius.circular(14),
                  boxShadow: [
                    BoxShadow(
                      color: chipColor.withValues(alpha: 0.2),
                      blurRadius: 6,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Icon(
                  critical ? Icons.priority_high_rounded : Icons.receipt_long_rounded,
                  color: chipColor,
                  size: isSmallScreen ? 22 : 26,
                ),
              ),
              SizedBox(width: isSmallScreen ? 12 : 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      '#${orden.numeroRadicado}',
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w600,
                            fontSize: isSmallScreen ? 14 : 16,
                          ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (orden.nombreActivo != null && orden.nombreActivo!.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        orden.nombreActivo!,
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: Theme.of(context).colorScheme.onSurfaceVariant,
                              fontSize: isSmallScreen ? 12 : 14,
                            ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                    const SizedBox(height: 6),
                    Wrap(
                      spacing: isSmallScreen ? 4 : 8,
                      runSpacing: 4,
                      children: [
                        Chip(
                          label: Text(
                            orden.estado.toUpperCase(),
                            style: TextStyle(
                              fontSize: isSmallScreen ? 9 : 11,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          labelStyle: TextStyle(color: onChipColor),
                          backgroundColor: chipColor,
                          padding: EdgeInsets.symmetric(
                            horizontal: isSmallScreen ? 6 : 8,
                            vertical: 4,
                          ),
                        ),
                        Container(
                          padding: EdgeInsets.symmetric(
                            horizontal: isSmallScreen ? 6 : 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: critical
                                ? Theme.of(context).colorScheme.errorContainer
                                : Theme.of(context).colorScheme.surfaceContainerHighest,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            orden.nivelCriticidad.toUpperCase(),
                            style: Theme.of(context).textTheme.labelSmall?.copyWith(
                                  color: critical
                                      ? Theme.of(context).colorScheme.onErrorContainer
                                      : Theme.of(context).colorScheme.onSurfaceVariant,
                                  fontWeight: FontWeight.w600,
                                  fontSize: isSmallScreen ? 9 : 11,
                                ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Creada: ${_DashboardContent._formatDate(orden.fechaCreacion)}',
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            color: Theme.of(context).colorScheme.onSurfaceVariant,
                            fontSize: isSmallScreen ? 10 : 12,
                          ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
              SizedBox(width: isSmallScreen ? 4 : 8),
              Icon(
                Icons.chevron_right_rounded,
                color: Theme.of(context).colorScheme.onSurfaceVariant,
                size: isSmallScreen ? 20 : 24,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.title,
    required this.value,
    required this.icon,
    this.subtitle,
    this.color,
  });

  final String title;
  final String value;
  final String? subtitle;
  final IconData icon;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final effectiveColor = color ?? theme.colorScheme.primary;
    final screenWidth = MediaQuery.of(context).size.width;
    final isSmallScreen = screenWidth < 360;
    
    return Card(
      child: Container(
        width: double.infinity,
        padding: EdgeInsets.all(isSmallScreen ? 12 : 14),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              effectiveColor.withValues(alpha: 0.08),
              effectiveColor.withValues(alpha: 0.03),
            ],
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: EdgeInsets.all(isSmallScreen ? 8 : 10),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    effectiveColor.withValues(alpha: 0.2),
                    effectiveColor.withValues(alpha: 0.1),
                  ],
                ),
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: effectiveColor.withValues(alpha: 0.2),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Icon(
                icon,
                color: effectiveColor,
                size: isSmallScreen ? 20 : 24,
              ),
            ),
            SizedBox(height: isSmallScreen ? 8 : 10),
            Text(
              title,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
                fontWeight: FontWeight.w500,
                fontSize: isSmallScreen ? 11 : 12,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            SizedBox(height: isSmallScreen ? 4 : 6),
            FittedBox(
              fit: BoxFit.scaleDown,
              alignment: Alignment.centerLeft,
              child: Text(
                value,
                style: theme.textTheme.headlineSmall?.copyWith(
                  color: effectiveColor,
                  fontWeight: FontWeight.bold,
                  height: 1.0,
                  fontSize: isSmallScreen ? 24 : 28,
                ),
              ),
            ),
            if (subtitle != null) ...[
              SizedBox(height: isSmallScreen ? 4 : 6),
              Text(
                subtitle!,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                  fontSize: isSmallScreen ? 10 : 11,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _DashboardError extends StatelessWidget {
  const _DashboardError({
    required this.message,
    required this.onRetry,
  });

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.warning_amber_outlined, size: 64),
            const SizedBox(height: 12),
            Text(
              message,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: const Text('Reintentar'),
            ),
          ],
        ),
      ),
    );
  }
}

class _EmptyPlaceholder extends StatelessWidget {
  const _EmptyPlaceholder({
    required this.icon,
    required this.message,
  });

  final IconData icon;
  final String message;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 32, horizontal: 24),
        child: Column(
          children: [
            Icon(icon, size: 48, color: Theme.of(context).colorScheme.onSurfaceVariant),
            const SizedBox(height: 12),
            Text(
              message,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyMedium,
            ),
          ],
        ),
      ),
    );
  }
}


