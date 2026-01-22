import 'package:equatable/equatable.dart';

import 'package:roma_app_flutter/domain/entities/order_detail.dart';

enum OrderDetailStatus { initial, loading, success, failure }

class OrderDetailState extends Equatable {
  const OrderDetailState({
    this.status = OrderDetailStatus.initial,
    this.detail,
    this.message,
  });

  final OrderDetailStatus status;
  final OrderDetail? detail;
  final String? message;

  OrderDetailState copyWith({
    OrderDetailStatus? status,
    OrderDetail? detail,
    String? message,
  }) {
    return OrderDetailState(
      status: status ?? this.status,
      detail: detail ?? this.detail,
      message: message,
    );
  }

  @override
  List<Object?> get props => [status, detail, message];
}
