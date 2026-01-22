import 'dart:async';

import 'package:flutter/widgets.dart';

import 'package:roma_app_flutter/app.dart';
import 'package:roma_app_flutter/core/config/app_flavor.dart';
import 'package:roma_app_flutter/core/config/env.dart';
import 'package:roma_app_flutter/core/di/injector.dart';

Future<void> bootstrap({AppFlavor? flavorOverride}) async {
  WidgetsFlutterBinding.ensureInitialized();

  final flavor = flavorOverride ?? AppFlavor.dev;

  await EnvConfig.load(flavor: flavor);
  await configureDependencies(flavor);

  runApp(
    RomaApp(
      flavor: flavor,
      env: EnvConfig.instance,
    ),
  );
}


