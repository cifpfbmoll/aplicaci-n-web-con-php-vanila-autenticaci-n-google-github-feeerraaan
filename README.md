# 🔐 Autenticación con Google OAuth en PHP

Aplicación simple de PHP que permite a los usuarios iniciar sesión con su cuenta de Google utilizando OAuth 2.0.

## 📋 Requisitos Previos

- PHP 7.4 o superior
- Composer
- Cuenta de Google Cloud Platform

## 🚀 Instalación

### 1. Instalar Dependencias

```bash
composer install
```

Esto instalará el paquete `google/apiclient` necesario para la autenticación OAuth.

### 2. Configurar Google Cloud Console

#### a) Crear un Proyecto

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Anota el nombre del proyecto

#### b) Habilitar la API de Google+

1. En el menú lateral, ve a **APIs y servicios** > **Biblioteca**
2. Busca "Google+ API" o "Google OAuth2 API"
3. Haz clic en **Habilitar**

#### c) Crear Credenciales OAuth 2.0

1. Ve a **APIs y servicios** > **Credenciales**
2. Haz clic en **Crear credenciales** > **ID de cliente de OAuth**
3. Si es la primera vez, configura la **pantalla de consentimiento OAuth**:
   - Tipo de usuario: **Externo** (para pruebas)
   - Nombre de la aplicación: El nombre que verán los usuarios
   - Correo electrónico de asistencia: Tu email
   - Ámbitos: Agrega `email` y `profile`
   - Guarda y continúa

4. Vuelve a **Crear credenciales** > **ID de cliente de OAuth**
5. Tipo de aplicación: **Aplicación web**
6. Nombre: "PHP Google Auth"
7. **URIs de redirección autorizados**: Agrega:
   - `http://localhost:8000/redirect.php`
   - (Si usas otro puerto o dominio, ajústalo)
8. Haz clic en **Crear**
9. **Copia el ID de cliente y el Secreto de cliente**

### 3. Configurar Credenciales

Edita el archivo `config.php` y reemplaza los valores:

```php
return [
    'client_id' => 'TU_CLIENT_ID.apps.googleusercontent.com',
    'client_secret' => 'TU_CLIENT_SECRET',
    'redirect_uri' => 'http://localhost:8000/redirect.php',
    'scopes' => [
        'email',
        'profile'
    ]
];
```

**⚠️ IMPORTANTE**: Nunca subas `config.php` a un repositorio público. Ya está incluido en `.gitignore`.

## 🏃 Ejecutar la Aplicación

### Opción 1: Servidor PHP integrado

```bash
php -S localhost:8000
```

### Opción 2: Apache/Nginx

Configura tu servidor web para servir el directorio del proyecto.

Luego abre tu navegador en: `http://localhost:8000`

## 📂 Estructura del Proyecto

```
php-google-auth/
├── composer.json          # Dependencias del proyecto
├── config.php            # Configuración OAuth (credenciales)
├── index.php             # Página principal con botón de login
├── redirect.php          # Callback de OAuth (procesa autenticación)
├── logout.php            # Cierra la sesión del usuario
├── .gitignore            # Archivos a ignorar en Git
└── README.md             # Este archivo
```

## 🔄 Flujo de Autenticación

### 1. **index.php** - Página Principal

Este archivo:
- Inicia una sesión PHP
- Carga el cliente de Google API
- Genera una URL de autenticación con `createAuthUrl()`
- Muestra un botón "Iniciar sesión con Google"
- Si el usuario ya está autenticado, muestra su información

**Código clave:**
```php
$client = new Google_Client();
$client->setClientId($config['client_id']);
$client->setClientSecret($config['client_secret']);
$client->setRedirectUri($config['redirect_uri']);
$client->addScope($config['scopes']);

$authUrl = $client->createAuthUrl();
```

### 2. **Redirección a Google**

Cuando el usuario hace clic en el botón:
- Es redirigido a Google
- Google muestra una pantalla de consentimiento
- El usuario autoriza los permisos (email y profile)

### 3. **redirect.php** - Callback de OAuth

Después de la autorización, Google redirige aquí con un código:

**Paso 1: Recibir el código**
```php
if (!isset($_GET['code'])) {
    header('Location: index.php');
    exit;
}
```

**Paso 2: Intercambiar código por token de acceso**
```php
$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
$client->setAccessToken($token);
```

Este es el paso más importante: el código de autorización es temporal y de un solo uso. Se intercambia por un **token de acceso** que permite hacer peticiones a la API de Google.

