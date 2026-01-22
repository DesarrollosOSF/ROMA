import 'package:dio/dio.dart';

import 'package:roma_app_flutter/core/errors/app_exception.dart';
import 'package:roma_app_flutter/core/network/network_exception.dart';
import 'package:roma_app_flutter/data/models/dashboard_data_model.dart';

abstract class DashboardRemoteDataSource {
  Future<DashboardDataModel> fetchDashboard();
}

class DashboardRemoteDataSourceImpl implements DashboardRemoteDataSource {
  DashboardRemoteDataSourceImpl(this._dio);

  final Dio _dio;

  @override
  Future<DashboardDataModel> fetchDashboard() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        '/dashboard',
        queryParameters: {
          'include': 'estadisticas,ordenes_recientes,ordenes_criticas,mantenimientos_proximos',
        },
      );

      final data = response.data ?? {};
      if (data['success'] != true) {
        throw AppException(data['message']?.toString() ?? 'No se pudo obtener el dashboard.');
      }

      final payload = data['data'] as Map<String, dynamic>? ?? const {};
      return DashboardDataModel.fromJson(payload);
    } on DioException catch (error) {
      throw NetworkException.fromDioError(error);
    }
  }
}


