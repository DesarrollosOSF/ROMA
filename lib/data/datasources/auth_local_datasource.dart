import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:roma_app_flutter/data/models/auth_session_model.dart';

abstract class AuthLocalDataSource {
  Future<void> cacheSession(AuthSessionModel session);
  Future<AuthSessionModel?> getSession();
  Future<void> clearSession();
}

class AuthLocalDataSourceImpl implements AuthLocalDataSource {
  AuthLocalDataSourceImpl({
    required SharedPreferences preferences,
    required FlutterSecureStorage secureStorage,
  })  : _preferences = preferences,
        _secureStorage = secureStorage;

  static const _tokenKey = 'auth_token';
  static const _expiresAtKey = 'auth_expires_at';
  static const _userKey = 'auth_user';

  final SharedPreferences _preferences;
  final FlutterSecureStorage _secureStorage;

  @override
  Future<void> cacheSession(AuthSessionModel session) async {
    await Future.wait([
      _secureStorage.write(key: _tokenKey, value: session.token),
      _preferences.setString(_expiresAtKey, session.expiresAt.toIso8601String()),
      _preferences.setString(_userKey, jsonEncode(session.user.toJson())),
    ]);
  }

  @override
  Future<void> clearSession() async {
    await Future.wait([
      _secureStorage.delete(key: _tokenKey),
      _preferences.remove(_expiresAtKey),
      _preferences.remove(_userKey),
    ]);
  }

  @override
  Future<AuthSessionModel?> getSession() async {
    final token = await _secureStorage.read(key: _tokenKey);
    if (token == null || token.isEmpty) {
      return null;
    }

    final expiresAtString = _preferences.getString(_expiresAtKey);
    if (expiresAtString == null) {
      return null;
    }

    final expiresAt = DateTime.tryParse(expiresAtString);
    if (expiresAt == null) {
      await clearSession();
      return null;
    }

    if (DateTime.now().isAfter(expiresAt)) {
      await clearSession();
      return null;
    }

    final userRaw = _preferences.getString(_userKey);
    if (userRaw == null) {
      await clearSession();
      return null;
    }

    final userMap = jsonDecode(userRaw) as Map<String, dynamic>;
    return AuthSessionModel.fromJson({
      'token': token,
      'expires_at': expiresAt.toIso8601String(),
      'usuario': userMap,
    });
  }
}


