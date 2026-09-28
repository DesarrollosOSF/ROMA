enum AppFlavor {
  dev,
  qa,
  prod,
}

extension AppFlavorX on AppFlavor {
  String get name {
    switch (this) {
      case AppFlavor.dev:
        return 'dev';
      case AppFlavor.qa:
        return 'qa';
      case AppFlavor.prod:
        return 'prod';
    }
  }

  String get envFileName {
    switch (this) {
      case AppFlavor.dev:
        return 'env/dev.env';
      case AppFlavor.qa:
        return 'env/qa.env';
      case AppFlavor.prod:
        return 'env/prod.env';
    }
  }

  bool get enableNetworkLogging {
    switch (this) {
      case AppFlavor.dev:
        return true;
      case AppFlavor.qa:
        return true;
      case AppFlavor.prod:
        return false;
    }
  }

  static AppFlavor fromName(String name) {
    final normalized = name.trim().toLowerCase();
    switch (normalized) {
      case 'qa':
        return AppFlavor.qa;
      case 'prod':
      case 'production':
        return AppFlavor.prod;
      case 'dev':
      case 'development':
      default:
        return AppFlavor.dev;
    }
  }
}


