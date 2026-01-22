import 'package:equatable/equatable.dart';

class OrderComment extends Equatable {
  const OrderComment({
    required this.id,
    required this.comentario,
    required this.fecha,
    this.usuarioNombre,
    this.usuarioEmail,
  });

  final int id;
  final String comentario;
  final DateTime fecha;
  final String? usuarioNombre;
  final String? usuarioEmail;

  @override
  List<Object?> get props => [
        id,
        comentario,
        fecha,
        usuarioNombre,
        usuarioEmail,
      ];
}
