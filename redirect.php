<?php
/**
 * Callback de OAuth 2.0 - Procesa la respuesta de Google
 * 
 * Este archivo es el corazón del proceso de autenticación OAuth.
 * Google redirige aquí después de que el usuario autoriza la aplicación.
 * 
 * Flujo:
 * 1. Recibe el código de autorización de Google ($_GET['code'])
 * 2. Intercambia el código por un token de acceso
 * 3. Usa el token para obtener información del usuario
 * 4. Guarda los datos en la sesión
 * 5. Redirige al usuario a index.php
 */

// Iniciar sesión para almacenar datos del usuario
session_start();

// Cargar autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

// Cargar configuración OAuth
$config = require __DIR__ . '/config.php';

// Inicializar el cliente de Google con las mismas credenciales que index.php
$client = new Google_Client();
$client->setClientId($config['client_id']);
$client->setClientSecret($config['client_secret']);
$client->setRedirectUri($config['redirect_uri']);
$client->addScope($config['scopes']);

// PASO 1: Verificar si Google envió un código de autorización
if (!isset($_GET['code'])) {
    // Caso 1: El usuario canceló o hubo un error en Google
    if (isset($_GET['error'])) {
        die('Error de autenticación: ' . htmlspecialchars($_GET['error']));
    }
    
    // Caso 2: Acceso directo sin código (no debería pasar)
    header('Location: index.php');
    exit;
}

try {
    // PASO 2: Intercambiar el código de autorización por un token de acceso
    // Este es el paso más importante: el código es temporal y de un solo uso
    // Se envía al servidor de Google junto con el client_secret para obtener el token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    // Verificar si Google devolvió un error al intercambiar el código
    if (isset($token['error'])) {
        throw new Exception('Error al obtener el token: ' . $token['error']);
    }
    
    // PASO 3: Configurar el token en el cliente para hacer peticiones autenticadas
    $client->setAccessToken($token);
    
    // PASO 4: Obtener información del perfil del usuario desde Google
    // Creamos un servicio OAuth2 para acceder a la API de información de usuario
    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();
    
    // PASO 5: Extraer y estructurar los datos del usuario
    $userData = [
        'id' => $userInfo->id,                          // ID único de Google (usar como clave primaria)
        'email' => $userInfo->email,                    // Email verificado por Google
        'name' => $userInfo->name,                      // Nombre completo
        'given_name' => $userInfo->givenName,           // Nombre
        'family_name' => $userInfo->familyName,         // Apellido
        'picture' => $userInfo->picture,                // URL de la foto de perfil
        'verified_email' => $userInfo->verifiedEmail,   // true si Google verificó el email
        'locale' => $userInfo->locale                   // Idioma preferido (ej: 'es', 'en')
    ];
    
    // PASO 6: Guardar información del usuario en la sesión PHP
    $_SESSION['user'] = $userData;              // Datos del usuario para mostrar en la UI
    $_SESSION['access_token'] = $token;         // Token para futuras peticiones a Google APIs
    
    // PASO 7: Guardar el refresh token si está disponible (solo la primera vez)
    // El refresh token permite renovar el access token sin pedir autorización de nuevo
    if (isset($token['refresh_token'])) {
        $_SESSION['refresh_token'] = $token['refresh_token'];
    }
    
    // PASO 8: Redirigir al usuario a la página principal (ya autenticado)
    header('Location: index.php');
    exit;
    
} catch (Exception $e) {
    // Manejo de errores: Mostrar página de error amigable
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error de Autenticación</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: #f5f5f5;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
            }
            .error-container {
                background: white;
                border-radius: 12px;
                padding: 40px;
                max-width: 500px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                text-align: center;
            }
            h1 {
                color: #dc3545;
                margin-bottom: 20px;
            }
            p {
                color: #666;
                line-height: 1.6;
                margin-bottom: 24px;
            }
            a {
                display: inline-block;
                background: #667eea;
                color: white;
                padding: 12px 24px;
                border-radius: 8px;
                text-decoration: none;
                transition: background 0.3s;
            }
            a:hover {
                background: #5568d3;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>❌ Error de Autenticación</h1>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
            <a href="index.php">Volver al inicio</a>
        </div>
    </body>
    </html>';
    exit;
}
