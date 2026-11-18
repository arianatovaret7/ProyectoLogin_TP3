<?php
/**
 * Configuración general del Proyecto Login.
 *
 * Valores pensados para XAMPP en localhost (127.0.0.1):
 *  - MySQL / MariaDB en el puerto 3306, usuario "root" sin contraseña.
 *  - Apache en los puertos 80 (HTTP) y 443 (HTTPS).
 * Si tu instalación usa otros datos, modificalos acá.
 */
declare(strict_types=1);

// ---------- Base de datos ----------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'proyecto_login');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---------- Aplicación ----------
define('APP_NOMBRE', 'Proyecto Login');
define('APP_VERSION', '1.0.0');

// Tiempo máximo de inactividad de la sesión (en segundos): 30 minutos.
define('SESION_INACTIVIDAD', 30 * 60);

// Protección contra fuerza bruta: intentos fallidos permitidos y minutos de bloqueo.
define('MAX_INTENTOS', 5);
define('BLOQUEO_MINUTOS', 15);

// Validez (en minutos) del enlace para restablecer la contraseña.
define('TOKEN_EXPIRA_MINUTOS', 30);

// En localhost no hay servidor de correo configurado: si está en true,
// el enlace de recuperación se muestra en pantalla en lugar de enviarse por email.
define('MODO_LOCAL', true);

date_default_timezone_set('America/Argentina/Buenos_Aires');
