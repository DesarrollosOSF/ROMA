# Solución para Error de Instalación: INSTALL_FAILED_USER_RESTRICTED

## 🔴 Error
```
adb.exe: failed to install
[INSTALL_FAILED_USER_RESTRICTED: Install canceled by user]
```

## 📋 Causa
Este error ocurre cuando el dispositivo Android tiene restricciones de seguridad que impiden la instalación de aplicaciones desde fuentes desconocidas o vía USB.

## ✅ Soluciones

### Opción 1: Habilitar Instalación desde USB (Recomendado)

1. **En el dispositivo Android:**
   - Ve a **Configuración** > **Seguridad** (o **Configuración** > **Privacidad**)
   - Busca la opción **"Instalar aplicaciones desconocidas"** o **"Instalar vía USB"**
   - Activa la opción para **"Permitir desde esta fuente"** o **"Permitir instalación vía USB"**

2. **Si no encuentras la opción anterior:**
   - Ve a **Configuración** > **Opciones de desarrollador**
   - Si no tienes "Opciones de desarrollador" habilitadas:
     - Ve a **Configuración** > **Acerca del teléfono**
     - Toca 7 veces en **"Número de compilación"** o **"Versión de MIUI"**
   - Busca **"Instalar vía USB"** o **"Verificación de aplicaciones USB"**
   - Actívala

3. **Para dispositivos Xiaomi/MIUI específicamente:**
   - Ve a **Configuración** > **Opciones adicionales** > **Privacidad**
   - Activa **"Instalar vía USB"**
   - También verifica **"Depuración USB"** esté activada

### Opción 2: Instalar Manualmente el APK

1. **Generar el APK:**
   ```powershell
   flutter build apk --release --flavor prod --dart-define=FLAVOR=prod
   ```

2. **Transferir el APK al dispositivo:**
   - El APK estará en: `build\app\outputs\flutter-apk\app-prod-release.apk`
   - Transfiere el archivo al dispositivo vía USB, email, o almacenamiento en la nube

3. **Instalar en el dispositivo:**
   - Abre el archivo APK en el dispositivo
   - Si aparece un mensaje de seguridad, ve a **Configuración** > **Seguridad** > **"Permitir instalación desde esta fuente"**
   - Confirma la instalación

### Opción 3: Usar ADB Install con Flag de Forzar

```powershell
# Desinstalar versión anterior primero
adb uninstall com.example.roma_app_flutter

# Instalar con flag de forzar
adb install -r -d build\app\outputs\flutter-apk\app-prod-debug.apk
```

**Nota:** El flag `-r` reinstala si existe, `-d` permite downgrade.

## 🔧 Verificar Configuración ADB

1. **Verificar que el dispositivo esté conectado:**
   ```powershell
   adb devices
   ```
   Debe mostrar tu dispositivo con estado "device"

2. **Si el dispositivo aparece como "unauthorized":**
   - En el dispositivo, acepta el diálogo de "Permitir depuración USB"
   - Marca la casilla "Siempre permitir desde este equipo"

## 📱 Pasos Adicionales para Dispositivos Xiaomi

Los dispositivos Xiaomi tienen restricciones adicionales:

1. **Habilitar Depuración USB:**
   - Configuración > Opciones adicionales > Opciones de desarrollador
   - Activar "Depuración USB"

2. **Desactivar MIUI Optimization (si es necesario):**
   - Configuración > Opciones adicionales > Opciones de desarrollador
   - Desactivar "Optimización de MIUI"
   - Reiniciar el dispositivo

3. **Permitir instalación desde USB:**
   - Configuración > Opciones adicionales > Privacidad
   - Activar "Instalar vía USB"

## ✅ Verificación

Después de aplicar las soluciones, intenta nuevamente:

```powershell
flutter run --flavor prod --dart-define=FLAVOR=prod
```

O instala directamente:

```powershell
adb install -r build\app\outputs\flutter-apk\app-prod-debug.apk
```

## 🎯 Nota Importante

Este error **NO es un problema del código de la aplicación**, sino una restricción de seguridad del dispositivo Android. Una vez habilitadas las opciones correctas, la instalación funcionará normalmente.

