import 'package:roma_app_flutter/domain/entities/asset_summary.dart';

class AssetSummaryModel extends AssetSummary {
  const AssetSummaryModel({
    required super.id,
    required super.nombre,
    super.codigo,
    super.fechaObjetivo,
    super.estadoActual,
  });

  factory AssetSummaryModel.fromJson(Map<String, dynamic> json) {
    DateTime? fechaObjetivo;
    final fechaRaw = json['proximo_mantenimiento'] ?? json['fecha_creacion'];
    if (fechaRaw != null) {
      fechaObjetivo = DateTime.tryParse(fechaRaw.toString());
    }

    return AssetSummaryModel(
      id: int.tryParse(json['id_activo']?.toString() ?? '') ?? 0,
      nombre: json['nombre_activo']?.toString() ?? '',
      codigo: json['codigo_interno']?.toString(),
      fechaObjetivo: fechaObjetivo,
      estadoActual: json['estado_actual']?.toString(),
    );
  }
}


