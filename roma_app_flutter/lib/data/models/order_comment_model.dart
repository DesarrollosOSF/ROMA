import 'package:roma_app_flutter/domain/entities/order_comment.dart';

class OrderCommentModel extends OrderComment {
  const OrderCommentModel({
    required super.id,
    required super.comentario,
    required super.fecha,
    super.usuarioNombre,
    super.usuarioEmail,
  });

  factory OrderCommentModel.fromJson(Map<String, dynamic> json) {
    DateTime fecha = DateTime.now();
    final fechaRaw = json['fecha_creacion'] ?? json['fecha'];
    if (fechaRaw != null) {
      fecha = DateTime.tryParse(fechaRaw.toString()) ?? fecha;
    }

    return OrderCommentModel(
      id: int.tryParse(json['id_comentario']?.toString() ?? json['id']?.toString() ?? '') ?? 0,
      comentario: json['comentario']?.toString() ?? '',
      fecha: fecha,
      usuarioNombre: json['nombre_usuario']?.toString() ?? json['usuario']?.toString(),
      usuarioEmail: json['email_usuario']?.toString(),
    );
  }
}
