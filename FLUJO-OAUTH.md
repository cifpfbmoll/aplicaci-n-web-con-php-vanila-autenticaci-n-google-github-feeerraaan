# 🔄 Flujo Detallado de OAuth 2.0 con Google

## Diagrama del Flujo

```
┌─────────────┐                                    ┌──────────────┐
│   Usuario   │                                    │    Google    │
│  (Navegador)│                                    │   OAuth 2.0  │
└──────┬──────┘                                    └──────┬───────┘
       │                                                  │
       │  1. Visita index.php                            │
       ├──────────────────────────────►                  │
       │                              │                  │
       │  2. Muestra botón de login  │                  │
       │◄──────────────────────────────                  │
       │                                                  │
       │  3. Click en "Iniciar sesión con Google"       │
       ├─────────────────────────────────────────────────►
       │                                                  │
       │  4. Pantalla de consentimiento de Google       │
       │◄─────────────────────────────────────────────────
       │                                                  │
       │  5. Usuario autoriza permisos                   │
       ├─────────────────────────────────────────────────►
       │                                                  │
       │  6. Redirección a redirect.php?code=XXXXX      │
       │◄─────────────────────────────────────────────────
       │                                                  │
┌──────▼──────┐                                          │
│ redirect.php│                                          │
└──────┬──────┘                                          │
       │  7. Intercambio: código → token de acceso      │
       ├─────────────────────────────────────────────────►
       │                                                  │
       │  8. Respuesta con access_token                 │
       │◄─────────────────────────────────────────────────
       │                                                  │
       │  9. Solicitud de información del usuario       │
       ├─────────────────────────────────────────────────►
       │                                                  │
       │  10. Datos del usuario (email, nombre, foto)   │
       │◄─────────────────────────────────────────────────
       │                                                  │
       │  11. Guardar en $_SESSION['user']              │
       │                                                  │
       │  12. Redirección a index.php                    │
       ├──────────────────────────────►                  │
       │                              │                  │
       │  13. Muestra perfil usuario │                  │
       │◄──────────────────────────────                  │
       │                                                  │
```

## Explicación Paso a Paso

### **Paso 1-2: Página Inicial (index.php)**

El usuario visita `index.php`. El servidor PHP:
- Inicia una sesión
- Crea un cliente de Google con las credenciales
- Genera una URL de autenticación

```php
$client = new Google_Client();
$authUrl = $client->createAuthUrl();
// Resultado: https://accounts.google.com/o/oauth2/auth?client_id=...&redirect_uri=...
```

### **Paso 3-5: Autenticación en Google**

Cuando el usuario hace clic en el botón:
1. Es redirigido a Google
2. Google muestra qué permisos solicita la app (email, profile)
3. El usuario acepta o rechaza

**URL de ejemplo:**
```
https://accounts.google.com/o/oauth2/auth?
  client_id=123456789.apps.googleusercontent.com
  &redirect_uri=http://localhost:8000/redirect.php
  &scope=email+profile
  &response_type=code
```

### **Paso 6: Redirección con Código**

Si el usuario acepta, Google redirige a:
```
http://localhost:8000/redirect.php?code=4/0AY0e-g7X...
```

Este **código de autorización** es:
- ✅ Temporal (expira en ~10 minutos)
- ✅ De un solo uso
- ✅ Debe intercambiarse por un token

### **Paso 7-8: Intercambio de Código por Token**

`redirect.php` recibe el código y lo intercambia:

```php
$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
```

**Petición interna (realizada por la librería):**
```http
POST https://oauth2.googleapis.com/token
Content-Type: application/x-www-form-urlencoded

code=4/0AY0e-g7X...
&client_id=123456789.apps.googleusercontent.com
&client_secret=GOCSPX-abc123...
&redirect_uri=http://localhost:8000/redirect.php
&grant_type=authorization_code
```

**Respuesta de Google:**
```json
{
  "access_token": "ya29.a0AfH6SMBx...",
  "expires_in": 3599,
  "token_type": "Bearer",
  "scope": "https://www.googleapis.com/auth/userinfo.email ...",
  "refresh_token": "1//0gX..." // Solo la primera vez
}
```

### **Paso 9-10: Obtener Información del Usuario**

Con el `access_token`, ahora podemos pedir datos del usuario:

