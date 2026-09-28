import 'package:roma_app_flutter/domain/entities/auth_session.dart';
import 'package:roma_app_flutter/domain/entities/user.dart';

abstract class AuthRepository {
  Future<AuthSession> login({
    required String email,
    required String password,
  });

  Future<AuthSession?> getSession();
  Future<void> logout();
  Future<User> fetchProfile();
  Future<bool> ping();
  Future<String?> getLastEmail();
  Future<void> saveLastEmail(String email);
  Future<bool> isServerReachable();
}


