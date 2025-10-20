<?php
/**
 * Página principal de autenticación con Google OAuth 2.0
 * 
 * Funcionalidad:
 * - Si el usuario NO está autenticado: muestra el botón de login con Google
 * - Si el usuario YA está autenticado: muestra su perfil (foto, nombre, email)
 * 
 * Flujo:
 * 1. Inicializa el cliente de Google con las credenciales de config.php
 * 2. Genera la URL de autenticación que redirige a Google
 * 3. Verifica si existe $_SESSION['user'] para determinar el estado de autenticación
 */

// Iniciar sesión PHP para mantener el estado del usuario
session_start();

// Cargar autoloader de Composer (incluye Google API Client)
require_once __DIR__ . '/vendor/autoload.php';

// Cargar configuración (client_id, client_secret, redirect_uri, scopes)
$config = require __DIR__ . '/config.php';

// Inicializar el cliente de Google OAuth 2.0
$client = new Google_Client();
$client->setClientId($config['client_id']);           // ID de cliente de Google Cloud Console
$client->setClientSecret($config['client_secret']);   // Secreto de cliente
$client->setRedirectUri($config['redirect_uri']);     // URL de callback (redirect.php)
$client->addScope($config['scopes']);                 // Permisos solicitados (email, profile)

// Generar URL de autenticación que redirige a Google
// Esta URL incluye todos los parámetros necesarios para el flujo OAuth
$authUrl = $client->createAuthUrl();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión con Google</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 450px;
            width: 100%;
            text-align: center;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        p {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .google-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: white;
            color: #444;
            border: 2px solid #ddd;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            gap: 12px;
        }
        
        .google-btn:hover {
            background: #f8f9fa;
            border-color: #4285f4;
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.2);
            transform: translateY(-2px);
        }
        
        .google-icon {
            width: 20px;
            height: 20px;
        }
        
        .user-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            margin-top: 20px;
        }
        
        .user-info img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 16px;
            border: 3px solid #667eea;
        }
        
        .user-info h2 {
            color: #333;
            margin-bottom: 8px;
            font-size: 22px;
        }
        
        .user-info p {
            color: #666;
            margin-bottom: 8px;
        }
        
        .logout-btn {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 16px;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Autenticación con Google</h1>
        
        <?php if (isset($_SESSION['user'])): ?>
            <!-- USUARIO AUTENTICADO: Mostrar perfil -->
            <p>¡Bienvenido de nuevo!</p>
            <div class="user-info">
                <!-- Foto de perfil de Google -->
                <?php if (isset($_SESSION['user']['picture'])): ?>
                    <img src="<?php echo htmlspecialchars($_SESSION['user']['picture']); ?>" alt="Foto de perfil">
                <?php endif; ?>
                
                <!-- Nombre completo del usuario -->
                <h2><?php echo htmlspecialchars($_SESSION['user']['name']); ?></h2>
                
                <!-- Email verificado de Google -->
                <p><strong>Email:</strong><br>
                <?php echo htmlspecialchars($_SESSION['user']['email']); ?></p>
                
                <!-- ID único de Google (útil para vincular con tu base de datos) -->
                <?php if (isset($_SESSION['user']['id'])): ?>
                    <p><strong>Google ID:</strong><br>
                    <?php echo htmlspecialchars($_SESSION['user']['id']); ?></p>
                <?php endif; ?>
                
                <!-- Botón para cerrar sesión -->
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
        <?php else: ?>
            <!-- USUARIO NO AUTENTICADO: Mostrar botón de login -->
            <p>Inicia sesión con tu cuenta de Google para acceder a la aplicación. Es rápido, seguro y no necesitas crear una nueva cuenta.</p>
            
            <!-- Botón que redirige a Google OAuth -->
            <a href="<?php echo htmlspecialchars($authUrl); ?>" class="google-btn">
                <!-- Logo oficial de Google en SVG -->
                <svg class="google-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Iniciar sesión con Google
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
