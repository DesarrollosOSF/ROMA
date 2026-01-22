import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:roma_app_flutter/core/errors/app_exception.dart';
import 'package:roma_app_flutter/domain/repositories/assets_repository.dart';
import 'package:roma_app_flutter/presentation/bloc/asset_detail/asset_detail_state.dart';

class AssetDetailCubit extends Cubit<AssetDetailState> {
  AssetDetailCubit(this._repository) : super(const AssetDetailState());

  final AssetsRepository _repository;

  Future<void> load(int id) async {
    emit(state.copyWith(status: AssetDetailStatus.loading, message: null));
    try {
      final detail = await _repository.fetchAsset(id);
      emit(state.copyWith(status: AssetDetailStatus.success, detail: detail, message: null));
    } on AppException catch (error) {
      emit(state.copyWith(status: AssetDetailStatus.failure, message: error.message));
    } catch (_) {
      emit(state.copyWith(
        status: AssetDetailStatus.failure,
        message: 'No fue posible obtener el detalle del activo.',
      ));
    }
  }
}
