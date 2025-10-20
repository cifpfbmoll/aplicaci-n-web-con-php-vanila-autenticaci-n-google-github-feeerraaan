<?php
/**
 * Configuración de Google OAuth - PLANTILLA
 * 
 * 1. Copia este archivo como 'config.php'
 * 2. Reemplaza los valores con tus credenciales reales
 * 3. Obtén las credenciales desde: https://console.cloud.google.com/apis/credentials
 */

return [
    // ID de cliente OAuth 2.0
    // Ejemplo: '123456789-abcdefghijklmnop.apps.googleusercontent.com'
    'client_id' => 'TU_CLIENT_ID.apps.googleusercontent.com',
    
    // Secreto de cliente OAuth 2.0
    // Ejemplo: 'GOCSPX-abcdefghijklmnopqrstuvwx'
    'client_secret' => 'TU_CLIENT_SECRET',
    
    // URL de redirección (debe coincidir EXACTAMENTE con la configurada en Google Cloud Console)
    // Para desarrollo local: 'http://localhost:8000/redirect.php'
    // Para producción: 'https://tudominio.com/redirect.php'
    'redirect_uri' => 'http://localhost:8000/redirect.php',
    
    // Scopes solicitados (permisos que necesita tu aplicación)
    'scopes' => [
        'email',    // Acceso al email del usuario
        'profile'   // Acceso al perfil básico (nombre, foto)
    ]
];
