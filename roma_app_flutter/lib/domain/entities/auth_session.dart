import 'package:equatable/equatable.dart';

import 'package:roma_app_flutter/domain/entities/user.dart';

class AuthSession extends Equatable {
  const AuthSession({
    required this.token,
    required this.expiresAt,
    required this.user,
  });

  final String token;
  final DateTime expiresAt;
  final User user;

  bool get isExpired => DateTime.now().isAfter(expiresAt);

  Duration get timeToExpire => expiresAt.difference(DateTime.now());

  @override
  List<Object?> get props => [token, expiresAt, user];
}


