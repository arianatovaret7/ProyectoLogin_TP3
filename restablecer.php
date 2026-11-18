<?php
/**
 * restablecer.php - Crear una contraseña nueva a partir del enlace (token).
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

redirigir_si_logueado();

$tokenGet     = $_GET['token'] ?? '';
$token        = es_post() ? campo_post('token') : (is_string($tokenGet) ? $tokenGet : '');
$recuperacion = buscar_recuperacion_vigente($token);
$errores      = [];
$errorGeneral = null;

if ($recuperacion !== null && es_post()) {
    $password  = campo_post('password');
    $password2 = campo_post('password2');

    if (!csrf_valido()) {
        $errorGeneral = 'El formulario expiró. Volvé a intentarlo.';
    } else {
        $errores = validar_nueva_password($password, $password2);

        if (!$errores) {
            actualizar_password((int) $recuperacion['id_usuario'], $password);
            marcar_recuperacion_usada((int) $recuperacion['id_recuperacion']);

            set_flash('exito', 'Tu contraseña se actualizó. Ya podés iniciar sesión.');
            redirigir('index.php');
        }
    }
}

$clase = static fn (string $campo): string => isset($errores[$campo]) ? 'invalido' : '';

encabezado('Restablecer contraseña');
?>
        <h1>Restablecer contraseña</h1>

        <?php if ($recuperacion === null): ?>
            <div class="alerta alerta-error" role="alert">
                El enlace no es válido, ya fue utilizado o venció.
            </div>
            <a class="boton boton-enlace" href="recuperar.php">Solicitar un enlace nuevo</a>
        <?php else: ?>
            <p class="subtitulo">Hola <?= e($recuperacion['nombre']) ?>, elegí una contraseña nueva para <strong><?= e($recuperacion['email']) ?></strong>.</p>

            <?php mostrar_mensajes($errorGeneral); ?>

            <form method="post" action="restablecer.php" class="formulario" data-validar>
                <?= campo_csrf() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div class="campo">
                    <label for="password" class="sr-only">Contraseña nueva</label>
                    <div class="con-boton">
                        <input type="password" id="password" name="password" placeholder="Contraseña nueva"
                               required minlength="8" maxlength="72" autocomplete="new-password" autofocus
                               title="Mín 8 chars, mayúscula, minúscula, número y símbolo"
                               data-reglas="requerido passwordSegura"
                               data-msj-requerido="<?= e(MSJ_PASSWORD_SEGURA) ?>"
                               data-msj-password-segura="<?= e(MSJ_PASSWORD_SEGURA) ?>"
                               aria-describedby="error-password" class="<?= $clase('password') ?>">
                        <?= boton_ver_password('password') ?>
                    </div>
                    <small class="error" id="error-password" aria-live="polite"><?= e($errores['password'] ?? '') ?></small>
                    <?= requisitos_password('password') ?>
                </div>

                <div class="campo">
                    <label for="password2" class="sr-only">Repetir contraseña</label>
                    <div class="con-boton">
                        <input type="password" id="password2" name="password2" placeholder="Repetir contraseña"
                               required maxlength="72" autocomplete="new-password"
                               data-reglas="requerido coincide" data-coincide="password"
                               data-msj-requerido="<?= e(MSJ_PASSWORD_REPETIR) ?>"
                               data-msj-coincide="<?= e(MSJ_PASSWORD_DISTINTA) ?>"
                               aria-describedby="error-password2" class="<?= $clase('password2') ?>">
                        <?= boton_ver_password('password2') ?>
                    </div>
                    <small class="error" id="error-password2" aria-live="polite"><?= e($errores['password2'] ?? '') ?></small>
                </div>

                <button type="submit" class="boton" data-texto-envio="Guardando...">Guardar contraseña</button>
            </form>
        <?php endif; ?>

        <nav class="enlaces">
            <a href="index.php">Volver a iniciar sesión</a>
        </nav>
<?php
pie();
