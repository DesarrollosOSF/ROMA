import 'package:equatable/equatable.dart';

class User extends Equatable {
  const User({
    required this.id,
    required this.nombre,
    required this.email,
    required this.rol,
    this.telefono,
    this.cargo,
    this.area,
    this.fechaCreacion,
    this.permisos = const {},
  });

  final int id;
  final String nombre;
  final String email;
  final String rol;
  final String? telefono;
  final String? cargo;
  final String? area;
  final DateTime? fechaCreacion;
  final Map<String, bool> permisos;

  bool get esAdministrador => rol.toLowerCase() == 'administrador';

  Map<String, dynamic> toJson() {
    return {
      'id_usuario': id,
      'nombre': nombre,
      'email': email,
      'rol': rol,
      'telefono': telefono,
      'cargo': cargo,
      'area': area,
      'fecha_creacion': fechaCreacion?.toIso8601String(),
      'permisos': permisos,
    };
  }

  @override
  List<Object?> get props => [
        id,
        nombre,
        email,
        rol,
        telefono,
        cargo,
        area,
        fechaCreacion,
        permisos,
      ];
}


