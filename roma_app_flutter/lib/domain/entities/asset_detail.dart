import 'package:equatable/equatable.dart';

import 'package:roma_app_flutter/domain/entities/asset_attachment.dart';
import 'package:roma_app_flutter/domain/entities/order_summary.dart';

class AssetDetail extends Equatable {
  const AssetDetail({
    required this.id,
    required this.nombre,
    this.codigo,
    this.codigoPatrimonial,
    this.descripcion,
    this.categoria,
    this.ubicacion,
    this.ruta,
    this.estadoActual,
    this.responsable,
    this.areaAsignada,
    this.marca,
    this.modelo,
    this.numeroSerie,
    this.fechaAdquisicion,
    this.valorAdquisicion,
    this.proximoMantenimiento,
    this.ultimoMantenimiento,
    this.observaciones,
    this.fotoPrincipal,
    this.vidaUtilEstimada,
    this.kilometraje,
    this.horasUso,
    this.adjuntos = const [],
    this.ordenesAsociadas = const [],
  });

  final int id;
  final String nombre;
  final String? codigo;
  final String? codigoPatrimonial;
  final String? descripcion;
  final String? categoria;
  final String? ubicacion;
  final String? ruta;
  final String? estadoActual;
  final String? responsable;
  final String? areaAsignada;
  final String? marca;
  final String? modelo;
  final String? numeroSerie;
  final DateTime? fechaAdquisicion;
  final double? valorAdquisicion;
  final DateTime? proximoMantenimiento;
  final DateTime? ultimoMantenimiento;
  final String? observaciones;
  final String? fotoPrincipal;
  final String? vidaUtilEstimada;
  final num? kilometraje;
  final num? horasUso;
  final List<AssetAttachment> adjuntos;
  final List<OrderSummary> ordenesAsociadas;

  @override
  List<Object?> get props => [
        id,
        nombre,
        codigo,
        codigoPatrimonial,
        descripcion,
        categoria,
        ubicacion,
        ruta,
        estadoActual,
        responsable,
        areaAsignada,
        marca,
        modelo,
        numeroSerie,
        fechaAdquisicion,
        valorAdquisicion,
        proximoMantenimiento,
        ultimoMantenimiento,
        observaciones,
        fotoPrincipal,
        vidaUtilEstimada,
        kilometraje,
        horasUso,
        adjuntos,
        ordenesAsociadas,
      ];
}
