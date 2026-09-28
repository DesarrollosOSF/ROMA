import 'package:equatable/equatable.dart';

import 'package:roma_app_flutter/domain/entities/order_attachment.dart';
import 'package:roma_app_flutter/domain/entities/order_comment.dart';
import 'package:roma_app_flutter/domain/entities/order_history_entry.dart';
import 'package:roma_app_flutter/domain/entities/order_summary.dart';

class OrderDetail extends Equatable {
  const OrderDetail({
    required this.summary,
    required this.descripcionCorta,
    required this.tipoMantenimiento,
    required this.estado,
    required this.nivelCriticidad,
    required this.fechaCreacion,
    this.descripcionDetallada,
    this.fechaLimite,
    this.solicitante,
    this.solicitanteEmail,
    this.asignado,
    this.asignadoEmail,
    this.activoId,
    this.activoNombre,
    this.activoCodigo,
    this.fotoActivo,
    this.adjuntos = const [],
    this.comentarios = const [],
    this.historial = const [],
  });

  final OrderSummary summary;
  final String descripcionCorta;
  final String? descripcionDetallada;
  final String tipoMantenimiento;
  final String estado;
  final String nivelCriticidad;
  final DateTime fechaCreacion;
  final DateTime? fechaLimite;
  final String? solicitante;
  final String? solicitanteEmail;
  final String? asignado;
  final String? asignadoEmail;
  final int? activoId;
  final String? activoNombre;
  final String? activoCodigo;
  final String? fotoActivo;
  final List<OrderAttachment> adjuntos;
  final List<OrderComment> comentarios;
  final List<OrderHistoryEntry> historial;

  @override
  List<Object?> get props => [
        summary,
        descripcionCorta,
        descripcionDetallada,
        tipoMantenimiento,
        estado,
        nivelCriticidad,
        fechaCreacion,
        fechaLimite,
        solicitante,
        solicitanteEmail,
        asignado,
        asignadoEmail,
        activoId,
        activoNombre,
        activoCodigo,
        fotoActivo,
        adjuntos,
        comentarios,
        historial,
      ];
}
