import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';

import 'package:roma_app_flutter/core/di/injector.dart';
import 'package:roma_app_flutter/domain/entities/order_attachment.dart';
import 'package:roma_app_flutter/domain/entities/order_comment.dart';
import 'package:roma_app_flutter/domain/entities/order_detail.dart';
import 'package:roma_app_flutter/domain/entities/order_history_entry.dart';
import 'package:roma_app_flutter/domain/repositories/orders_repository.dart';
import 'package:roma_app_flutter/presentation/bloc/order_detail/order_detail_cubit.dart';
import 'package:roma_app_flutter/presentation/bloc/order_detail/order_detail_state.dart';

class OrderDetailPage extends StatelessWidget {
  const OrderDetailPage({
    super.key,
    required this.orderId,
  });

  final int orderId;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => OrderDetailCubit(injector<OrdersRepository>())..load(orderId),
      child: Scaffold(
        appBar: AppBar(
          title: Text('Orden #$orderId'),
        ),
        body: BlocBuilder<OrderDetailCubit, OrderDetailState>(
          builder: (context, state) {
            switch (state.status) {
              case OrderDetailStatus.initial:
              case OrderDetailStatus.loading:
                return const Center(child: CircularProgressIndicator());
              case OrderDetailStatus.failure:
                return _ErrorView(
                  message: state.message ?? 'No se pudo cargar la orden.',
                  onRetry: () => context.read<OrderDetailCubit>().load(orderId),
                );
              case OrderDetailStatus.success:
                final detail = state.detail!;
                return _OrderDetailView(detail: detail);
            }
          },
        ),
      ),
    );
  }
}

class _OrderDetailView extends StatelessWidget {
  const _OrderDetailView({required this.detail});

  final OrderDetail detail;

  @override
  Widget build(BuildContext context) {
    final dateFormat = DateFormat('dd/MM/yyyy HH:mm');
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
                    detail.summary.numeroRadicado,
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      _InfoChip(
                        icon: Icons.assignment,
                        label: detail.estado.toUpperCase(),
                      ),
                      _InfoChip(
                        icon: Icons.report,
                        label: detail.nivelCriticidad.toUpperCase(),
                      ),
                      _InfoChip(
                        icon: Icons.build_circle_outlined,
                        label: detail.tipoMantenimiento.toUpperCase(),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _InfoRow('Activo', detail.activoNombre ?? 'Sin activo asociado'),
                  if (detail.activoCodigo != null && detail.activoCodigo!.isNotEmpty)
                    _InfoRow('Código interno', detail.activoCodigo!),
                  const SizedBox(height: 8),
                  _InfoRow('Solicitante', detail.solicitante ?? '—'),
                  _InfoRow('Asignado a', detail.asignado ?? '—'),
                  const SizedBox(height: 8),
                  _InfoRow('Creada', dateFormat.format(detail.fechaCreacion)),
                  if (detail.fechaLimite != null)
                    _InfoRow('Fecha límite', dateFormat.format(detail.fechaLimite!)),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          _Section(title: 'Descripción corta', child: Text(detail.descripcionCorta)),
          if (detail.descripcionDetallada != null && detail.descripcionDetallada!.isNotEmpty) ...[
            const SizedBox(height: 16),
            _Section(title: 'Descripción detallada', child: Text(detail.descripcionDetallada!)),
          ],
          if (detail.adjuntos.isNotEmpty) ...[
            const SizedBox(height: 16),
            _Section(
              title: 'Adjuntos',
              child: Column(
                children: detail.adjuntos
                    .map((attachment) => _AttachmentTile(attachment: attachment))
                    .toList(),
              ),
            ),
          ],
          if (detail.comentarios.isNotEmpty) ...[
            const SizedBox(height: 16),
            _Section(
              title: 'Comentarios',
              child: Column(
                children: detail.comentarios
                    .map((comment) => _CommentTile(comment: comment))
                    .toList(),
              ),
            ),
          ],
          if (detail.historial.isNotEmpty) ...[
            const SizedBox(height: 16),
            _Section(
              title: 'Historial de cambios',
              child: Column(
                children: detail.historial
                    .map((entry) => _HistoryTile(entry: entry))
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

class _Section extends StatelessWidget {
  const _Section({required this.title, required this.child});

  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: Theme.of(context).textTheme.titleMedium,
        ),
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
            child: Text(value),
          ),
        ],
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  const _InfoChip({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Chip(
      avatar: Icon(icon, size: 18),
      label: Text(label),
    );
  }
}

class _AttachmentTile extends StatelessWidget {
  const _AttachmentTile({required this.attachment});

  final OrderAttachment attachment;

  @override
  Widget build(BuildContext context) {
    final uri = Uri.tryParse(attachment.ruta);
    return ListTile(
      leading: const Icon(Icons.attach_file),
      title: Text(attachment.nombre),
      subtitle: attachment.descripcion != null && attachment.descripcion!.isNotEmpty
          ? Text(attachment.descripcion!)
          : null,
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
          ..showSnackBar(
            const SnackBar(content: Text('No se pudo abrir el adjunto.')),
          );
      }
    }
  }
}

class _CommentTile extends StatelessWidget {
  const _CommentTile({required this.comment});

  final OrderComment comment;

  @override
  Widget build(BuildContext context) {
    final dateFormat = DateFormat('dd/MM/yyyy HH:mm');
    return ListTile(
      leading: const Icon(Icons.chat_bubble_outline),
      title: Text(comment.usuarioNombre ?? 'Usuario'),
      subtitle: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(comment.comentario),
          const SizedBox(height: 4),
          Text(dateFormat.format(comment.fecha), style: Theme.of(context).textTheme.bodySmall),
        ],
      ),
    );
  }
}

class _HistoryTile extends StatelessWidget {
  const _HistoryTile({required this.entry});

  final OrderHistoryEntry entry;

  @override
  Widget build(BuildContext context) {
    final dateFormat = DateFormat('dd/MM/yyyy HH:mm');
    return ListTile(
      leading: const Icon(Icons.timeline_outlined),
      title: Text(entry.tipoCambio.toUpperCase()),
      subtitle: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (entry.descripcion != null && entry.descripcion!.isNotEmpty)
            Text(entry.descripcion!),
          if (entry.campoAnterior != null || entry.campoNuevo != null)
            Text(
              '${entry.campoAnterior ?? ''} → ${entry.campoNuevo ?? ''}'.trim(),
              style: Theme.of(context).textTheme.bodySmall,
            ),
          if (entry.valorAnterior != null || entry.valorNuevo != null)
            Text(
              '${entry.valorAnterior ?? ''} → ${entry.valorNuevo ?? ''}'.trim(),
              style: Theme.of(context).textTheme.bodySmall,
            ),
          Text(dateFormat.format(entry.fecha), style: Theme.of(context).textTheme.bodySmall),
          if (entry.rutaEvidencia != null)
            TextButton.icon(
              onPressed: () {
                final uri = Uri.tryParse(entry.rutaEvidencia!);
                if (uri != null) {
                  final target = uri.hasScheme
                      ? uri
                      : Uri.parse('https://roma.osf.com.co/${uri.toString()}');
                  launchUrl(target, mode: LaunchMode.externalApplication);
                }
              },
              icon: const Icon(Icons.image_outlined),
              label: const Text('Ver evidencia'),
            ),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  const _ErrorView({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 64),
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
