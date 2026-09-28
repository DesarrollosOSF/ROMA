import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:roma_app_flutter/core/config/app_flavor.dart';
import 'package:roma_app_flutter/core/config/env.dart';
import 'package:roma_app_flutter/core/di/injector.dart';
import 'package:roma_app_flutter/presentation/bloc/auth/auth_cubit.dart';
import 'package:roma_app_flutter/presentation/bloc/auth/auth_state.dart';
import 'package:roma_app_flutter/presentation/home/home_page.dart';
import 'package:roma_app_flutter/presentation/pages/login/login_page.dart';
import 'package:roma_app_flutter/presentation/pages/splash/splash_page.dart';
import 'package:roma_app_flutter/presentation/theme/app_theme.dart';

class RomaApp extends StatelessWidget {
  const RomaApp({
    super.key,
    required this.flavor,
    required this.env,
  });

  final AppFlavor flavor;
  final EnvConfig env;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => AuthCubit(injector())..checkAuth(),
      child: MaterialApp(
        title: 'ROMA',
        theme: AppTheme.light,
        darkTheme: AppTheme.dark,
        debugShowCheckedModeBanner: false,
        home: _AuthRouter(
          flavor: flavor,
          env: env,
        ),
      ),
    );
  }
}

class _AuthRouter extends StatelessWidget {
  const _AuthRouter({
    required this.flavor,
    required this.env,
  });

  final AppFlavor flavor;
  final EnvConfig env;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AuthCubit, AuthState>(
      builder: (context, state) {
        switch (state.status) {
          case AuthStatus.initial:
          case AuthStatus.checking:
            return SplashPage(
              apiBaseUrl: env.apiBaseUrl,
              flavor: flavor,
              message: state.message,
            );
          case AuthStatus.authenticated:
            return HomePage(
              session: state.session!,
              flavor: flavor,
            );
          case AuthStatus.loading:
            return LoginPage(
              errorMessage: state.message,
              isSubmitting: true,
            );
          case AuthStatus.unauthenticated:
          case AuthStatus.failure:
            return LoginPage(
              errorMessage: state.message,
              isSubmitting: false,
            );
        }
      },
    );
  }
}
