import 'dart:developer';

import 'package:flutter_dotenv/flutter_dotenv.dart';

import 'package:roma_app_flutter/core/config/app_flavor.dart';

class EnvConfig {
  EnvConfig._({
    required this.apiBaseUrl,
    required this.apiTimeout,
    required this.sentryDsn,
    required this.firebaseProjectId,
  });

  static EnvConfig? _instance;

  final String apiBaseUrl;
  final Duration apiTimeout;
  final String? sentryDsn;
  final String? firebaseProjectId;

  static EnvConfig get instance {
    final config = _instance;
    if (config == null) {
      throw StateError(
        'EnvConfig not initialized. Call EnvConfig.load() before accessing instance.',
      );
    }
    return config;
  }

  static Future<void> load({required AppFlavor flavor}) async {
    if (_instance != null) {
      return;
    }

    final defaultValues = _defaultValuesFor(flavor);

    try {
      await dotenv.load(
        fileName: flavor.envFileName,
        mergeWith: defaultValues,
      );
    } catch (error, stackTrace) {
      log(
        'No se pudo cargar ${flavor.envFileName}. Se usarán valores por defecto (.env).',
        error: error,
        stackTrace: stackTrace,
        name: 'EnvConfig',
      );
      try {
        await dotenv.load(
          fileName: '.env',
          mergeWith: defaultValues,
        );
      } catch (_) {
        dotenv.testLoad(mergeWith: defaultValues);
      }
    }

    _instance = EnvConfig._(
      apiBaseUrl: dotenv.env['API_BASE_URL'] ?? defaultValues['API_BASE_URL']!,
      apiTimeout: Duration(
        milliseconds: int.tryParse(dotenv.env['API_TIMEOUT'] ?? '') ??
            int.parse(defaultValues['API_TIMEOUT']!),
      ),
      sentryDsn: dotenv.env['SENTRY_DSN'],
      firebaseProjectId: dotenv.env['FIREBASE_PROJECT_ID'],
    );
  }

  static Map<String, String> _defaultValuesFor(AppFlavor flavor) {
    switch (flavor) {
      case AppFlavor.dev:
        return {
          'API_BASE_URL': 'https://roma.osf.com.co/api',
          'API_TIMEOUT': '15000',
          'SENTRY_DSN': '',
          'FIREBASE_PROJECT_ID': 'roma-dev',
        };
      case AppFlavor.qa:
        return {
          'API_BASE_URL': 'https://qa.roma.osf.com.co/api',
          'API_TIMEOUT': '15000',
          'SENTRY_DSN': '',
          'FIREBASE_PROJECT_ID': 'roma-qa',
        };
      case AppFlavor.prod:
        return {
          'API_BASE_URL': 'https://roma.osf.com.co/api',
          'API_TIMEOUT': '15000',
          'SENTRY_DSN': '',
          'FIREBASE_PROJECT_ID': 'roma-prod',
        };
    }
  }
}


