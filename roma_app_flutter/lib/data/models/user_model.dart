import 'package:roma_app_flutter/domain/entities/user.dart';

class UserModel extends User {
  const UserModel({
    required super.id,
    required super.nombre,
    required super.email,
    required super.rol,
    super.telefono,
    super.cargo,
    super.area,
    super.fechaCreacion,
    super.permisos,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    DateTime? fechaCreacion;
    final fechaRaw = json['fecha_creacion']?.toString();
    if (fechaRaw != null && fechaRaw.isNotEmpty) {
      fechaCreacion = DateTime.tryParse(fechaRaw);
    }

    final permisosRaw = json['permisos'];
    Map<String, bool> permisos = const {};
    if (permisosRaw is Map<String, dynamic>) {
      permisos = permisosRaw.map(
        (key, value) => MapEntry(key, value == true || value?.toString() == '1'),
      );
    }

    return UserModel(
      id: int.tryParse(json['id_usuario']?.toString() ?? '') ?? 0,
      nombre: json['nombre']?.toString() ?? 'Sin nombre',
      email: json['email']?.toString() ?? '',
      rol: json['rol']?.toString() ?? '',
      telefono: json['telefono']?.toString(),
      cargo: json['cargo']?.toString(),
      area: json['area']?.toString(),
      fechaCreacion: fechaCreacion,
      permisos: permisos,
    );
  }

  @override
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
}


