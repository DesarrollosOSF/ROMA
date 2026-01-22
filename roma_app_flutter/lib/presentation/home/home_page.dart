import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:roma_app_flutter/core/config/app_flavor.dart';
import 'package:roma_app_flutter/domain/entities/auth_session.dart';
import 'package:roma_app_flutter/presentation/bloc/auth/auth_cubit.dart';
import 'package:roma_app_flutter/presentation/home/home_cubit.dart';
import 'package:roma_app_flutter/presentation/home/home_state.dart';
import 'package:roma_app_flutter/presentation/pages/assets/assets_section.dart';
import 'package:roma_app_flutter/presentation/pages/dashboard/dashboard_page.dart';
import 'package:roma_app_flutter/presentation/pages/orders/orders_section.dart';
import 'package:roma_app_flutter/presentation/pages/profile/profile_section.dart';

class HomePage extends StatelessWidget {
  const HomePage({
    super.key,
    required this.session,
    required this.flavor,
  });

  final AuthSession session;
  final AppFlavor flavor;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => HomeCubit(),
      child: _HomeView(session: session, flavor: flavor),
    );
  }
}

class _HomeView extends StatelessWidget {
  const _HomeView({
    required this.session,
    required this.flavor,
  });

  final AuthSession session;
  final AppFlavor flavor;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<HomeCubit, HomeState>(
      builder: (context, state) {
        final theme = Theme.of(context);
        return Scaffold(
          appBar: AppBar(
            title: Row(
              children: [
                CircleAvatar(
                  radius: 16,
                  backgroundColor: theme.colorScheme.primaryContainer,
                  child: Text(
                    session.user.nombre.isNotEmpty
                        ? session.user.nombre[0].toUpperCase()
                        : '?',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      color: theme.colorScheme.onPrimaryContainer,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        _titleForSection(state.section),
                        style: theme.textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      Flexible(
                        child: Text(
                          session.user.nombre,
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            elevation: 0,
            actions: [
              // Solo mostrar el indicador de ambiente en desarrollo/QA
              if (flavor != AppFlavor.prod)
                Container(
                  margin: const EdgeInsets.only(right: 8),
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: flavor == AppFlavor.dev
                        ? Colors.orange.withValues(alpha: 0.15)
                        : Colors.blue.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        Icons.code_outlined,
                        size: 16,
                        color: flavor == AppFlavor.dev ? Colors.orange.shade700 : Colors.blue.shade700,
                      ),
                      const SizedBox(width: 6),
                      Text(
                        flavor.name.toUpperCase(),
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          letterSpacing: 0.5,
                          color: flavor == AppFlavor.dev ? Colors.orange.shade700 : Colors.blue.shade700,
                        ),
                      ),
                    ],
                  ),
                ),
              PopupMenuButton<String>(
                icon: const Icon(Icons.more_vert_rounded),
                onSelected: (value) {
                  if (value == 'logout') {
                    context.read<AuthCubit>().logout();
                  }
                },
                itemBuilder: (context) => [
                  PopupMenuItem(
                    value: 'logout',
                    child: Row(
                      children: [
                        Icon(
                          Icons.logout_rounded,
                          size: 20,
                          color: theme.colorScheme.error,
                        ),
                        const SizedBox(width: 12),
                        Text(
                          'Cerrar sesión',
                          style: TextStyle(color: theme.colorScheme.error),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
          body: IndexedStack(
            index: state.section.index,
            children: [
              DashboardSection(session: session, flavor: flavor),
              OrdersSection(session: session),
              const AssetsSection(),
              ProfileSection(session: session),
            ],
          ),
          bottomNavigationBar: Container(
            decoration: BoxDecoration(
              color: theme.colorScheme.surface,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.08),
                  blurRadius: 20,
                  offset: const Offset(0, -4),
                  spreadRadius: 0,
                ),
              ],
            ),
            child: SafeArea(
              top: false,
              child: NavigationBar(
                selectedIndex: state.section.index,
                onDestinationSelected: (index) {
                  context.read<HomeCubit>().select(HomeSection.values[index]);
                },
                height: 72,
                backgroundColor: theme.colorScheme.surface,
                indicatorColor: theme.colorScheme.primaryContainer,
                labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
                destinations: [
                  NavigationDestination(
                    icon: Icon(
                      Icons.dashboard_outlined,
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                    selectedIcon: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.primaryContainer,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(
                        Icons.dashboard_rounded,
                        color: theme.colorScheme.onPrimaryContainer,
                      ),
                    ),
                    label: 'Dashboard',
                  ),
                  NavigationDestination(
                    icon: Icon(
                      Icons.assignment_outlined,
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                    selectedIcon: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: Colors.orange.shade50,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(
                        Icons.assignment_rounded,
                        color: Colors.orange.shade700,
                      ),
                    ),
                    label: 'Órdenes',
                  ),
                  NavigationDestination(
                    icon: Icon(
                      Icons.inventory_2_outlined,
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                    selectedIcon: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: Colors.green.shade50,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(
                        Icons.inventory_2_rounded,
                        color: Colors.green.shade700,
                      ),
                    ),
                    label: 'Activos',
                  ),
                  NavigationDestination(
                    icon: Icon(
                      Icons.person_outline,
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                    selectedIcon: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: Colors.purple.shade50,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(
                        Icons.person_rounded,
                        color: Colors.purple.shade700,
                      ),
                    ),
                    label: 'Perfil',
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  String _titleForSection(HomeSection section) {
    switch (section) {
      case HomeSection.dashboard:
        return 'Dashboard';
      case HomeSection.orders:
        return 'Órdenes de Trabajo';
      case HomeSection.assets:
        return 'Activos';
      case HomeSection.profile:
        return 'Mi Perfil';
    }
  }
}

