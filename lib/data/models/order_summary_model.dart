import 'package:roma_app_flutter/domain/entities/order_summary.dart';

class OrderSummaryModel extends OrderSummary {
  OrderSummaryModel({
    required super.id,
    required super.numeroRadicado,
    required super.estado,
    required super.nivelCriticidad,
    required super.fechaCreacion,
    super.fechaLimite,
    super.nombreActivo,
    super.codigoActivo,
  });

  factory OrderSummaryModel.fromJson(Map<String, dynamic> json) {
    DateTime? parseDate(dynamic value) {
      if (value == null) {
        return null;
      }
      if (value is int) {
        return DateTime.fromMillisecondsSinceEpoch(value);
      }
      final stringValue = value.toString();
      if (stringValue.isEmpty) {
        return null;
      }
      final parsed = DateTime.tryParse(stringValue);
      if (parsed != null) {
        return parsed;
      }
      return null;
    }

    return OrderSummaryModel(
      id: int.tryParse(json['id_orden']?.toString() ?? '') ?? 0,
      numeroRadicado: json['numero_radicado']?.toString() ?? '',
      estado: json['estado_proceso']?.toString() ?? '',
      nivelCriticidad: json['nivel_criticidad']?.toString() ?? '',
      fechaCreacion: parseDate(json['fecha_creacion']) ?? DateTime.now(),
      fechaLimite: parseDate(json['fecha_limite_ejecucion']),
      nombreActivo: json['nombre_activo']?.toString(),
      codigoActivo: json['codigo_interno']?.toString(),
    );
  }
}


