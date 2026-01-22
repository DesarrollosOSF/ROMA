import 'package:equatable/equatable.dart';

import 'package:roma_app_flutter/domain/entities/order_detail.dart';
import 'package:roma_app_flutter/domain/entities/order_summary.dart';

class OrderFilters extends Equatable {
  const OrderFilters({
    this.estado,
    this.criticidad,
    this.busqueda,
    this.asignadoA,
    this.solicitante,
  });

  final String? estado;
  final String? criticidad;
  final String? busqueda;
  final int? asignadoA;
  final int? solicitante;

  OrderFilters copyWith({
    String? estado,
    String? criticidad,
    String? busqueda,
    int? asignadoA,
    int? solicitante,
  }) {
    return OrderFilters(
      estado: estado ?? this.estado,
      criticidad: criticidad ?? this.criticidad,
      busqueda: busqueda ?? this.busqueda,
      asignadoA: asignadoA ?? this.asignadoA,
      solicitante: solicitante ?? this.solicitante,
    );
  }

  @override
  List<Object?> get props => [estado, criticidad, busqueda, asignadoA, solicitante];
}

abstract class OrdersRepository {
  Future<List<OrderSummary>> fetchOrders({OrderFilters filters = const OrderFilters()});

  Future<OrderDetail> fetchOrder(int id);
}
