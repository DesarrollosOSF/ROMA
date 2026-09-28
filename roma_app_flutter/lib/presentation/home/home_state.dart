import 'package:equatable/equatable.dart';

enum HomeSection {
  dashboard,
  orders,
  assets,
  profile,
}

class HomeState extends Equatable {
  const HomeState({this.section = HomeSection.dashboard});

  final HomeSection section;

  HomeState copyWith({HomeSection? section}) {
    return HomeState(section: section ?? this.section);
  }

  @override
  List<Object?> get props => [section];
}
