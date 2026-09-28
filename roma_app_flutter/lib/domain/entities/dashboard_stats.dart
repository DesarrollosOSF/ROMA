import 'package:equatable/equatable.dart';

class DashboardStats extends Equatable {
  const DashboardStats({
    required this.totalActivos,
    required this.activosOperativos,
    required this.activosReparacion,
    required this.mantenimientosProximos,
    required this.totalMantenimientos,
    required this.activosSinPlan,
    required this.activosVencidos,
    required this.totalOrdenes,
    required this.ordenesPendientes,
    required this.ordenesFinalizadas,
    required this.ordenesUrgentes,
    required this.ordenesCriticas,
    this.cierrePromedioHoras,
  });

  final int totalActivos;
  final int activosOperativos;
  final int activosReparacion;
  final int mantenimientosProximos;
  final int totalMantenimientos;
  final int activosSinPlan;
  final int activosVencidos;
  final int totalOrdenes;
  final int ordenesPendientes;
  final int ordenesFinalizadas;
  final int ordenesUrgentes;
  final int ordenesCriticas;
  final double? cierrePromedioHoras;

  bool get tieneActivos => totalActivos > 0;
  bool get tieneOrdenes => totalOrdenes > 0;

  @override
  List<Object?> get props => [
        totalActivos,
        activosOperativos,
        activosReparacion,
        mantenimientosProximos,
        totalMantenimientos,
        activosSinPlan,
        activosVencidos,
        totalOrdenes,
        ordenesPendientes,
        ordenesFinalizadas,
        ordenesUrgentes,
        ordenesCriticas,
        cierrePromedioHoras,
      ];
}


