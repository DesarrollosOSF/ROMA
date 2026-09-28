import 'package:dio/dio.dart';

import 'package:roma_app_flutter/core/errors/app_exception.dart';
import 'package:roma_app_flutter/core/network/network_exception.dart';
import 'package:roma_app_flutter/data/models/auth_session_model.dart';
import 'package:roma_app_flutter/data/models/user_model.dart';

abstract class AuthRemoteDataSource {
  Future<AuthSessionModel> login({
    required String email,
    required String password,
  });

  Future<UserModel> fetchProfile();
  Future<void> ping();
}

class AuthRemoteDataSourceImpl implements AuthRemoteDataSource {
  AuthRemoteDataSourceImpl(this._dio);

  final Dio _dio;

  @override
  Future<UserModel> fetchProfile() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>('/auth/profile');
      final data = response.data ?? {};

      if (data['success'] != true) {
        throw AppException(data['message']?.toString() ?? 'No fue posible obtener el perfil.');
      }

      final userJson = data['data'] as Map<String, dynamic>? ?? const {};
      return UserModel.fromJson(userJson);
    } on DioException catch (error) {
      throw NetworkException.fromDioError(error);
    }
  }

  @override
  Future<AuthSessionModel> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        '/auth/login',
        data: {
          'email': email,
          'password': password,
        },
      );

      final data = response.data ?? {};
      if (data['success'] != true) {
        throw AppException(data['message']?.toString() ?? 'Credenciales inválidas.');
      }

      return AuthSessionModel.fromJson(data['data'] as Map<String, dynamic>? ?? const {});
    } on DioException catch (error) {
      throw NetworkException.fromDioError(error);
    }
  }

  @override
  Future<void> ping() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>('/auth/ping');
      final data = response.data ?? {};
      if (data['success'] != true) {
        throw AppException(data['message']?.toString() ?? 'Token inválido.');
      }
    } on DioException catch (error) {
      throw NetworkException.fromDioError(error);
    }
  }
}


