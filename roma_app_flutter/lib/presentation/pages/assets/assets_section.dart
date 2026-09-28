import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';

import 'package:roma_app_flutter/core/di/injector.dart';
import 'package:roma_app_flutter/domain/entities/asset_attachment.dart';
import 'package:roma_app_flutter/domain/entities/asset_detail.dart';
import 'package:roma_app_flutter/domain/entities/asset_summary.dart';
import 'package:roma_app_flutter/domain/repositories/assets_repository.dart';
import 'package:roma_app_flutter/presentation/bloc/asset_detail/asset_detail_cubit.dart';
import 'package:roma_app_flutter/presentation/bloc/asset_detail/asset_detail_state.dart';
import 'package:roma_app_flutter/presentation/bloc/assets/assets_cubit.dart';
import 'package:roma_app_flutter/presentation/bloc/assets/assets_state.dart';

class AssetsSection extends StatefulWidget {
  const AssetsSection({super.key});

  @override
  State<AssetsSection> createState() => _AssetsSectionState();
}

class _AssetsSectionState extends State<AssetsSection> {
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
      create: (_) => AssetsCubit(repository: injector<AssetsRepository>())..loadAssets(),
      child: Column(
        children: [
          _AssetsFiltersBar(searchController: _searchController),
          Expanded(
            child: BlocBuilder<AssetsCubit, AssetsState>(
              builder: (context, state) {
                if (state.status == AssetsStatus.loading && state.assets.isEmpty) {
                  return const Center(child: CircularProgressIndicator());
                }

                if (state.status == AssetsStatus.failure && state.assets.isEmpty) {
                  return _AssetsEmptyMessage(
                    icon: Icons.warning_amber_outlined,
                    message: state.message ?? 'No fue posible cargar los activos.',
                    actionLabel: 'Reintentar',
                    onAction: () => context.read<AssetsCubit>().loadAssets(),
                  );
                }

                if (state.assets.isEmpty) {
                  return _AssetsEmptyMessage(
                    icon: Icons.inventory_2_outlined,
                    message: 'No se encontraron activos con los filtros actuales.',
                  );
                }

                return RefreshIndicator(
                  onRefresh: () async => context.read<AssetsCubit>().refresh(),
                  child: ListView.separated(
                    padding: EdgeInsets.fromLTRB(
                      16,
                      8,
                      16,
                      MediaQuery.of(context).padding.bottom + 32,
                    ),
                    itemBuilder: (context, index) {
                      final asset = state.assets[index];
                      return _AssetTile(
                        asset: asset,
                        onTap: () => _openDetail(context, asset.id),
                      );
                    },
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemCount: state.assets.length,
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _openDetail(BuildContext context, int assetId) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _AssetDetailPage(assetId: assetId),
      ),
    );
    if (context.mounted) {
      context.read<AssetsCubit>().refresh();
    }
  }
}

class _AssetsFiltersBar extends StatelessWidget {
  const _AssetsFiltersBar({required this.searchController});

  final TextEditingController searchController;

  static const _categories = <String, String>{
    'todas': 'Todas las categorías',
    'equipo_especial': 'Equipo especial',
    'vehiculos': 'Vehículos',
    'maquinas': 'Máquinas',
    'muebles_enseres': 'Muebles y enseres',
    'horno_crematorio': 'Horno crematorio',
    'planta_agua_residual': 'Planta de agua residual',
    'cofres_cremacion': 'Cofres de cremación',
  };

  static const _states = <String, String>{
    'todos': 'Todos los estados',
    'operativo': 'Operativo',
    'en_reparacion': 'En reparación',
    'fuera_servicio': 'Fuera de servicio',
    'en_baja': 'En baja',
  };

  @override
  Widget build(BuildContext context) {
    final cubit = context.watch<AssetsCubit>();
    final filters = cubit.state.filters;

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
      child: Column(
        children: [
          TextField(
            controller: searchController,
            decoration: InputDecoration(
              hintText: 'Buscar por nombre, código o ubicación',
              prefixIcon: const Icon(Icons.search),
              suffixIcon: IconButton(
                icon: const Icon(Icons.clear),
                tooltip: 'Limpiar búsqueda',
                onPressed: () {
                  searchController.clear();
                  context.read<AssetsCubit>().search(null);
                },
              ),
            ),
            textInputAction: TextInputAction.search,
            onSubmitted: (value) => context.read<AssetsCubit>().search(value),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _DropdownFilter(
                  label: 'Categoría',
                  value: filters.categoria ?? 'todas',
                  items: _categories.entries
                      .map(
                        (entry) => DropdownMenuItem(
                          value: entry.key,
                          child: Text(entry.value),
                        ),
                      )
                      .toList(),
                  onChanged: (value) => context.read<AssetsCubit>().changeCategoria(value),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _DropdownFilter(
                  label: 'Estado',
                  value: filters.estado ?? 'todos',
                  items: _states.entries
                      .map(
                        (entry) => DropdownMenuItem(
                          value: entry.key,
                          child: Text(entry.value),
                        ),
                      )
                      .toList(),
                  onChanged: (value) => context.read<AssetsCubit>().changeEstado(value),
                ),
              ),
            ],
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

class _AssetTile extends StatelessWidget {
  const _AssetTile({required this.asset, required this.onTap});

  final AssetSummary asset;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final date = asset.fechaObjetivo != null
        ? DateFormat('dd/MM/yyyy').format(asset.fechaObjetivo!)
        : null;

    final theme = Theme.of(context);
    Color statusColor = theme.colorScheme.primary;
    if (asset.estadoActual != null) {
      switch (asset.estadoActual!.toLowerCase()) {
        case 'operativo':
          statusColor = Colors.green.shade700;
          break;
        case 'en_reparacion':
        case 'en reparación':
          statusColor = Colors.orange.shade700;
          break;
        case 'fuera_servicio':
        case 'fuera de servicio':
          statusColor = Colors.red.shade700;
          break;
        default:
          statusColor = theme.colorScheme.primary;
      }
    }

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
                      statusColor.withValues(alpha: 0.25),
                      statusColor.withValues(alpha: 0.15),
                    ],
                  ),
                  borderRadius: BorderRadius.circular(14),
                  boxShadow: [
                    BoxShadow(
                      color: statusColor.withValues(alpha: 0.2),
                      blurRadius: 6,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Icon(
                  Icons.inventory_2_rounded,
                  color: statusColor,
                  size: 26,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      asset.nombre,
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    if (asset.codigo != null && asset.codigo!.isNotEmpty)
                      Text(
                        'Código: ${asset.codigo}',
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    const SizedBox(height: 8),
                    if (asset.estadoActual != null && asset.estadoActual!.isNotEmpty)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: statusColor.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          asset.estadoActual!.toUpperCase(),
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: statusColor,
                          ),
                        ),
                      ),
                    if (date != null) ...[
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Icon(
                            Icons.calendar_today_rounded,
                            size: 14,
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              'Mantenimiento: $date',
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: theme.colorScheme.onSurfaceVariant,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
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
}

class _AssetsEmptyMessage extends StatelessWidget {
  const _AssetsEmptyMessage({
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

class _AssetDetailPage extends StatelessWidget {
  const _AssetDetailPage({required this.assetId});

  final int assetId;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => AssetDetailCubit(injector<AssetsRepository>())..load(assetId),
      child: Scaffold(
        appBar: AppBar(
          title: Text('Activo #$assetId'),
        ),
        body: BlocBuilder<AssetDetailCubit, AssetDetailState>(
          builder: (context, state) {
            switch (state.status) {
              case AssetDetailStatus.initial:
              case AssetDetailStatus.loading:
                return const Center(child: CircularProgressIndicator());
              case AssetDetailStatus.failure:
                return _AssetsEmptyMessage(
                  icon: Icons.error_outline,
                  message: state.message ?? 'No se pudo cargar el activo.',
                  actionLabel: 'Reintentar',
                  onAction: () => context.read<AssetDetailCubit>().load(assetId),
                );
              case AssetDetailStatus.success:
                final detail = state.detail!;
                return _AssetDetailView(detail: detail);
            }
          },
        ),
      ),
    );
  }
}

class _AssetDetailView extends StatelessWidget {
  const _AssetDetailView({required this.detail});

  final AssetDetail detail;

  @override
  Widget build(BuildContext context) {
    final dateFormat = DateFormat('dd/MM/yyyy');
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    detail.nombre,
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const SizedBox(height: 8),
                  if (detail.codigo != null && detail.codigo!.isNotEmpty)
                    _InfoRow('Código interno', detail.codigo!),
                  if (detail.codigoPatrimonial != null && detail.codigoPatrimonial!.isNotEmpty)
                    _InfoRow('Código patrimonial', detail.codigoPatrimonial!),
                  _InfoRow('Categoría', detail.categoria ?? '—'),
                  _InfoRow('Ubicación', detail.ubicacion ?? '—'),
                  _InfoRow('Estado', detail.estadoActual ?? '—'),
                  _InfoRow('Responsable', detail.responsable ?? '—'),
                  _InfoRow('Área asignada', detail.areaAsignada ?? '—'),
                  if (detail.fechaAdquisicion != null)
                    _InfoRow('Fecha de adquisición', dateFormat.format(detail.fechaAdquisicion!)),
                  if (detail.proximoMantenimiento != null)
                    _InfoRow('Próximo mantenimiento', dateFormat.format(detail.proximoMantenimiento!)),
                  if (detail.ultimoMantenimiento != null)
                    _InfoRow('Último mantenimiento', dateFormat.format(detail.ultimoMantenimiento!)),
                ],
              ),
            ),
          ),
          if (detail.descripcion != null && detail.descripcion!.isNotEmpty) ...[
            const SizedBox(height: 16),
            _Section(title: 'Descripción', child: Text(detail.descripcion!)),
          ],
          if (detail.observaciones != null && detail.observaciones!.isNotEmpty) ...[
            const SizedBox(height: 16),
            _Section(title: 'Observaciones', child: Text(detail.observaciones!)),
          ],
          if (detail.adjuntos.isNotEmpty) ...[
            const SizedBox(height: 16),
            _Section(
              title: 'Adjuntos',
              child: Column(
                children: detail.adjuntos
                    .map((attachment) => _AssetAttachmentTile(attachment: attachment))
                    .toList(),
              ),
            ),
          ],
          if (detail.ordenesAsociadas.isNotEmpty) ...[
            const SizedBox(height: 16),
            _Section(
              title: 'Órdenes asociadas',
              child: Column(
                children: detail.ordenesAsociadas
                    .map((order) => ListTile(
                          leading: const Icon(Icons.assignment_outlined),
                          title: Text(order.numeroRadicado),
                          subtitle: Text(order.estado.toUpperCase()),
                        ))
                    .toList(),
              ),
            ),
          ],
          const SizedBox(height: 24),
        ],
      ),
    );
  }
}

class _AssetAttachmentTile extends StatelessWidget {
  const _AssetAttachmentTile({required this.attachment});

  final AssetAttachment attachment;

  @override
  Widget build(BuildContext context) {
    final uri = Uri.tryParse(attachment.ruta);
    return ListTile(
      leading: const Icon(Icons.attach_file),
      title: Text(attachment.nombre),
      subtitle: attachment.descripcion != null ? Text(attachment.descripcion!) : null,
      trailing: const Icon(Icons.open_in_new),
      onTap: uri == null ? null : () => _launchAttachment(context, uri),
    );
  }

  Future<void> _launchAttachment(BuildContext context, Uri uri) async {
    final target = uri.hasScheme ? uri : Uri.parse('https://roma.osf.com.co/${uri.toString()}');
    if (!await launchUrl(target, mode: LaunchMode.externalApplication)) {
      if (context.mounted) {
        ScaffoldMessenger.of(context)
          ..clearSnackBars()
          ..showSnackBar(const SnackBar(content: Text('No se pudo abrir el adjunto.')));
      }
    }
  }
}

class _Section extends StatelessWidget {
  const _Section({required this.title, required this.child});

  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 8),
        child,
      ],
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow(this.label, this.value);

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        children: [
          Expanded(
            flex: 2,
            child: Text(
              '$label:',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
            ),
          ),
          Expanded(
            flex: 3,
            child: Text(value.isEmpty ? '—' : value),
          ),
        ],
      ),
    );
  }
}
