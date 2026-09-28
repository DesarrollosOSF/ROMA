import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:roma_app_flutter/core/errors/app_exception.dart';
import 'package:roma_app_flutter/domain/repositories/dashboard_repository.dart';
import 'package:roma_app_flutter/presentation/bloc/dashboard/dashboard_state.dart';

class DashboardCubit extends Cubit<DashboardState> {
  DashboardCubit(this._dashboardRepository) : super(const DashboardState.initial());

  final DashboardRepository _dashboardRepository;

  Future<void> loadDashboard() async {
    emit(state.copyWith(status: DashboardStatus.loading, message: null));
    try {
      final dashboard = await _dashboardRepository.fetchDashboard();
      emit(
        state.copyWith(
          status: DashboardStatus.success,
          data: dashboard,
          message: null,
        ),
      );
    } on AppException catch (error) {
      emit(
        state.copyWith(
          status: DashboardStatus.failure,
          message: error.message,
        ),
      );
    } catch (_) {
      emit(
        state.copyWith(
          status: DashboardStatus.failure,
          message: 'No fue posible cargar el dashboard. Intente nuevamente.',
        ),
      );
    }
  }

  Future<void> refresh() async {
    await loadDashboard();
  }
}


