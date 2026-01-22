import 'package:roma_app_flutter/data/models/user_model.dart';
import 'package:roma_app_flutter/domain/entities/auth_session.dart';

class AuthSessionModel extends AuthSession {
  AuthSessionModel({
    required super.token,
    required super.expiresAt,
    required UserModel super.user,
  });

  factory AuthSessionModel.fromJson(Map<String, dynamic> json) {
    final expiraEn = json['expira_en'];
    DateTime? expiresAt;

    if (json['expires_at'] != null) {
      expiresAt = DateTime.tryParse(json['expires_at'].toString());
    }

    if (expiresAt == null) {
      final ttlSeconds = int.tryParse(expiraEn?.toString() ?? '');
      if (ttlSeconds != null) {
        expiresAt = DateTime.now().add(Duration(seconds: ttlSeconds));
      } else {
        expiresAt = DateTime.now().add(const Duration(hours: 1));
      }
    }

    return AuthSessionModel(
      token: json['token']?.toString() ?? '',
      expiresAt: expiresAt,
      user: UserModel.fromJson(json['usuario'] as Map<String, dynamic>? ?? const {}),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'token': token,
      'expira_en': timeToExpire.inSeconds,
      'expires_at': expiresAt.toIso8601String(),
      'usuario': (user as UserModel).toJson(),
    };
  }
}


