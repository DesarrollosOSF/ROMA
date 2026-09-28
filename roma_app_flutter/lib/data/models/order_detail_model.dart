import 'package:roma_app_flutter/data/models/order_attachment_model.dart';
import 'package:roma_app_flutter/data/models/order_comment_model.dart';
import 'package:roma_app_flutter/data/models/order_history_entry_model.dart';
import 'package:roma_app_flutter/data/models/order_summary_model.dart';
import 'package:roma_app_flutter/domain/entities/order_detail.dart';

class OrderDetailModel extends OrderDetail {
  const OrderDetailModel({
    required super.summary,
    required super.descripcionCorta,
    required super.tipoMantenimiento,
    required super.estado,
    required super.nivelCriticidad,
    required super.fechaCreacion,
    super.descripcionDetallada,
    super.fechaLimite,
    super.solicitante,
    super.solicitanteEmail,
    super.asignado,
    super.asignadoEmail,
    super.activoId,
    super.activoNombre,
    super.activoCodigo,
    super.fotoActivo,
    super.adjuntos,
    super.comentarios,
    super.historial,
  });

  factory OrderDetailModel.fromJson(Map<String, dynamic> json) {
    final summary = OrderSummaryModel.fromJson(json);
    final adjuntosRaw = json['adjuntos'] as List<dynamic>? ?? const [];
    final comentariosRaw = json['comentarios'] as List<dynamic>? ?? const [];
    final historialRaw = json['historial'] as List<dynamic>? ?? const [];

    return OrderDetailModel(
      summary: summary,
      descripcionCorta: json['descripcion_corta']?.toString() ?? '',
      descripcionDetallada: json['descripcion_detallada']?.toString(),
      tipoMantenimiento: json['tipo_mantenimiento']?.toString() ?? '',
      estado: json['estado_proceso']?.toString() ?? '',
      nivelCriticidad: json['nivel_criticidad']?.toString() ?? '',
      fechaCreacion: summary.fechaCreacion,
      fechaLimite: summary.fechaLimite,
      solicitante: json['nombre_solicitante']?.toString(),
      solicitanteEmail: json['email_solicitante']?.toString(),
      asignado: json['nombre_asignado']?.toString(),
      asignadoEmail: json['email_asignado']?.toString(),
      activoId: int.tryParse(json['id_activo']?.toString() ?? ''),
      activoNombre: json['nombre_activo']?.toString(),
      activoCodigo: json['codigo_interno']?.toString(),
      fotoActivo: json['foto_principal']?.toString(),
      adjuntos: adjuntosRaw
          .map((item) => OrderAttachmentModel.fromJson(
                (item as Map).cast<String, dynamic>(),
              ))
          .toList(),
      comentarios: comentariosRaw
          .map((item) => OrderCommentModel.fromJson(
                (item as Map).cast<String, dynamic>(),
              ))
          .toList(),
      historial: historialRaw
          .map((item) => OrderHistoryEntryModel.fromJson(
                (item as Map).cast<String, dynamic>(),
              ))
          .toList(),
    );
  }
}
