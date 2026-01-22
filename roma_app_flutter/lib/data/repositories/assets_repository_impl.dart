import 'package:roma_app_flutter/data/datasources/assets_remote_datasource.dart';
import 'package:roma_app_flutter/domain/entities/asset_detail.dart';
import 'package:roma_app_flutter/domain/entities/asset_summary.dart';
import 'package:roma_app_flutter/domain/repositories/assets_repository.dart';

class AssetsRepositoryImpl implements AssetsRepository {
  AssetsRepositoryImpl(this._remoteDataSource);

  final AssetsRemoteDataSource _remoteDataSource;

  @override
  Future<List<AssetSummary>> fetchAssets({AssetFilters filters = const AssetFilters()}) async {
    final models = await _remoteDataSource.fetchAssets(
      categoria: filters.categoria,
      estado: filters.estado,
      ubicacion: filters.ubicacion,
      busqueda: filters.busqueda,
    );
    return models;
  }

  @override
  Future<AssetDetail> fetchAsset(int id) async {
    final model = await _remoteDataSource.fetchAsset(id);
    return model;
  }
}
