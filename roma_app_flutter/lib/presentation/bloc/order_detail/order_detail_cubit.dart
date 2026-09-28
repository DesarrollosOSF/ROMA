import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:roma_app_flutter/core/errors/app_exception.dart';
import 'package:roma_app_flutter/domain/repositories/orders_repository.dart';
import 'package:roma_app_flutter/presentation/bloc/order_detail/order_detail_state.dart';

class OrderDetailCubit extends Cubit<OrderDetailState> {
  OrderDetailCubit(this._repository) : super(const OrderDetailState());

  final OrdersRepository _repository;

  Future<void> load(int id) async {
    emit(state.copyWith(status: OrderDetailStatus.loading, message: null));
    try {
      final detail = await _repository.fetchOrder(id);
      emit(state.copyWith(status: OrderDetailStatus.success, detail: detail, message: null));
    } on AppException catch (error) {
      emit(state.copyWith(status: OrderDetailStatus.failure, message: error.message));
    } catch (_) {
      emit(state.copyWith(
        status: OrderDetailStatus.failure,
        message: 'No fue posible obtener el detalle de la orden.',
      ));
    }
  }
}
