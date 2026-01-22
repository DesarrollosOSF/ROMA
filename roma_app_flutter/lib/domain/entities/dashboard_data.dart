import 'package:equatable/equatable.dart';

import 'package:roma_app_flutter/domain/entities/asset_summary.dart';
import 'package:roma_app_flutter/domain/entities/dashboard_stats.dart';
import 'package:roma_app_flutter/domain/entities/order_summary.dart';

class DashboardData extends Equatable {
  const DashboardData({
    required this.stats,
    required this.ordenesRecientes,
    required this.ordenesCriticas,
    required this.mantenimientosProximos,
  });

  final DashboardStats stats;
  final List<OrderSummary> ordenesRecientes;
  final List<OrderSummary> ordenesCriticas;
  final List<AssetSummary> mantenimientosProximos;

  @override
  List<Object?> get props => [
        stats,
        ordenesRecientes,
        ordenesCriticas,
        mantenimientosProximos,
      ];
}


