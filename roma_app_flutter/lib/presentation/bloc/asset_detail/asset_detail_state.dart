import 'package:equatable/equatable.dart';

import 'package:roma_app_flutter/domain/entities/asset_detail.dart';

enum AssetDetailStatus { initial, loading, success, failure }

class AssetDetailState extends Equatable {
  const AssetDetailState({
    this.status = AssetDetailStatus.initial,
    this.detail,
    this.message,
  });

  final AssetDetailStatus status;
  final AssetDetail? detail;
  final String? message;

  AssetDetailState copyWith({
    AssetDetailStatus? status,
    AssetDetail? detail,
    String? message,
  }) {
    return AssetDetailState(
      status: status ?? this.status,
      detail: detail ?? this.detail,
      message: message,
    );
  }

  @override
  List<Object?> get props => [status, detail, message];
}
