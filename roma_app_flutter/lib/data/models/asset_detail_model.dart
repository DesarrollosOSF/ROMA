import 'package:roma_app_flutter/data/models/asset_attachment_model.dart';
import 'package:roma_app_flutter/data/models/order_summary_model.dart';
import 'package:roma_app_flutter/domain/entities/asset_detail.dart';

class AssetDetailModel extends AssetDetail {
  const AssetDetailModel({
    required super.id,
    required super.nombre,
    super.codigo,
    super.codigoPatrimonial,
    super.descripcion,
    super.categoria,
    super.ubicacion,
    super.ruta,
    super.estadoActual,
    super.responsable,
    super.areaAsignada,
    super.marca,
    super.modelo,
    super.numeroSerie,
    super.fechaAdquisicion,
    super.valorAdquisicion,
    super.proximoMantenimiento,
    super.ultimoMantenimiento,
    super.observaciones,
    super.fotoPrincipal,
    super.vidaUtilEstimada,
    super.kilometraje,
    super.horasUso,
    super.adjuntos,
    super.ordenesAsociadas,
  });

  factory AssetDetailModel.fromJson(Map<String, dynamic> json) {
    DateTime? parseDate(dynamic value) {
      if (value == null) return null;
      return DateTime.tryParse(value.toString());
    }

    double? parseDouble(dynamic value) {
      if (value == null) return null;
      return double.tryParse(value.toString());
    }

    num? parseNum(dynamic value) {
      if (value == null) return null;
      return num.tryParse(value.toString());
    }

    final attachmentsRaw = json['adjuntos'] as List<dynamic>? ?? const [];
    final ordersRaw = json['ordenes'] as List<dynamic>? ??
        json['ordenes_asociadas'] as List<dynamic>? ?? const [];

    return AssetDetailModel(
      id: int.tryParse(json['id_activo']?.toString() ?? '') ?? 0,
      nombre: json['nombre_activo']?.toString() ?? '',
      codigo: json['codigo_interno']?.toString(),
      codigoPatrimonial: json['codigo_patrimonial']?.toString(),
      descripcion: json['descripcion_general']?.toString(),
      categoria: json['categoria']?.toString(),
      ubicacion: json['ubicacion']?.toString(),
      ruta: json['ruta']?.toString(),
      estadoActual: json['estado_actual']?.toString(),
      responsable: json['responsable']?.toString(),
      areaAsignada: json['area_asignada']?.toString(),
      marca: json['marca']?.toString(),
      modelo: json['modelo']?.toString(),
      numeroSerie: json['numero_serie']?.toString(),
      fechaAdquisicion: parseDate(json['fecha_adquisicion']),
      valorAdquisicion: parseDouble(json['valor_adquisicion']),
      proximoMantenimiento: parseDate(json['proximo_mantenimiento']),
      ultimoMantenimiento: parseDate(json['ultimo_mantenimiento']),
      observaciones: json['observaciones']?.toString(),
      fotoPrincipal: json['foto_principal']?.toString(),
      vidaUtilEstimada: json['vida_util_estimada']?.toString(),
      kilometraje: parseNum(json['kilometraje']),
      horasUso: parseNum(json['horas_uso']),
      adjuntos: attachmentsRaw
          .map((item) => AssetAttachmentModel.fromJson(
                Map<String, dynamic>.from(item as Map),
              ))
          .toList(),
      ordenesAsociadas: ordersRaw
          .map((item) => OrderSummaryModel.fromJson(
                Map<String, dynamic>.from(item as Map),
              ))
          .toList(),
    );
  }
}