**Paso 3: Obtener información del usuario**
```php
$oauth2 = new Google_Service_Oauth2($client);
$userInfo = $oauth2->userinfo->get();
```

**Paso 4: Guardar datos en la sesión**
```php
$_SESSION['user'] = [
    'id' => $userInfo->id,
    'email' => $userInfo->email,
    'name' => $userInfo->name,
    'given_name' => $userInfo->givenName,
    'family_name' => $userInfo->familyName,
    'picture' => $userInfo->picture,
    'verified_email' => $userInfo->verifiedEmail,
    'locale' => $userInfo->locale
];
```

**Paso 5: Redirigir al inicio**
```php
header('Location: index.php');
```

## 👤 Acceder a los Datos del Usuario

Una vez autenticado, puedes acceder a los datos del usuario desde `$_SESSION['user']`:

### Datos Disponibles

```php
// ID único de Google del usuario
$userId = $_SESSION['user']['id'];

// Email verificado
$email = $_SESSION['user']['email'];

// Nombre completo
$fullName = $_SESSION['user']['name'];

// Nombre
$firstName = $_SESSION['user']['given_name'];

// Apellido
$lastName = $_SESSION['user']['family_name'];

// URL de la foto de perfil
$profilePicture = $_SESSION['user']['picture'];

// Email verificado (boolean)
$isVerified = $_SESSION['user']['verified_email'];

// Idioma preferido
$locale = $_SESSION['user']['locale'];
```

### Ejemplo de Uso

```php
<?php
session_start();

if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
    
    // Iniciar sesión en tu sistema
    // Por ejemplo, buscar o crear usuario en tu base de datos
    
    echo "Bienvenido, " . htmlspecialchars($user['name']);
    echo "Tu email es: " . htmlspecialchars($user['email']);
    
    // Puedes usar el ID de Google como identificador único
    // para vincular con tu base de datos
    $googleId = $user['id'];
    
    // Ejemplo: guardar en base de datos
    // $db->query("INSERT INTO users (google_id, email, name) 
    //             VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE ...");
} else {
    echo "No has iniciado sesión";
}
?>
```

## 🔒 Seguridad

### Buenas Prácticas Implementadas

1. **Sesiones PHP**: Los datos del usuario se almacenan en sesiones del servidor
2. **Validación de tokens**: Se verifica que el token sea válido
3. **HTTPS en producción**: Siempre usa HTTPS en producción
4. **Protección de credenciales**: `config.php` está en `.gitignore`
5. **Escape de HTML**: Se usa `htmlspecialchars()` para prevenir XSS

### Recomendaciones Adicionales

- **Producción**: Cambia `redirect_uri` a tu dominio real con HTTPS
- **Tokens de actualización**: Guarda `refresh_token` para mantener sesiones largas
- **Base de datos**: Almacena usuarios en una base de datos
- **Validación de email**: Verifica `verified_email` antes de confiar en el email
- **CSRF**: Implementa tokens CSRF para formularios críticos

## 🐛 Solución de Problemas

### Error: "redirect_uri_mismatch"

**Causa**: La URI de redirección no coincide con la configurada en Google Cloud Console.

**Solución**: 
- Verifica que `redirect_uri` en `config.php` sea exactamente igual a la configurada en Google Cloud Console
- Incluye el protocolo (`http://` o `https://`)
- No incluyas parámetros de consulta

### Error: "invalid_client"

**Causa**: El Client ID o Client Secret son incorrectos.

**Solución**: 
- Verifica que copiaste correctamente las credenciales
- Asegúrate de no tener espacios adicionales

### Error: "access_denied"

**Causa**: El usuario canceló la autorización.

**Solución**: Normal, el usuario decidió no autorizar. Maneja este caso en tu aplicación.

### La sesión no persiste

**Causa**: Las sesiones PHP no están configuradas correctamente.

**Solución**: 
- Verifica que `session_start()` esté al inicio de cada archivo
- Comprueba los permisos del directorio de sesiones PHP

## 📚 Recursos Adicionales

- [Documentación oficial de Google OAuth 2.0](https://developers.google.com/identity/protocols/oauth2)
- [Google API Client para PHP](https://github.com/googleapis/google-api-php-client)
- [Consola de Google Cloud](https://console.cloud.google.com/)

## 📝 Licencia

Este proyecto es de código abierto y está disponible bajo la licencia MIT.

---

**¿Necesitas ayuda?** Revisa la sección de solución de problemas o consulta la documentación oficial de Google.
