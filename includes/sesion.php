<?php
/**
 * Manejo de sesión, protección CSRF, mensajes flash y utilidades generales.
 */
declare(strict_types=1);

/** Inicia la sesión con parámetros seguros para la cookie. */
function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_name('PROYECTOLOGIN');
    session_set_cookie_params([
        'lifetime' => 0,          // la cookie dura hasta cerrar el navegador
        'path'     => '/',
        'secure'   => $https,     // solo por HTTPS cuando se usa el puerto 443
        'httponly' => true,       // JavaScript no puede leer la cookie
        'samesite' => 'Lax',
    ]);

    session_start();
}

/** Escapa texto para mostrarlo en HTML (previene XSS). */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirige a otra página y corta la ejecución. */
function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Devuelve true si el formulario se envió por POST. */
function es_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Lee un campo de texto enviado por POST, siempre como string. */
function campo_post(string $nombre): string
{
    $valor = $_POST[$nombre] ?? '';
    return is_string($valor) ? $valor : '';
}

// ---------------------------------------------------------------------
// Protección CSRF
// ---------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function campo_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valido(): bool
{
    $token = campo_post('csrf');
    return $token !== ''
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

// ---------------------------------------------------------------------
// Mensajes flash (se muestran una sola vez, luego de una redirección)
// ---------------------------------------------------------------------

function set_flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function obtener_flash(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}

// ---------------------------------------------------------------------
// Autenticación
// ---------------------------------------------------------------------

function usuario_logueado(): bool
{
    return isset($_SESSION['usuario']['id']);
}

/** Guarda los datos del usuario en la sesión luego de un login correcto. */
function crear_sesion_usuario(array $usuario, ?string $ultimoAcceso): void
{
    // Nuevo identificador de sesión: evita ataques de fijación de sesión.
    session_regenerate_id(true);

    $_SESSION['usuario'] = [
        'id'            => (int) $usuario['id_usuario'],
        'nombre'        => $usuario['nombre'],
        'apellido'      => $usuario['apellido'],
        'email'         => $usuario['email'],
        'fecha_alta'    => $usuario['fecha_alta'],
        'ultimo_acceso' => $ultimoAcceso,
    ];
    $_SESSION['ultima_actividad'] = time();
}

/** Vacía la sesión y genera un identificador nuevo (cierre de sesión). */
function cerrar_sesion(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

/** Protege las vistas privadas: si no hay sesión válida vuelve al login. */
function requerir_login(): void
{
    if (!usuario_logueado()) {
        set_flash('error', 'Debés iniciar sesión para acceder a esa página.');
        redirigir('index.php');
    }

    $inactivo = time() - (int) ($_SESSION['ultima_actividad'] ?? 0);
    if ($inactivo > SESION_INACTIVIDAD) {
        cerrar_sesion();
        set_flash('info', 'Tu sesión expiró por inactividad. Volvé a ingresar.');
        redirigir('index.php');
    }

    $_SESSION['ultima_actividad'] = time();
}

/** Las pantallas de login/registro no tienen sentido si ya hay sesión. */
function redirigir_si_logueado(): void
{
    if (usuario_logueado()) {
        redirigir('dashboard.php');
    }
}

// ---------------------------------------------------------------------
// Datos del cliente
// ---------------------------------------------------------------------

function ip_cliente(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function navegador_cliente(): ?string
{
    $agente = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    return $agente === '' ? null : mb_substr($agente, 0, 255);
}

/** Formatea una fecha de la base de datos como dd/mm/aaaa hh:mm. */
function formatear_fecha(?string $fecha, bool $conHora = true): string
{
    if ($fecha === null || $fecha === '') {
        return '-';
    }
    $marca = strtotime($fecha);
    if ($marca === false) {
        return '-';
    }
    return date($conHora ? 'd/m/Y H:i' : 'd/m/Y', $marca);
}

/** Fecha y hora actual en formato MySQL (DATETIME). */
function ahora(int $sumarSegundos = 0): string
{
    return date('Y-m-d H:i:s', time() + $sumarSegundos);
}
