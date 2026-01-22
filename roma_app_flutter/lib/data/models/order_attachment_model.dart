import 'package:roma_app_flutter/domain/entities/order_attachment.dart';

class OrderAttachmentModel extends OrderAttachment {
  const OrderAttachmentModel({
    required super.id,
    required super.nombre,
    required super.ruta,
    super.tipoMime,
    super.tamanoBytes,
    super.descripcion,
    super.fechaSubida,
    super.usuario,
  });

  factory OrderAttachmentModel.fromJson(Map<String, dynamic> json) {
    DateTime? fechaSubida;
    final fechaRaw = json['fecha_subida'] ?? json['fecha'];
    if (fechaRaw != null) {
      fechaSubida = DateTime.tryParse(fechaRaw.toString());
    }

    return OrderAttachmentModel(
      id: int.tryParse(json['id_adjunto']?.toString() ?? '') ?? 0,
      nombre: json['nombre_archivo']?.toString() ?? json['nombre']?.toString() ?? '',
      ruta: json['ruta_archivo']?.toString() ?? json['ruta']?.toString() ?? '',
      tipoMime: json['tipo_archivo']?.toString() ?? json['mime']?.toString(),
      tamanoBytes: int.tryParse(json['tamaño_archivo']?.toString() ?? json['size']?.toString() ?? ''),
      descripcion: json['descripcion']?.toString(),
      fechaSubida: fechaSubida,
      usuario: json['usuario_subida']?.toString() ?? json['usuario']?.toString(),
    );
  }
}
