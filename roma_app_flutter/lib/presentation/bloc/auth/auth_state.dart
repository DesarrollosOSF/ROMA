import 'package:equatable/equatable.dart';

import 'package:roma_app_flutter/domain/entities/auth_session.dart';

enum AuthStatus {
  initial,
  checking,
  authenticated,
  unauthenticated,
  loading,
  failure,
}

class AuthState extends Equatable {
  const AuthState({
    required this.status,
    this.session,
    this.message,
  });

  const AuthState.initial() : this(status: AuthStatus.initial);

  final AuthStatus status;
  final AuthSession? session;
  final String? message;

  AuthState copyWith({
    AuthStatus? status,
    AuthSession? session,
    String? message,
  }) {
    return AuthState(
      status: status ?? this.status,
      session: session ?? this.session,
      message: message,
    );
  }

  @override
  List<Object?> get props => [status, session, message];
}


