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
      child: _DashboardView(
        session: session,
        flavor: flavor,
      ),
    );
  }
}

class _DashboardView extends StatelessWidget {
  const _DashboardView({
    required this.session,
    required this.flavor,
  });

  final AuthSession session;
  final AppFlavor flavor;

  @override
  Widget build(BuildContext context) {
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
            onPressed: () => context.read<AuthCubit>().logout(),
            tooltip: 'Cerrar sesión',
            icon: const Icon(Icons.exit_to_app),
          ),
        ],
      ),
      body: BlocConsumer<DashboardCubit, DashboardState>(
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
      ),
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
        padding: const EdgeInsets.all(16),
        children: [
          Wrap(
            spacing: 16,
            runSpacing: 16,
            children: [
              _StatCard(
                title: 'Activos registrados',
                value: stats.totalActivos.toString(),
                icon: Icons.precision_manufacturing_outlined,
                subtitle: '${stats.activosOperativos} operativos',
              ),
              _StatCard(
                title: 'Órdenes abiertas',
                value: stats.ordenesPendientes.toString(),
                icon: Icons.assignment_outlined,
                subtitle: '${stats.ordenesCriticas} críticas',
                color: Colors.orange,
              ),
              _StatCard(
                title: 'Órdenes finalizadas (30d)',
                value: stats.ordenesFinalizadas.toString(),
                icon: Icons.task_alt_outlined,
                subtitle: stats.cierrePromedioHoras != null
                    ? 'Cierre promedio: ${stats.cierrePromedioHoras!.toStringAsFixed(1)} h'
                    : 'Sin cierres recientes',
                color: Colors.green,
              ),
              _StatCard(
                title: 'Mantenimientos próximos',
                value: stats.mantenimientosProximos.toString(),
                icon: Icons.build_outlined,
                subtitle: '${stats.activosVencidos} vencidos',
                color: Colors.blue,
              ),
            ],
          ),
          const SizedBox(height: 24),
          if (data.ordenesCriticas.isNotEmpty) ...[
            Text(
              'Órdenes críticas',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 8),
            ...data.ordenesCriticas.map((orden) => _OrderTile(orden: orden, critical: true)),
            const SizedBox(height: 16),
          ],
          Text(
            'Órdenes recientes',
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: 8),
          if (data.ordenesRecientes.isEmpty)
            const _EmptyPlaceholder(
              icon: Icons.inbox_outlined,
              message: 'No hay órdenes recientes para mostrar.',
            )
          else
            ...data.ordenesRecientes.map((orden) => _OrderTile(orden: orden)),
          const SizedBox(height: 24),
          Text(
            'Mantenimientos próximos',
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: 8),
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
          const SizedBox(height: 24),
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

    return Card(
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: chipColor.withOpacity(critical ? 0.15 : 0.35),
          child: Icon(
            critical ? Icons.priority_high : Icons.receipt_long,
            color: chipColor,
          ),
        ),
        title: Text('#${orden.numeroRadicado}'),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (orden.nombreActivo != null && orden.nombreActivo!.isNotEmpty)
              Text(
                orden.nombreActivo!,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            Text(
              'Creada: ${_DashboardContent._formatDate(orden.fechaCreacion)}',
            ),
            if (orden.fechaLimite != null)
              Text('Limite: ${_DashboardContent._formatDate(orden.fechaLimite!)}'),
          ],
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Chip(
              label: Text(orden.estado.toUpperCase()),
              labelStyle: TextStyle(
                color: onChipColor,
                fontWeight: FontWeight.w600,
              ),
              backgroundColor: chipColor,
            ),
            const SizedBox(height: 6),
            Text(
              orden.nivelCriticidad.toUpperCase(),
              style: Theme.of(context).textTheme.labelMedium?.copyWith(
                    color: critical
                        ? Theme.of(context).colorScheme.error
                        : Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
            ),
          ],
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
    final effectiveColor = color ?? Theme.of(context).colorScheme.primary;
    return SizedBox(
      width: 260,
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(icon, color: effectiveColor, size: 32),
              const SizedBox(height: 8),
              Text(
                title,
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: 4),
              Text(
                value,
                style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                      color: effectiveColor,
                      fontWeight: FontWeight.bold,
                    ),
              ),
              if (subtitle != null) ...[
                const SizedBox(height: 6),
                Text(
                  subtitle!,
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ],
            ],
          ),
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


