// ignore_for_file: cascade_invocations

import 'dart:io';

import 'package:dio/dio.dart';
import 'package:dio/io.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:get_it/get_it.dart';
import 'package:pretty_dio_logger/pretty_dio_logger.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:roma_app_flutter/core/config/app_flavor.dart';
import 'package:roma_app_flutter/core/config/env.dart';
import 'package:roma_app_flutter/data/datasources/auth_local_datasource.dart';
import 'package:roma_app_flutter/data/datasources/auth_remote_datasource.dart';
import 'package:roma_app_flutter/data/datasources/dashboard_remote_datasource.dart';
import 'package:roma_app_flutter/data/datasources/orders_remote_datasource.dart';
import 'package:roma_app_flutter/data/datasources/assets_remote_datasource.dart';
import 'package:roma_app_flutter/data/repositories/auth_repository_impl.dart';
import 'package:roma_app_flutter/data/repositories/dashboard_repository_impl.dart';
import 'package:roma_app_flutter/data/repositories/orders_repository_impl.dart';
import 'package:roma_app_flutter/data/repositories/assets_repository_impl.dart';
import 'package:roma_app_flutter/domain/repositories/auth_repository.dart';
import 'package:roma_app_flutter/domain/repositories/dashboard_repository.dart';
import 'package:roma_app_flutter/domain/repositories/orders_repository.dart';
import 'package:roma_app_flutter/domain/repositories/assets_repository.dart';

final GetIt injector = GetIt.instance;

Future<void> configureDependencies(AppFlavor flavor) async {
  await injector.reset(dispose: false);

  final env = EnvConfig.instance;
  final sharedPreferences = await SharedPreferences.getInstance();
  const secureStorage = FlutterSecureStorage();

  final authLocalDataSource = AuthLocalDataSourceImpl(
    preferences: sharedPreferences,
    secureStorage: secureStorage,
  );

  final dio = Dio(
    BaseOptions(
      baseUrl: env.apiBaseUrl,
      connectTimeout: env.apiTimeout,
      receiveTimeout: env.apiTimeout,
      headers: const {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ),
  );

  final interceptors = dio.interceptors;

  interceptors.addAll([
    QueuedInterceptorsWrapper(
      onRequest: (options, handler) async {
        final session = await authLocalDataSource.getSession();
        if (session != null && !session.isExpired) {
          options.headers['Authorization'] = 'Bearer ${session.token}';
        }
        handler.next(options);
      },
      onError: (error, handler) async {
        if (error.response?.statusCode == 401) {
          await authLocalDataSource.clearSession();
        }
        handler.next(error);
      },
    ),
    if (flavor.enableNetworkLogging)
      PrettyDioLogger(
        requestHeader: true,
        requestBody: true,
        responseBody: true,
        responseHeader: false,
        error: true,
        compact: true,
      ),
  ]);

  if (flavor != AppFlavor.prod) {
    final adapter = dio.httpClientAdapter;
    if (adapter is IOHttpClientAdapter) {
      adapter.createHttpClient = () {
        final client = HttpClient();
        client.badCertificateCallback = (cert, host, port) => true;
        return client;
      };
    }
  }

  injector
    ..registerSingleton<EnvConfig>(env)
    ..registerSingleton<AppFlavor>(flavor)
    ..registerSingleton<SharedPreferences>(sharedPreferences)
    ..registerSingleton<FlutterSecureStorage>(secureStorage)
    ..registerSingleton<AuthLocalDataSource>(authLocalDataSource)
    ..registerSingleton<Dio>(dio)
    ..registerLazySingleton<AuthRemoteDataSource>(() => AuthRemoteDataSourceImpl(injector()))
    ..registerLazySingleton<DashboardRemoteDataSource>(() => DashboardRemoteDataSourceImpl(injector()))
    ..registerLazySingleton<OrdersRemoteDataSource>(() => OrdersRemoteDataSourceImpl(injector()))
    ..registerLazySingleton<AssetsRemoteDataSource>(() => AssetsRemoteDataSourceImpl(injector()))
    ..registerLazySingleton<AuthRepository>(
      () => AuthRepositoryImpl(
        remoteDataSource: injector(),
        localDataSource: injector(),
      ),
    )
    ..registerLazySingleton<DashboardRepository>(
      () => DashboardRepositoryImpl(injector()),
    )
    ..registerLazySingleton<OrdersRepository>(
      () => OrdersRepositoryImpl(injector()),
    )
    ..registerLazySingleton<AssetsRepository>(
      () => AssetsRepositoryImpl(injector()),
    );
}


