import 'package:equatable/equatable.dart';

import 'package:roma_app_flutter/domain/entities/asset_summary.dart';
import 'package:roma_app_flutter/domain/repositories/assets_repository.dart';

enum AssetsStatus { initial, loading, success, failure }

class AssetsState extends Equatable {
  const AssetsState({
    this.status = AssetsStatus.initial,
    this.assets = const [],
    this.filters = const AssetFilters(),
    this.message,
  });

  final AssetsStatus status;
  final List<AssetSummary> assets;
  final AssetFilters filters;
  final String? message;

  AssetsState copyWith({
    AssetsStatus? status,
    List<AssetSummary>? assets,
    AssetFilters? filters,
    String? message,
  }) {
    return AssetsState(
      status: status ?? this.status,
      assets: assets ?? this.assets,
      filters: filters ?? this.filters,
      message: message,
    );
  }

  @override
  List<Object?> get props => [status, assets, filters, message];
}
