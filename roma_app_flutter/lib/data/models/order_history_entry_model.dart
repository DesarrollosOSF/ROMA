import 'package:roma_app_flutter/domain/entities/order_history_entry.dart';

class OrderHistoryEntryModel extends OrderHistoryEntry {
  const OrderHistoryEntryModel({
    required super.id,
    required super.tipoCambio,
    required super.fecha,
    super.descripcion,
    super.campoAnterior,
    super.valorAnterior,
    super.campoNuevo,
    super.valorNuevo,
    super.usuarioNombre,
    super.usuarioEmail,
    super.rutaEvidencia,
  });

  factory OrderHistoryEntryModel.fromJson(Map<String, dynamic> json) {
    DateTime fecha = DateTime.now();
    final fechaRaw = json['fecha_cambio'] ?? json['fecha'];
    if (fechaRaw != null) {
      fecha = DateTime.tryParse(fechaRaw.toString()) ?? fecha;
    }

    return OrderHistoryEntryModel(
      id: int.tryParse(json['id_historial']?.toString() ?? json['id']?.toString() ?? '') ?? 0,
      tipoCambio: json['tipo_cambio']?.toString() ?? '',
      fecha: fecha,
      descripcion: json['descripcion']?.toString(),
      campoAnterior: json['campo_anterior']?.toString(),
      valorAnterior: json['valor_anterior']?.toString(),
      campoNuevo: json['campo_nuevo']?.toString(),
      valorNuevo: json['valor_nuevo']?.toString(),
      usuarioNombre: json['nombre_usuario']?.toString(),
      usuarioEmail: json['email_usuario']?.toString(),
      rutaEvidencia: json['ruta_evidencia']?.toString(),
    );
  }
}
