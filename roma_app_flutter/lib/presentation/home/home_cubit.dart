import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:roma_app_flutter/presentation/home/home_state.dart';

class HomeCubit extends Cubit<HomeState> {
  HomeCubit() : super(const HomeState());

  void select(HomeSection section) {
    if (state.section == section) {
      return;
    }
    emit(state.copyWith(section: section));
  }
}
