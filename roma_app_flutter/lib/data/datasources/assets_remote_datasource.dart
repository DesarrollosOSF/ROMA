import 'package:dio/dio.dart';

import 'package:roma_app_flutter/core/errors/app_exception.dart';
import 'package:roma_app_flutter/core/network/network_exception.dart';
import 'package:roma_app_flutter/data/models/asset_detail_model.dart';
import 'package:roma_app_flutter/data/models/asset_summary_model.dart';

abstract class AssetsRemoteDataSource {
  Future<List<AssetSummaryModel>> fetchAssets({
    String? categoria,
    String? estado,
    String? ubicacion,
    String? busqueda,
  });

  Future<AssetDetailModel> fetchAsset(int id);
}

class AssetsRemoteDataSourceImpl implements AssetsRemoteDataSource {
  AssetsRemoteDataSourceImpl(this._dio);

  final Dio _dio;

  @override
  Future<List<AssetSummaryModel>> fetchAssets({
    String? categoria,
    String? estado,
    String? ubicacion,
    String? busqueda,
  }) async {
    try {
      final query = <String, dynamic>{};
      if (categoria != null && categoria.isNotEmpty) {
        query['categoria'] = categoria;
      }
      if (estado != null && estado.isNotEmpty) {
        query['estado'] = estado;
      }
      if (ubicacion != null && ubicacion.isNotEmpty) {
        query['ubicacion'] = ubicacion;
      }
      if (busqueda != null && busqueda.isNotEmpty) {
        query['busqueda'] = busqueda;
      }

      final response = await _dio.get<Map<String, dynamic>>(
        '/assets',
        queryParameters: query.isEmpty ? null : query,
      );

      final data = response.data ?? {};
      if (data['success'] != true) {
        throw AppException(data['message']?.toString() ?? 'No se pudieron obtener los activos.');
      }

      final rawList = data['data'] as List<dynamic>? ?? const [];
      return rawList
          .map((item) => AssetSummaryModel.fromJson(
                Map<String, dynamic>.from(item as Map),
              ))
          .toList();
    } on DioException catch (error) {
      throw NetworkException.fromDioError(error);
    }
  }

  @override
  Future<AssetDetailModel> fetchAsset(int id) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>('/assets/$id');
      final data = response.data ?? {};
      if (data['success'] != true) {
        throw AppException(data['message']?.toString() ?? 'No se pudo obtener el activo.');
      }

      final assetJson = data['data'] as Map<String, dynamic>? ?? const {};
      return AssetDetailModel.fromJson(assetJson);
    } on DioException catch (error) {
      throw NetworkException.fromDioError(error);
    }
  }
}
