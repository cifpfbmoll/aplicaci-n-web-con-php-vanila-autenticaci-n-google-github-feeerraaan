<?php
session_start();

// Cargar autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

// Cargar configuración
$config = require __DIR__ . '/config.php';

// Inicializar el cliente de Google
$client = new Google_Client();
$client->setClientId($config['client_id']);
$client->setClientSecret($config['client_secret']);
$client->setRedirectUri($config['redirect_uri']);
$client->addScope($config['scopes']);

// Verificar si hay un código de autorización en la URL
if (!isset($_GET['code'])) {
    // Si no hay código, verificar si hay un error
    if (isset($_GET['error'])) {
        die('Error de autenticación: ' . htmlspecialchars($_GET['error']));
    }
    
    // Si no hay código ni error, redirigir al inicio
    header('Location: index.php');
    exit;
}

try {
    // Intercambiar el código de autorización por un token de acceso
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    // Verificar si hubo un error al obtener el token
    if (isset($token['error'])) {
        throw new Exception('Error al obtener el token: ' . $token['error']);
    }
    
    // Establecer el token de acceso en el cliente
    $client->setAccessToken($token);
    
    // Obtener información del perfil del usuario
    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();
    
    // Extraer datos del usuario
    $userData = [
        'id' => $userInfo->id,
        'email' => $userInfo->email,
        'name' => $userInfo->name,
        'given_name' => $userInfo->givenName,
        'family_name' => $userInfo->familyName,
        'picture' => $userInfo->picture,
        'verified_email' => $userInfo->verifiedEmail,
        'locale' => $userInfo->locale
    ];
    
    // Guardar información del usuario en la sesión
    $_SESSION['user'] = $userData;
    $_SESSION['access_token'] = $token;
    
    // Opcional: Guardar el token de actualización si está disponible
    if (isset($token['refresh_token'])) {
        $_SESSION['refresh_token'] = $token['refresh_token'];
    }
    
    // Redirigir al usuario a la página principal
    header('Location: index.php');
    exit;
    
} catch (Exception $e) {
    // Manejar errores
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
