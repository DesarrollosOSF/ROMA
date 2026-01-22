import 'package:equatable/equatable.dart';

class OrderHistoryEntry extends Equatable {
  const OrderHistoryEntry({
    required this.id,
    required this.tipoCambio,
    required this.fecha,
    this.descripcion,
    this.campoAnterior,
    this.valorAnterior,
    this.campoNuevo,
    this.valorNuevo,
    this.usuarioNombre,
    this.usuarioEmail,
    this.rutaEvidencia,
  });

  final int id;
  final String tipoCambio;
  final DateTime fecha;
  final String? descripcion;
  final String? campoAnterior;
  final String? valorAnterior;
  final String? campoNuevo;
  final String? valorNuevo;
  final String? usuarioNombre;
  final String? usuarioEmail;
  final String? rutaEvidencia;

  @override
  List<Object?> get props => [
        id,
        tipoCambio,
        fecha,
        descripcion,
        campoAnterior,
        valorAnterior,
        campoNuevo,
        valorNuevo,
        usuarioNombre,
        usuarioEmail,
        rutaEvidencia,
      ];
}
