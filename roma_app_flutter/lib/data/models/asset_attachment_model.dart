import 'package:roma_app_flutter/domain/entities/asset_attachment.dart';

class AssetAttachmentModel extends AssetAttachment {
  const AssetAttachmentModel({
    required super.id,
    required super.nombre,
    required super.ruta,
    super.tipoMime,
    super.tamanoBytes,
    super.descripcion,
    super.fechaSubida,
  });

  factory AssetAttachmentModel.fromJson(Map<String, dynamic> json) {
    DateTime? fechaSubida;
    final fechaRaw = json['fecha_subida'] ?? json['fecha'];
    if (fechaRaw != null) {
      fechaSubida = DateTime.tryParse(fechaRaw.toString());
    }

    return AssetAttachmentModel(
      id: int.tryParse(json['id_adjunto']?.toString() ?? json['id']?.toString() ?? '') ?? 0,
      nombre: json['nombre_archivo']?.toString() ?? json['nombre']?.toString() ?? '',
      ruta: json['ruta_archivo']?.toString() ?? json['ruta']?.toString() ?? '',
      tipoMime: json['tipo_archivo']?.toString() ?? json['mime']?.toString(),
      tamanoBytes: int.tryParse(json['tamaño_archivo']?.toString() ?? json['size']?.toString() ?? ''),
      descripcion: json['descripcion']?.toString(),
      fechaSubida: fechaSubida,
    );
  }
}
