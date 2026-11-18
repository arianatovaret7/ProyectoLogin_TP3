<?php
/**
 * Archivo de arranque: se incluye al principio de cada página pública.
 * Carga la configuración, las funciones del proyecto e inicia la sesión.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/validaciones.php';
require_once __DIR__ . '/usuarios.php';
require_once __DIR__ . '/plantilla.php';

// Encabezados de seguridad básicos.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

iniciar_sesion();
