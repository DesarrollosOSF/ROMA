import 'package:roma_app_flutter/data/models/asset_summary_model.dart';
import 'package:roma_app_flutter/data/models/dashboard_stats_model.dart';
import 'package:roma_app_flutter/data/models/order_summary_model.dart';
import 'package:roma_app_flutter/domain/entities/dashboard_data.dart';

class DashboardDataModel extends DashboardData {
  const DashboardDataModel({
    required DashboardStatsModel stats,
    required List<OrderSummaryModel> ordenesRecientes,
    required List<OrderSummaryModel> ordenesCriticas,
    required List<AssetSummaryModel> mantenimientosProximos,
  }) : super(
          stats: stats,
          ordenesRecientes: ordenesRecientes,
          ordenesCriticas: ordenesCriticas,
          mantenimientosProximos: mantenimientosProximos,
        );

  factory DashboardDataModel.fromJson(Map<String, dynamic> json) {
    final estadisticas = json['estadisticas'] as Map<String, dynamic>? ?? const {};
    final ordenesRecientesJson = json['ordenes_recientes'] as List<dynamic>? ?? const [];
    final ordenesCriticasJson = json['ordenes_criticas'] as List<dynamic>? ?? const [];
    final mantenimientosProximosJson = json['mantenimientos_proximos'] as List<dynamic>? ?? const [];

    return DashboardDataModel(
      stats: DashboardStatsModel.fromJson(estadisticas),
      ordenesRecientes: ordenesRecientesJson
          .map((item) => OrderSummaryModel.fromJson(item as Map<String, dynamic>))
          .toList(),
      ordenesCriticas: ordenesCriticasJson
          .map((item) => OrderSummaryModel.fromJson(item as Map<String, dynamic>))
          .toList(),
      mantenimientosProximos: mantenimientosProximosJson
          .map((item) => AssetSummaryModel.fromJson(item as Map<String, dynamic>))
          .toList(),
    );
  }
}