```php
$client->setAccessToken($token);
$oauth2 = new Google_Service_Oauth2($client);
$userInfo = $oauth2->userinfo->get();
```

**Petición interna:**
```http
GET https://www.googleapis.com/oauth2/v2/userinfo
Authorization: Bearer ya29.a0AfH6SMBx...
```

**Respuesta:**
```json
{
  "id": "1234567890",
  "email": "usuario@gmail.com",
  "verified_email": true,
  "name": "Juan Pérez",
  "given_name": "Juan",
  "family_name": "Pérez",
  "picture": "https://lh3.googleusercontent.com/a/...",
  "locale": "es"
}
```

### **Paso 11: Guardar en Sesión**

Los datos se almacenan en la sesión PHP:

```php
$_SESSION['user'] = [
    'id' => $userInfo->id,
    'email' => $userInfo->email,
    'name' => $userInfo->name,
    // ... más datos
];
```

### **Paso 12-13: Vuelta a la Página Principal**

El usuario es redirigido a `index.php`, que ahora detecta:
```php
if (isset($_SESSION['user'])) {
    // Mostrar perfil del usuario
}
```

## 🔑 Conceptos Clave

### **Authorization Code (Código de Autorización)**
- Código temporal que Google devuelve después de la autorización
- Se intercambia por un token de acceso
- Expira rápidamente (~10 minutos)
- Solo se puede usar una vez

### **Access Token (Token de Acceso)**
- Permite hacer peticiones a las APIs de Google
- Expira en ~1 hora
- Se envía en el header `Authorization: Bearer <token>`
- No debe exponerse públicamente

### **Refresh Token (Token de Actualización)**
- Solo se obtiene la primera vez que el usuario autoriza
- Permite obtener nuevos access tokens sin pedir autorización de nuevo
- Dura mucho tiempo (puede ser indefinido)
- Debe guardarse de forma segura

### **Scopes (Ámbitos)**
- Definen qué permisos solicitas
- `email`: Acceso al correo electrónico
- `profile`: Acceso al perfil básico (nombre, foto)
- Más scopes: [Lista completa](https://developers.google.com/identity/protocols/oauth2/scopes)

## 🛡️ Seguridad

### ¿Por qué este flujo es seguro?

1. **El código nunca se expone al navegador**: El intercambio código→token ocurre en el servidor
2. **El client_secret nunca sale del servidor**: Solo el servidor lo conoce
3. **Los tokens son temporales**: Si se filtran, expiran pronto
4. **HTTPS en producción**: Cifra toda la comunicación

### ¿Qué puede salir mal?

| Problema | Causa | Solución |
|----------|-------|----------|
| `redirect_uri_mismatch` | La URI no coincide exactamente | Verifica en Google Cloud Console |
| `invalid_client` | Client ID/Secret incorrectos | Copia de nuevo las credenciales |
| `access_denied` | Usuario canceló | Normal, maneja el error |
| Token expirado | Access token caducó | Usa el refresh token para renovar |

## 📊 Comparación: OAuth vs Login Tradicional

| Aspecto | Login Tradicional | OAuth con Google |
|---------|-------------------|------------------|
| **Contraseñas** | Debes almacenarlas (hash) | Google las maneja |
| **Seguridad** | Responsabilidad tuya | Google se encarga |
| **Recuperación** | Debes implementar "olvidé mi contraseña" | Google lo maneja |
| **2FA** | Debes implementarlo | Google lo incluye |
| **UX** | Usuario crea otra cuenta | Usa cuenta existente |
| **Verificación email** | Debes enviar emails | Google ya verificó |

## 🔄 Renovación de Tokens

Si guardas el `refresh_token`, puedes renovar el acceso:

```php
if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($_SESSION['refresh_token']);
    $newToken = $client->getAccessToken();
    $_SESSION['access_token'] = $newToken;
}
```

## 📚 Recursos Adicionales

- [RFC 6749 - OAuth 2.0](https://tools.ietf.org/html/rfc6749)
- [Google Identity Platform](https://developers.google.com/identity)
- [OAuth 2.0 Playground](https://developers.google.com/oauthplayground/)

---

**💡 Tip**: Usa el [OAuth 2.0 Playground](https://developers.google.com/oauthplayground/) de Google para experimentar con el flujo y ver las peticiones/respuestas reales.
