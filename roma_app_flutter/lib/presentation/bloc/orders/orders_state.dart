import 'package:equatable/equatable.dart';

import 'package:roma_app_flutter/domain/entities/order_summary.dart';
import 'package:roma_app_flutter/domain/repositories/orders_repository.dart';

enum OrdersStatus { initial, loading, success, failure }

class OrdersState extends Equatable {
  const OrdersState({
    this.status = OrdersStatus.initial,
    this.orders = const [],
    this.filters = const OrderFilters(),
    this.onlyMine = false,
    this.message,
  });

  final OrdersStatus status;
  final List<OrderSummary> orders;
  final OrderFilters filters;
  final bool onlyMine;
  final String? message;

  OrdersState copyWith({
    OrdersStatus? status,
    List<OrderSummary>? orders,
    OrderFilters? filters,
    bool? onlyMine,
    String? message,
  }) {
    return OrdersState(
      status: status ?? this.status,
      orders: orders ?? this.orders,
      filters: filters ?? this.filters,
      onlyMine: onlyMine ?? this.onlyMine,
      message: message,
    );
  }

  @override
  List<Object?> get props => [status, orders, filters, onlyMine, message];
}
