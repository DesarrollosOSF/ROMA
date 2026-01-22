import 'package:roma_app_flutter/bootstrap.dart';
import 'package:roma_app_flutter/core/config/app_flavor.dart';

void main() {
  const flavorName = String.fromEnvironment('FLAVOR', defaultValue: 'prod');
  final flavor = AppFlavorX.fromName(flavorName);

  bootstrap(flavorOverride: flavor);
}


