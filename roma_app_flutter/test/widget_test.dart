// This is a basic Flutter widget test.
//
// To perform an interaction with a widget in your test, use the WidgetTester
// utility in the flutter_test package. For example, you can send tap and scroll
// gestures. You can also use WidgetTester to find child widgets in the widget
// tree, read text, and verify that the values of widget properties are correct.

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:roma_app_flutter/core/config/app_flavor.dart';
import 'package:roma_app_flutter/presentation/pages/splash/splash_page.dart';

void main() {
  testWidgets('SplashPage muestra la marca ROMA y el flavor', (tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: SplashPage(
          apiBaseUrl: 'https://demo.api',
          flavor: AppFlavor.dev,
        ),
      ),
    );

    await tester.pumpAndSettle();

    expect(find.text('Sistema ROMA'), findsOneWidget);
    expect(find.textContaining('Flavor: DEV'), findsOneWidget);
    expect(find.textContaining('https://demo.api'), findsOneWidget);
  });
}
