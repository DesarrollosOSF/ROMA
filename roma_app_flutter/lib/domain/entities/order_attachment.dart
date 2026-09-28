import 'package:equatable/equatable.dart';

class OrderAttachment extends Equatable {
  const OrderAttachment({
    required this.id,
    required this.nombre,
    required this.ruta,
    this.tipoMime,
    this.tamanoBytes,
    this.descripcion,
    this.fechaSubida,
    this.usuario,
  });

  final int id;
  final String nombre;
  final String ruta;
  final String? tipoMime;
  final int? tamanoBytes;
  final String? descripcion;
  final DateTime? fechaSubida;
  final String? usuario;

  @override
  List<Object?> get props => [
        id,
        nombre,
        ruta,
        tipoMime,
        tamanoBytes,
        descripcion,
        fechaSubida,
        usuario,
      ];
}
