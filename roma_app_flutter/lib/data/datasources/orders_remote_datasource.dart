import 'package:dio/dio.dart';

import 'package:roma_app_flutter/core/errors/app_exception.dart';
import 'package:roma_app_flutter/core/network/network_exception.dart';
import 'package:roma_app_flutter/data/models/order_detail_model.dart';
import 'package:roma_app_flutter/data/models/order_summary_model.dart';

abstract class OrdersRemoteDataSource {
  Future<List<OrderSummaryModel>> fetchOrders({
    String? estado,
    String? criticidad,
    String? busqueda,
    int? asignadoA,
    int? solicitante,
  });

  Future<OrderDetailModel> fetchOrder(int id);
}

class OrdersRemoteDataSourceImpl implements OrdersRemoteDataSource {
  OrdersRemoteDataSourceImpl(this._dio);

  final Dio _dio;

  @override
  Future<List<OrderSummaryModel>> fetchOrders({
    String? estado,
    String? criticidad,
    String? busqueda,
    int? asignadoA,
    int? solicitante,
  }) async {
    try {
      final query = <String, dynamic>{};
      if (estado != null && estado.isNotEmpty) {
        query['estado'] = estado;
      }
      if (criticidad != null && criticidad.isNotEmpty) {
        query['criticidad'] = criticidad;
      }
      if (busqueda != null && busqueda.isNotEmpty) {
        query['busqueda'] = busqueda;
      }
      if (asignadoA != null) {
        query['asignado_a'] = asignadoA;
      }
      if (solicitante != null) {
        query['solicitante'] = solicitante;
      }

      final response = await _dio.get<Map<String, dynamic>>(
        '/orders',
        queryParameters: query.isEmpty ? null : query,
      );

      final data = response.data ?? {};
      if (data['success'] != true) {
        throw AppException(data['message']?.toString() ?? 'No se pudieron obtener las órdenes.');
      }

      final rawList = data['data'] as List<dynamic>? ?? const [];
      return rawList
          .map((item) => OrderSummaryModel.fromJson(
                Map<String, dynamic>.from(item as Map),
              ))
          .toList();
    } on DioException catch (error) {
      throw NetworkException.fromDioError(error);
    }
  }

  @override
  Future<OrderDetailModel> fetchOrder(int id) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>('/orders/$id');
      final data = response.data ?? {};
      if (data['success'] != true) {
        throw AppException(data['message']?.toString() ?? 'No se pudo obtener la orden.');
      }

      final orderJson = data['data'] as Map<String, dynamic>? ?? const {};
      return OrderDetailModel.fromJson(orderJson);
    } on DioException catch (error) {
      throw NetworkException.fromDioError(error);
    }
  }
}
