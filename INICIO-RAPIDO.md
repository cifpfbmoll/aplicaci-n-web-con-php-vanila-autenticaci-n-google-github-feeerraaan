# ⚡ Inicio Rápido - 5 Minutos

Guía express para poner en marcha la autenticación con Google.

## 🎯 Pasos Rápidos

### 1️⃣ Instalar Dependencias (1 min)

```bash
composer install
```

### 2️⃣ Obtener Credenciales de Google (2 min)

1. Ve a: https://console.cloud.google.com/apis/credentials
2. Crea un proyecto nuevo
3. **Crear credenciales** → **ID de cliente de OAuth**
4. Configura la pantalla de consentimiento (nombre de app + email)
5. Tipo: **Aplicación web**
6. URI de redirección: `http://localhost:8000/redirect.php`
7. Copia el **Client ID** y **Client Secret**

### 3️⃣ Configurar Credenciales (1 min)

Edita `config.php`:

```php
return [
    'client_id' => 'PEGA_AQUI_TU_CLIENT_ID',
    'client_secret' => 'PEGA_AQUI_TU_CLIENT_SECRET',
    'redirect_uri' => 'http://localhost:8000/redirect.php',
    'scopes' => ['email', 'profile']
];
```

### 4️⃣ Iniciar Servidor (30 seg)

```bash
php -S localhost:8000
```

### 5️⃣ Probar (30 seg)

1. Abre: http://localhost:8000
2. Click en "Iniciar sesión con Google"
3. Autoriza la aplicación
4. ¡Listo! Verás tu perfil

## 🎨 Personalización Rápida

### Cambiar el puerto

```bash
php -S localhost:3000
```

No olvides actualizar `redirect_uri` en `config.php` y en Google Cloud Console.

### Usar tu dominio

En `config.php`:
```php
'redirect_uri' => 'https://tudominio.com/redirect.php',
```

Y agrégalo en Google Cloud Console.

## 🐛 Problemas Comunes

### ❌ "redirect_uri_mismatch"
**Solución**: La URI en `config.php` debe ser EXACTAMENTE igual a la de Google Cloud Console.

### ❌ "invalid_client"
**Solución**: Verifica que copiaste bien el Client ID y Secret (sin espacios extra).

### ❌ Página en blanco
**Solución**: Revisa que ejecutaste `composer install`.

## 📖 Documentación Completa

- **README.md**: Guía completa paso a paso
- **FLUJO-OAUTH.md**: Explicación técnica del flujo OAuth
- **config.example.php**: Plantilla de configuración

## 🔐 Acceder a Datos del Usuario

En cualquier página PHP:

```php
<?php
session_start();

if (isset($_SESSION['user'])) {
    $email = $_SESSION['user']['email'];
    $nombre = $_SESSION['user']['name'];
    $foto = $_SESSION['user']['picture'];
    
    echo "Hola, $nombre ($email)";
}
?>
```

## 🚀 Siguiente Entrega

Replica este proyecto e integra esto con tu base de datos:

```php
// En redirect.php, después de obtener $userData
$googleId = $userData['id'];
$email = $userData['email'];
$name = $userData['name'];

// Buscar o crear usuario en tu BD
$stmt = $pdo->prepare("
    INSERT INTO users (google_id, email, name, created_at) 
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE 
        email = VALUES(email),
        name = VALUES(name),
        last_login = NOW()
");
$stmt->execute([$googleId, $email, $name]);
```

## 💡 Tips

- ✅ Usa HTTPS en producción
- ✅ Nunca subas `config.php` a Git (ya está en `.gitignore`)
- ✅ Guarda el `refresh_token` para sesiones largas
- ✅ Verifica `verified_email` antes de confiar en el email

---

**¿Necesitas ayuda?** Consulta el README.md completo o la documentación de Google OAuth.
