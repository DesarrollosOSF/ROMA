import 'package:roma_app_flutter/data/datasources/orders_remote_datasource.dart';
import 'package:roma_app_flutter/domain/entities/order_detail.dart';
import 'package:roma_app_flutter/domain/entities/order_summary.dart';
import 'package:roma_app_flutter/domain/repositories/orders_repository.dart';

class OrdersRepositoryImpl implements OrdersRepository {
  OrdersRepositoryImpl(this._remoteDataSource);

  final OrdersRemoteDataSource _remoteDataSource;

  @override
  Future<List<OrderSummary>> fetchOrders({OrderFilters filters = const OrderFilters()}) async {
    final models = await _remoteDataSource.fetchOrders(
      estado: filters.estado,
      criticidad: filters.criticidad,
      busqueda: filters.busqueda,
      asignadoA: filters.asignadoA,
      solicitante: filters.solicitante,
    );
    return models;
  }

  @override
  Future<OrderDetail> fetchOrder(int id) async {
    final model = await _remoteDataSource.fetchOrder(id);
    return model;
  }
}
