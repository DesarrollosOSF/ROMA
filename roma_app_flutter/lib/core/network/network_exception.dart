import 'package:dio/dio.dart';

import 'package:roma_app_flutter/core/errors/app_exception.dart';

class NetworkException {
  const NetworkException._();

  static AppException fromDioError(DioException error) {
    final response = error.response;
    final statusCode = response?.statusCode;

    if (response?.data is Map<String, dynamic>) {
      final data = response!.data as Map<String, dynamic>;
      final message = data['message']?.toString() ?? error.message ?? 'Error de red';
      final details = data['errors'] is Map<String, dynamic> ? data['errors'] as Map<String, dynamic> : null;
      return AppException(
        message,
        statusCode: statusCode,
        details: details,
      );
    }

    switch (error.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return AppException(
          'La petición tardó demasiado en responder. Intente nuevamente.',
          statusCode: statusCode,
        );
      case DioExceptionType.badResponse:
        return AppException(
          'Ocurrió un error al procesar la respuesta del servidor.',
          statusCode: statusCode,
        );
      case DioExceptionType.cancel:
        return AppException(
          'La petición fue cancelada.',
          statusCode: statusCode,
        );
      case DioExceptionType.unknown:
        return AppException(
          'No fue posible conectar con el servidor. Verifique su conexión a internet.',
          statusCode: statusCode,
        );
      case DioExceptionType.badCertificate:
        return AppException(
          'El certificado del servidor no es válido.',
          statusCode: statusCode,
        );
      case DioExceptionType.connectionError:
        return AppException(
          'No se pudo establecer la conexión con el servidor.',
          statusCode: statusCode,
        );
    }
  }
}


