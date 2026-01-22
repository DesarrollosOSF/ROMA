import 'package:equatable/equatable.dart';

class OrderSummary extends Equatable {
  const OrderSummary({
    required this.id,
    required this.numeroRadicado,
    required this.estado,
    required this.nivelCriticidad,
    required this.fechaCreacion,
    this.fechaLimite,
    this.nombreActivo,
    this.codigoActivo,
  });

  final int id;
  final String numeroRadicado;
  final String estado;
  final String nivelCriticidad;
  final DateTime fechaCreacion;
  final DateTime? fechaLimite;
  final String? nombreActivo;
  final String? codigoActivo;

  bool get esCritica => nivelCriticidad.toLowerCase() == 'critica';
  bool get esAlta => nivelCriticidad.toLowerCase() == 'alta';

  @override
  List<Object?> get props => [
        id,
        numeroRadicado,
        estado,
        nivelCriticidad,
        fechaCreacion,
        fechaLimite,
        nombreActivo,
        codigoActivo,
      ];
}


