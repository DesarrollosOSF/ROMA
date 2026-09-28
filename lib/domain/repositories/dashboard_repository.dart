import 'package:roma_app_flutter/domain/entities/dashboard_data.dart';

abstract class DashboardRepository {
  Future<DashboardData> fetchDashboard();
}


