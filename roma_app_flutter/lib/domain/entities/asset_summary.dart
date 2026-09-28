import 'package:equatable/equatable.dart';

class AssetSummary extends Equatable {
  const AssetSummary({
    required this.id,
    required this.nombre,
    this.codigo,
    this.fechaObjetivo,
    this.estadoActual,
  });

  final int id;
  final String nombre;
  final String? codigo;
  final DateTime? fechaObjetivo;
  final String? estadoActual;

  @override
  List<Object?> get props => [
        id,
        nombre,
        codigo,
        fechaObjetivo,
        estadoActual,
      ];
}


