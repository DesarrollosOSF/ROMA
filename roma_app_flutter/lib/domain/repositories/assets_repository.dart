import 'package:equatable/equatable.dart';

import 'package:roma_app_flutter/domain/entities/asset_detail.dart';
import 'package:roma_app_flutter/domain/entities/asset_summary.dart';

class AssetFilters extends Equatable {
  const AssetFilters({
    this.categoria,
    this.estado,
    this.ubicacion,
    this.busqueda,
  });

  final String? categoria;
  final String? estado;
  final String? ubicacion;
  final String? busqueda;

  AssetFilters copyWith({
    String? categoria,
    String? estado,
    String? ubicacion,
    String? busqueda,
  }) {
    return AssetFilters(
      categoria: categoria ?? this.categoria,
      estado: estado ?? this.estado,
      ubicacion: ubicacion ?? this.ubicacion,
      busqueda: busqueda ?? this.busqueda,
    );
  }

  @override
  List<Object?> get props => [categoria, estado, ubicacion, busqueda];
}

abstract class AssetsRepository {
  Future<List<AssetSummary>> fetchAssets({AssetFilters filters = const AssetFilters()});

  Future<AssetDetail> fetchAsset(int id);
}
