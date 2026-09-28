import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:intl/intl.dart';

import 'package:roma_app_flutter/core/di/injector.dart';
import 'package:roma_app_flutter/domain/entities/auth_session.dart';
import 'package:roma_app_flutter/domain/entities/order_summary.dart';
import 'package:roma_app_flutter/domain/repositories/orders_repository.dart';
import 'package:roma_app_flutter/presentation/bloc/orders/orders_cubit.dart';
import 'package:roma_app_flutter/presentation/bloc/orders/orders_state.dart';
import 'package:roma_app_flutter/presentation/pages/orders/order_detail_page.dart';

class OrdersSection extends StatefulWidget {
  const OrdersSection({
    super.key,
    required this.session,
  });

  final AuthSession session;

  @override
  State<OrdersSection> createState() => _OrdersSectionState();
}

class _OrdersSectionState extends State<OrdersSection> {
  late final TextEditingController _searchController;

  @override
  void initState() {
    super.initState();
    _searchController = TextEditingController();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => OrdersCubit(
        repository: injector<OrdersRepository>(),
        session: widget.session,
      )..loadOrders(),
      child: Column(
        children: [
          _FiltersBar(
            session: widget.session,
            searchController: _searchController,
          ),
          Expanded(
            child: BlocBuilder<OrdersCubit, OrdersState>(
              builder: (context, state) {
                if (state.status == OrdersStatus.loading && state.orders.isEmpty) {
                  return const Center(child: CircularProgressIndicator());
                }

                if (state.status == OrdersStatus.failure && state.orders.isEmpty) {
                  return _EmptyMessage(
                    icon: Icons.warning_amber_outlined,
                    message: state.message ?? 'No fue posible cargar las órdenes.',
                    actionLabel: 'Reintentar',
                    onAction: () => context.read<OrdersCubit>().loadOrders(),
                  );
                }

                if (state.orders.isEmpty) {
                  return _EmptyMessage(
                    icon: Icons.inbox_outlined,
                    message: 'No se encontraron órdenes con los filtros actuales.',
                  );
                }

                return RefreshIndicator(
                  onRefresh: () async => context.read<OrdersCubit>().refresh(),
                  child: ListView.separated(
                    padding: EdgeInsets.fromLTRB(
                      16,
                      8,
                      16,
                      MediaQuery.of(context).padding.bottom + 16,
                    ),
                    itemBuilder: (context, index) {
                      final order = state.orders[index];
                      return _OrderTile(
                        order: order,
                        onTap: () => _openDetail(context, order.id),
                      );
                    },
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemCount: state.orders.length,
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _openDetail(BuildContext context, int orderId) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => OrderDetailPage(orderId: orderId),
      ),
    );
    // Al regresar, actualizamos por si hubo cambios.
    if (context.mounted) {
      context.read<OrdersCubit>().refresh();
    }
  }
}

class _FiltersBar extends StatelessWidget {
  const _FiltersBar({
    required this.session,
    required this.searchController,
  });

  final AuthSession session;
  final TextEditingController searchController;

  @override
  Widget build(BuildContext context) {
    final cubit = context.watch<OrdersCubit>();
    final state = cubit.state;
    final role = session.user.rol.toLowerCase();

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          TextField(
            controller: searchController,
            decoration: InputDecoration(
              hintText: 'Buscar por radicado, descripción o activo',
              prefixIcon: const Icon(Icons.search),
              suffixIcon: IconButton(
                icon: const Icon(Icons.clear),
                tooltip: 'Limpiar búsqueda',
                onPressed: () {
                  searchController.clear();
                  context.read<OrdersCubit>().search(null);
                },
              ),
            ),
            textInputAction: TextInputAction.search,
            onSubmitted: (value) => context.read<OrdersCubit>().search(value),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _DropdownFilter(
                  label: 'Estado',
                  value: state.filters.estado ?? 'todos',
                  items: const [
                    DropdownMenuItem(value: 'todos', child: Text('Todos')),
                    DropdownMenuItem(value: 'recibido', child: Text('Recibido')),
                    DropdownMenuItem(value: 'en_proceso', child: Text('En proceso')),
                    DropdownMenuItem(value: 'finalizado', child: Text('Finalizado')),
                    DropdownMenuItem(value: 'rechazado', child: Text('Rechazado')),
                  ],
                  onChanged: (value) => context.read<OrdersCubit>().changeEstado(value),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _DropdownFilter(
                  label: 'Criticidad',
                  value: state.filters.criticidad ?? 'todas',
                  items: const [
                    DropdownMenuItem(value: 'todas', child: Text('Todas')),
                    DropdownMenuItem(value: 'critica', child: Text('Crítica')),
                    DropdownMenuItem(value: 'alta', child: Text('Alta')),
                    DropdownMenuItem(value: 'normal', child: Text('Normal')),
                    DropdownMenuItem(value: 'baja', child: Text('Baja')),
                  ],
                  onChanged: (value) => context.read<OrdersCubit>().changeCriticidad(value),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (role == 'operario' || role == 'jefe' || role == 'director')
            FilterChip(
              label: const Text('Sólo mis órdenes'),
              selected: state.onlyMine,
              onSelected: (_) => context.read<OrdersCubit>().toggleOnlyMine(),
            ),
        ],
      ),
    );
  }
}

class _DropdownFilter extends StatelessWidget {
  const _DropdownFilter({
    required this.label,
    required this.value,
    required this.items,
    required this.onChanged,
  });

  final String label;
  final String value;
  final List<DropdownMenuItem<String>> items;
  final ValueChanged<String?> onChanged;

  @override
  Widget build(BuildContext context) {
    return InputDecorator(
      decoration: InputDecoration(
        labelText: label,
        border: const OutlineInputBorder(),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        isDense: true,
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: value,
          items: items,
          onChanged: onChanged,
          isExpanded: true,
          isDense: true,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                fontSize: 13,
              ),
          itemHeight: 48,
        ),
      ),
    );
  }
}

class _OrderTile extends StatelessWidget {
  const _OrderTile({
    required this.order,
    required this.onTap,
  });

  final OrderSummary order;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final dateFormat = DateFormat('dd/MM/yyyy HH:mm');
    final fechaCreacion = dateFormat.format(order.fechaCreacion);
    final fechaLimite = order.fechaLimite != null ? dateFormat.format(order.fechaLimite!) : null;

    Color criticityColor;
    switch (order.nivelCriticidad.toLowerCase()) {
      case 'critica':
        criticityColor = Colors.red;
        break;
      case 'alta':
        criticityColor = Colors.orange;
        break;
      case 'baja':
        criticityColor = Colors.green;
        break;
      default:
        criticityColor = Theme.of(context).colorScheme.primary;
    }

    final theme = Theme.of(context);
    final isCritical = order.nivelCriticidad.toLowerCase() == 'critica';
    
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 52,
                height: 52,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      criticityColor.withValues(alpha: 0.25),
                      criticityColor.withValues(alpha: 0.15),
                    ],
                  ),
                  borderRadius: BorderRadius.circular(14),
                  boxShadow: [
                    BoxShadow(
                      color: criticityColor.withValues(alpha: 0.2),
                      blurRadius: 6,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Icon(
                  isCritical 
                      ? Icons.priority_high_rounded 
                      : Icons.assignment_rounded,
                  color: criticityColor,
                  size: 26,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      order.numeroRadicado,
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (order.nombreActivo != null && order.nombreActivo!.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        order.nombreActivo!,
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 4,
                      children: [
                        Chip(
                          avatar: Icon(
                            _OrderTile._getEstadoIcon(order.estado),
                            size: 14,
                            color: theme.colorScheme.onSecondaryContainer,
                          ),
                          label: Text(
                            order.estado.toUpperCase(),
                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600),
                          ),
                          backgroundColor: theme.colorScheme.secondaryContainer,
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [
                                criticityColor.withValues(alpha: 0.2),
                                criticityColor.withValues(alpha: 0.1),
                              ],
                            ),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(
                              color: criticityColor.withValues(alpha: 0.3),
                              width: 1,
                            ),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                isCritical ? Icons.warning_rounded : Icons.info_outline,
                                size: 14,
                                color: criticityColor,
                              ),
                              const SizedBox(width: 4),
                              Text(
                                order.nivelCriticidad.toUpperCase(),
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                  color: criticityColor,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Creada: $fechaCreacion',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (fechaLimite != null)
                      Text(
                        'Límite: $fechaLimite',
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                  ],
                ),
              ),
              Icon(
                Icons.chevron_right_rounded,
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ],
          ),
        ),
      ),
    );
  }

  static IconData _getEstadoIcon(String estado) {
    final est = estado.toLowerCase();
    if (est.contains('finalizado') || est.contains('completado')) {
      return Icons.check_circle_outline;
    } else if (est.contains('proceso') || est.contains('en_proceso')) {
      return Icons.sync;
    } else if (est.contains('recibido') || est.contains('pendiente')) {
      return Icons.pending_outlined;
    } else if (est.contains('rechazado') || est.contains('cancelado')) {
      return Icons.cancel_outlined;
    }
    return Icons.info_outline;
  }
}

class _EmptyMessage extends StatelessWidget {
  const _EmptyMessage({
    required this.icon,
    required this.message,
    this.actionLabel,
    this.onAction,
  });

  final IconData icon;
  final String message;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: theme.colorScheme.surfaceContainerHighest.withValues(alpha: 0.5),
                shape: BoxShape.circle,
              ),
              child: Icon(
                icon,
                size: 48,
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
            const SizedBox(height: 24),
            Text(
              message,
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyLarge?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
            if (actionLabel != null && onAction != null) ...[
              const SizedBox(height: 24),
              ElevatedButton.icon(
                onPressed: onAction,
                icon: const Icon(Icons.refresh_rounded),
                label: Text(actionLabel!),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
