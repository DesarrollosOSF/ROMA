import 'package:roma_app_flutter/core/errors/app_exception.dart';
import 'package:roma_app_flutter/data/datasources/auth_local_datasource.dart';
import 'package:roma_app_flutter/data/datasources/auth_remote_datasource.dart';
import 'package:roma_app_flutter/data/models/auth_session_model.dart';
import 'package:roma_app_flutter/domain/entities/auth_session.dart';
import 'package:roma_app_flutter/domain/entities/user.dart';
import 'package:roma_app_flutter/domain/repositories/auth_repository.dart';

class AuthRepositoryImpl implements AuthRepository {
  AuthRepositoryImpl({
    required AuthRemoteDataSource remoteDataSource,
    required AuthLocalDataSource localDataSource,
  })  : _remoteDataSource = remoteDataSource,
        _localDataSource = localDataSource;

  final AuthRemoteDataSource _remoteDataSource;
  final AuthLocalDataSource _localDataSource;

  @override
  Future<User> fetchProfile() async {
    final user = await _remoteDataSource.fetchProfile();
    final session = await _localDataSource.getSession();
    if (session != null) {
      final updatedSession = AuthSessionModel(
        token: session.token,
        expiresAt: session.expiresAt,
        user: user,
      );
      await _localDataSource.cacheSession(updatedSession);
    }
    return user;
  }

  @override
  Future<AuthSession?> getSession() async {
    final session = await _localDataSource.getSession();
    if (session == null) {
      return null;
    }

    if (session.isExpired) {
      await _localDataSource.clearSession();
      return null;
    }

    return session;
  }

  @override
  Future<AuthSession> login({
    required String email,
    required String password,
  }) async {
    final session = await _remoteDataSource.login(email: email, password: password);
    if (session.token.isEmpty) {
      throw const AppException('El servidor no retornó un token válido.');
    }
    await _localDataSource.cacheSession(session);
    return session;
  }

  @override
  Future<void> logout() async {
    await _localDataSource.clearSession();
  }

  @override
  Future<bool> ping() async {
    try {
      await _remoteDataSource.ping();
      return true;
    } on AppException {
      return false;
    }
  }
}


