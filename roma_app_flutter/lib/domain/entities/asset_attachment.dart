import 'package:equatable/equatable.dart';

class AssetAttachment extends Equatable {
  const AssetAttachment({
    required this.id,
    required this.nombre,
    required this.ruta,
    this.tipoMime,
    this.tamanoBytes,
    this.descripcion,
    this.fechaSubida,
  });

  final int id;
  final String nombre;
  final String ruta;
  final String? tipoMime;
  final int? tamanoBytes;
  final String? descripcion;
  final DateTime? fechaSubida;

  @override
  List<Object?> get props => [id, nombre, ruta, tipoMime, tamanoBytes, descripcion, fechaSubida];
}
