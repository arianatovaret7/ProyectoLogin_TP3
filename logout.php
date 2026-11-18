<?php
/**
 * logout.php - Cierra la sesión del usuario y vuelve al login.
 * Solo acepta POST con token CSRF (un enlace externo no puede cerrar la sesión).
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (!es_post() || !csrf_valido()) {
    redirigir(usuario_logueado() ? 'dashboard.php' : 'index.php');
}

cerrar_sesion();
set_flash('exito', 'Cerraste sesión correctamente.');
redirigir('index.php');
