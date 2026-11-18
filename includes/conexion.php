<?php
/**
 * Conexión a MySQL mediante PDO (patrón de instancia única).
 * Se usan consultas preparadas en todo el proyecto para evitar inyección SQL.
 */
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $ex) {
        error_log('[Proyecto Login] Error de conexión: ' . $ex->getMessage());
        mostrar_error_conexion();
    }

    return $pdo;
}

/**
 * Página amigable cuando la base de datos no está disponible.
 * No se muestra el detalle técnico del error al usuario.
 */
function mostrar_error_conexion(): void
{
    http_response_code(500);
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error de conexión | <?= e(APP_NOMBRE) ?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<main class="contenedor">
    <section class="tarjeta">
        <h1>No se pudo conectar</h1>
        <div class="alerta alerta-error" role="alert">
            No fue posible conectarse a la base de datos.
        </div>
        <ul class="lista-ayuda">
            <li>Verificá que <strong>MySQL</strong> esté iniciado en el panel de XAMPP (puerto 3306).</li>
            <li>Importá el archivo <code>database/proyecto_login.sql</code> desde phpMyAdmin.</li>
            <li>Revisá los datos de acceso en <code>config/config.php</code>.</li>
        </ul>
    </section>
</main>
</body>
</html>
    <?php
    exit;
}
