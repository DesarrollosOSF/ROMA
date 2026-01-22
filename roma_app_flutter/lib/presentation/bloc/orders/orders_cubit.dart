import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:roma_app_flutter/core/errors/app_exception.dart';
import 'package:roma_app_flutter/domain/entities/auth_session.dart';
import 'package:roma_app_flutter/domain/repositories/orders_repository.dart';
import 'package:roma_app_flutter/presentation/bloc/orders/orders_state.dart';

class OrdersCubit extends Cubit<OrdersState> {
  OrdersCubit({
    required OrdersRepository repository,
    required AuthSession session,
  })  : _repository = repository,
        _session = session,
        super(const OrdersState());

  final OrdersRepository _repository;
  final AuthSession _session;

  Future<void> loadOrders({bool showLoading = true}) async {
    final effectiveFilters = _applyMineFilter(state.filters, state.onlyMine);
    if (showLoading) {
      emit(state.copyWith(status: OrdersStatus.loading, filters: effectiveFilters, message: null));
    }
    try {
      final orders = await _repository.fetchOrders(filters: effectiveFilters);
      emit(state.copyWith(
        status: OrdersStatus.success,
        orders: orders,
        filters: effectiveFilters,
        message: null,
      ));
    } on AppException catch (error) {
      emit(state.copyWith(
        status: OrdersStatus.failure,
        message: error.message,
      ));
    } catch (_) {
      emit(state.copyWith(
        status: OrdersStatus.failure,
        message: 'No fue posible cargar las órdenes. Intenta nuevamente.',
      ));
    }
  }

  void changeEstado(String? estado) {
    final normalized = (estado == null || estado.isEmpty || estado == 'todos') ? null : estado;
    emit(state.copyWith(filters: state.filters.copyWith(estado: normalized)));
    loadOrders();
  }

  void changeCriticidad(String? criticidad) {
    final normalized = (criticidad == null || criticidad.isEmpty || criticidad == 'todas') ? null : criticidad;
    emit(state.copyWith(filters: state.filters.copyWith(criticidad: normalized)));
    loadOrders();
  }

  void search(String? query) {
    final normalized = (query == null || query.trim().isEmpty) ? null : query.trim();
    emit(state.copyWith(filters: state.filters.copyWith(busqueda: normalized)));
    loadOrders(showLoading: normalized != null);
  }

  void toggleOnlyMine() {
    final newValue = !state.onlyMine;
    emit(state.copyWith(onlyMine: newValue));
    loadOrders();
  }

  void refresh() {
    loadOrders(showLoading: true);
  }

  OrderFilters _applyMineFilter(OrderFilters filters, bool onlyMine) {
    if (!onlyMine) {
      return filters.copyWith(asignadoA: null, solicitante: null);
    }

    final role = _session.user.rol.toLowerCase();
    if (role == 'operario') {
      return filters.copyWith(asignadoA: _session.user.id, solicitante: null);
    }
    if (role == 'jefe' || role == 'director') {
      return filters.copyWith(solicitante: _session.user.id, asignadoA: null);
    }
    // Administrador u otros pueden ver todo; mantener filtros actuales.
    return filters;
  }
}
