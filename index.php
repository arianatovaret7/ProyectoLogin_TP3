<?php
/**
 * index.php - Pantalla de inicio: "Iniciar sesión".
 * Valida correo @gmail.com y contraseña contra la tabla "usuarios".
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

redirigir_si_logueado();

$email        = '';
$errores      = [];
$errorGeneral = null;

if (es_post()) {
    $email    = normalizar_email(campo_post('email'));
    $password = campo_post('password');

    if (!csrf_valido()) {
        $errorGeneral = 'El formulario expiró. Volvé a intentarlo.';
    } else {
        // 1) Validación de campos (también se hace en el navegador con HTML y JS).
        if (!es_gmail_valido($email)) {
            $errores['email'] = MSJ_EMAIL;
        }
        if (mb_strlen($password) < 8) {
            $errores['password'] = MSJ_PASSWORD_LOGIN;
        }

        // 2) Verificación de credenciales contra la base de datos.
        if (!$errores) {
            $usuario = buscar_usuario_por_email($email);

            if ($usuario !== null && esta_bloqueado($usuario)) {
                $errorGeneral = sprintf(
                    'Cuenta bloqueada temporalmente por intentos fallidos. Probá de nuevo en %d minuto(s) o restablecé tu contraseña.',
                    minutos_de_bloqueo_restantes($usuario)
                );
            } elseif ($usuario !== null && password_verify($password, $usuario['password_hash'])) {
                $idUsuario    = (int) $usuario['id_usuario'];
                $ultimoAcceso = ultimo_acceso_exitoso($idUsuario);

                reiniciar_intentos($idUsuario);
                if (password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
                    actualizar_password($idUsuario, $password);
                }
                registrar_acceso($idUsuario, true);
                crear_sesion_usuario($usuario, $ultimoAcceso);

                redirigir('dashboard.php');
            } else {
                // Mensaje genérico: no revela si el correo existe o no.
                $errorGeneral = 'Correo o contraseña incorrectos.';

                if ($usuario !== null) {
                    registrar_acceso((int) $usuario['id_usuario'], false);
                    if (registrar_intento_fallido($usuario)) {
                        $errorGeneral = sprintf(
                            'Superaste los %d intentos permitidos. La cuenta quedó bloqueada %d minutos.',
                            MAX_INTENTOS,
                            BLOQUEO_MINUTOS
                        );
                    }
                }
            }
        }
    }
}

encabezado('Iniciar sesión');
?>
        <h1>Iniciar sesión</h1>

        <?php mostrar_mensajes($errorGeneral); ?>

        <form method="post" action="index.php" class="formulario" data-validar autocomplete="on">
            <?= campo_csrf() ?>

            <div class="campo">
                <label for="email" class="sr-only">Correo</label>
                <input type="email" id="email" name="email"
                       placeholder="Correo @gmail.com"
                       value="<?= e($email) ?>"
                       required maxlength="100" autocomplete="email" autofocus
                       pattern="[A-Za-z0-9._+]+@gmail\.com"
                       title="Ingresá un correo que termine en @gmail.com"
                       data-reglas="requerido gmail"
                       data-msj-requerido="<?= e(MSJ_EMAIL) ?>"
                       data-msj-gmail="<?= e(MSJ_EMAIL) ?>"
                       aria-describedby="error-email"
                       class="<?= isset($errores['email']) ? 'invalido' : '' ?>">
                <small class="error" id="error-email" aria-live="polite"><?= e($errores['email'] ?? '') ?></small>
            </div>

            <div class="campo">
                <label for="password" class="sr-only">Contraseña</label>
                <div class="con-boton">
                    <input type="password" id="password" name="password"
                           placeholder="Contraseña"
                           required minlength="8" maxlength="72" autocomplete="current-password"
                           title="La contraseña tiene al menos 8 caracteres"
                           data-reglas="requerido min8"
                           data-msj-requerido="<?= e(MSJ_PASSWORD_LOGIN) ?>"
                           data-msj-min8="<?= e(MSJ_PASSWORD_LOGIN) ?>"
                           aria-describedby="error-password"
                           class="<?= isset($errores['password']) ? 'invalido' : '' ?>">
                    <?= boton_ver_password('password') ?>
                </div>
                <small class="error" id="error-password" aria-live="polite"><?= e($errores['password'] ?? '') ?></small>
            </div>

            <button type="submit" class="boton" data-texto-envio="Ingresando...">Ingresar</button>
        </form>

        <nav class="enlaces">
            <a href="registro.php">¿No tenés cuenta? Crear una</a>
            <a href="recuperar.php">¿Olvidaste la contraseña?</a>
        </nav>
<?php
pie();
