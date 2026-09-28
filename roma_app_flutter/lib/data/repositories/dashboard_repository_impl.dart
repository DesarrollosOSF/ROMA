import 'package:roma_app_flutter/data/datasources/dashboard_remote_datasource.dart';
import 'package:roma_app_flutter/domain/entities/dashboard_data.dart';
import 'package:roma_app_flutter/domain/repositories/dashboard_repository.dart';

class DashboardRepositoryImpl implements DashboardRepository {
  DashboardRepositoryImpl(this._remoteDataSource);

  final DashboardRemoteDataSource _remoteDataSource;

  @override
  Future<DashboardData> fetchDashboard() {
    return _remoteDataSource.fetchDashboard();
  }
}


