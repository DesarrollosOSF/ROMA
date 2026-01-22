class AppException implements Exception {
  const AppException(
    this.message, {
    this.statusCode,
    this.details,
  });

  final String message;
  final int? statusCode;
  final Map<String, dynamic>? details;

  @override
  String toString() => 'AppException($statusCode): $message';
}


