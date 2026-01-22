import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:roma_app_flutter/core/errors/app_exception.dart';
import 'package:roma_app_flutter/domain/entities/auth_session.dart';
import 'package:roma_app_flutter/domain/repositories/auth_repository.dart';
import 'package:roma_app_flutter/presentation/bloc/auth/auth_state.dart';

class AuthCubit extends Cubit<AuthState> {
  AuthCubit(this._authRepository) : super(const AuthState.initial());

  final AuthRepository _authRepository;

  Future<void> checkAuth() async {
    emit(state.copyWith(status: AuthStatus.checking, message: null));
    try {
      final session = await _authRepository.getSession();
      if (session == null) {
        emit(state.copyWith(status: AuthStatus.unauthenticated, session: null));
        return;
      }

      final valid = await _authRepository.ping();
      if (!valid) {
        await _authRepository.logout();
        emit(state.copyWith(
          status: AuthStatus.unauthenticated,
          session: null,
          message: 'Tu sesión ha expirado. Inicia sesión nuevamente.',
        ));
        return;
      }

      emit(state.copyWith(
        status: AuthStatus.authenticated,
        session: session,
        message: null,
      ));
    } on AppException catch (error) {
      emit(state.copyWith(
        status: AuthStatus.failure,
        session: null,
        message: error.message,
      ));
    } catch (_) {
      emit(state.copyWith(
        status: AuthStatus.failure,
        session: null,
        message: 'No pudimos validar tu sesión. Intenta más tarde.',
      ));
    }
  }

  Future<void> login(String email, String password) async {
    emit(state.copyWith(status: AuthStatus.loading, message: null));
    try {
      final session = await _authRepository.login(email: email, password: password);
      emit(state.copyWith(
        status: AuthStatus.authenticated,
        session: session,
        message: null,
      ));
    } on AppException catch (error) {
      emit(state.copyWith(
        status: AuthStatus.failure,
        session: null,
        message: error.message,
      ));
      emit(state.copyWith(
        status: AuthStatus.unauthenticated,
        session: null,
        message: error.message,
      ));
    } catch (_) {
      const message = 'No fue posible iniciar sesión. Revisa tu conexión e intenta de nuevo.';
      emit(state.copyWith(
        status: AuthStatus.failure,
        session: null,
        message: message,
      ));
      emit(state.copyWith(
        status: AuthStatus.unauthenticated,
        session: null,
        message: message,
      ));
    }
  }

  Future<void> logout() async {
    await _authRepository.logout();
    emit(state.copyWith(
      status: AuthStatus.unauthenticated,
      session: null,
      message: null,
    ));
  }

  Future<String?> getLastEmail() {
    return _authRepository.getLastEmail();
  }

  Future<void> rememberEmail(String email) {
    return _authRepository.saveLastEmail(email);
  }

  Future<bool> isServerReachable() {
    return _authRepository.isServerReachable();
  }

  Future<void> refreshProfile() async {
    final current = state.session;
    if (current == null) {
      return;
    }

    try {
      final user = await _authRepository.fetchProfile();
      final updatedSession = AuthSession(
        token: current.token,
        expiresAt: current.expiresAt,
        user: user,
      );
      emit(state.copyWith(session: updatedSession));
    } catch (_) {
      // Silently ignore; the dashboard can continue with cached data.
    }
  }
}


