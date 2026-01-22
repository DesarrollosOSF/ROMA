import 'package:equatable/equatable.dart';

import 'package:roma_app_flutter/domain/entities/dashboard_data.dart';

enum DashboardStatus {
  initial,
  loading,
  success,
  failure,
}

class DashboardState extends Equatable {
  const DashboardState({
    required this.status,
    this.data,
    this.message,
  });

  const DashboardState.initial() : this(status: DashboardStatus.initial);

  final DashboardStatus status;
  final DashboardData? data;
  final String? message;

  DashboardState copyWith({
    DashboardStatus? status,
    DashboardData? data,
    String? message,
  }) {
    return DashboardState(
      status: status ?? this.status,
      data: data ?? this.data,
      message: message,
    );
  }

  @override
  List<Object?> get props => [status, data, message];
}


