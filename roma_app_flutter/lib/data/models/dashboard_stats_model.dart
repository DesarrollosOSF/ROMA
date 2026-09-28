import 'package:roma_app_flutter/domain/entities/dashboard_stats.dart';

class DashboardStatsModel extends DashboardStats {
  const DashboardStatsModel({
    required super.totalActivos,
    required super.activosOperativos,
    required super.activosReparacion,
    required super.mantenimientosProximos,
    required super.totalMantenimientos,
    required super.activosSinPlan,
    required super.activosVencidos,
    required super.totalOrdenes,
    required super.ordenesPendientes,
    required super.ordenesFinalizadas,
    required super.ordenesUrgentes,
    required super.ordenesCriticas,
    super.cierrePromedioHoras,
  });

  factory DashboardStatsModel.fromJson(Map<String, dynamic> json) {
    double? cierrePromedio;
    final cierreRaw = json['cierre_promedio'];
    if (cierreRaw != null) {
      cierrePromedio = double.tryParse(cierreRaw.toString());
    }

    int parseInt(String key) => int.tryParse(json[key]?.toString() ?? '') ?? 0;

    return DashboardStatsModel(
      totalActivos: parseInt('total_activos'),
      activosOperativos: parseInt('activos_operativos'),
      activosReparacion: parseInt('activos_reparacion'),
      mantenimientosProximos: parseInt('mantenimientos_proximos'),
      totalMantenimientos: parseInt('total_mantenimientos'),
      activosSinPlan: parseInt('activos_sin_plan'),
      activosVencidos: parseInt('activos_vencidos'),
      totalOrdenes: parseInt('total_ordenes'),
      ordenesPendientes: parseInt('ordenes_pendientes'),
      ordenesFinalizadas: parseInt('ordenes_finalizadas'),
      ordenesUrgentes: parseInt('ordenes_urgentes'),
      ordenesCriticas: parseInt('ordenes_criticas'),
      cierrePromedioHoras: cierrePromedio,
    );
  }
}


