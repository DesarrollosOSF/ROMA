import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:roma_app_flutter/core/errors/app_exception.dart';
import 'package:roma_app_flutter/domain/repositories/assets_repository.dart';
import 'package:roma_app_flutter/presentation/bloc/assets/assets_state.dart';

class AssetsCubit extends Cubit<AssetsState> {
  AssetsCubit({required AssetsRepository repository})
      : _repository = repository,
        super(const AssetsState());

  final AssetsRepository _repository;

  Future<void> loadAssets({bool showLoading = true}) async {
    if (showLoading) {
      emit(state.copyWith(status: AssetsStatus.loading, message: null));
    }
    try {
      final assets = await _repository.fetchAssets(filters: state.filters);
      emit(state.copyWith(status: AssetsStatus.success, assets: assets, message: null));
    } on AppException catch (error) {
      emit(state.copyWith(status: AssetsStatus.failure, message: error.message));
    } catch (_) {
      emit(state.copyWith(
        status: AssetsStatus.failure,
        message: 'No fue posible cargar los activos. Intenta nuevamente.',
      ));
    }
  }

  void changeCategoria(String? categoria) {
    final normalized = (categoria == null || categoria.isEmpty || categoria == 'todas') ? null : categoria;
    emit(state.copyWith(filters: state.filters.copyWith(categoria: normalized)));
    loadAssets();
  }

  void changeEstado(String? estado) {
    final normalized = (estado == null || estado.isEmpty || estado == 'todos') ? null : estado;
    emit(state.copyWith(filters: state.filters.copyWith(estado: normalized)));
    loadAssets();
  }

  void changeUbicacion(String? ubicacion) {
    final normalized = (ubicacion == null || ubicacion.isEmpty) ? null : ubicacion;
    emit(state.copyWith(filters: state.filters.copyWith(ubicacion: normalized)));
    loadAssets();
  }

  void search(String? query) {
    final normalized = (query == null || query.trim().isEmpty) ? null : query.trim();
    emit(state.copyWith(filters: state.filters.copyWith(busqueda: normalized)));
    loadAssets(showLoading: normalized != null);
  }

  void refresh() {
    loadAssets(showLoading: true);
  }
}
