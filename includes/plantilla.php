<?php
/**
 * Plantilla HTML común (encabezado, pie, mensajes y campos de formulario).
 */
declare(strict_types=1);

function encabezado(string $titulo, string $claseTarjeta = ''): void
{
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Proyecto Login - Análisis de Sistemas">
    <title><?= e($titulo) ?> | <?= e(APP_NOMBRE) ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔐</text></svg>">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <script src="assets/js/validaciones.js" defer></script>
</head>
<body>
<main class="contenedor">
    <section class="tarjeta <?= e($claseTarjeta) ?>">
    <?php
}

function pie(): void
{
    ?>
    </section>
</main>
<footer class="pie">
    <?= e(APP_NOMBRE) ?> v<?= e(APP_VERSION) ?> &middot; Análisis de Sistemas &middot; 2026
</footer>
</body>
</html>
    <?php
}

/** Muestra los mensajes flash pendientes y un error general opcional. */
function mostrar_mensajes(?string $errorGeneral = null): void
{
    foreach (obtener_flash() as $flash) {
        $tipo = in_array($flash['tipo'], ['exito', 'error', 'info'], true) ? $flash['tipo'] : 'info';
        echo '<div class="alerta alerta-' . $tipo . '" role="status">' . e($flash['mensaje']) . '</div>';
    }

    if ($errorGeneral !== null && $errorGeneral !== '') {
        echo '<div class="alerta alerta-error" role="alert">' . e($errorGeneral) . '</div>';
    }
}

/** Botón para mostrar/ocultar la contraseña (lo maneja validaciones.js). */
function boton_ver_password(string $idCampo): string
{
    return '<button type="button" class="ver-password" data-objetivo="' . e($idCampo) . '"'
        . ' aria-label="Mostrar contraseña" title="Mostrar contraseña">'
        . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5C6.5 5 2.7 8.6 1.5 12c1.2 3.4 5 7 10.5 7s9.3-3.6 10.5-7C21.3 8.6 17.5 5 12 5zm0 11.5A4.5 4.5 0 1 1 12 7.5a4.5 4.5 0 0 1 0 9zm0-7a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z"/></svg>'
        . '</button>';
}

/** Lista de requisitos de la contraseña que se tilda en vivo con JavaScript. */
function requisitos_password(string $idCampo): string
{
    $reglas = [
        'largo'     => 'Mínimo 8 caracteres',
        'mayuscula' => 'Una letra mayúscula',
        'minuscula' => 'Una letra minúscula',
        'numero'    => 'Un número',
        'simbolo'   => 'Un símbolo (!@#$%...)',
    ];

    $html = '<ul class="requisitos" data-para="' . e($idCampo) . '" aria-label="Requisitos de la contraseña">';
    foreach ($reglas as $clave => $texto) {
        $html .= '<li data-regla="' . $clave . '">' . e($texto) . '</li>';
    }
    return $html . '</ul>';
}
