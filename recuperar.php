<?php
/**
 * recuperar.php - "¿Olvidaste la contraseña?"
 * Genera un enlace temporal (token) para restablecer la contraseña.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

redirigir_si_logueado();

$email        = '';
$errores      = [];
$errorGeneral = null;
$enviado      = false;
$enlace       = null;

if (es_post()) {
    $email = normalizar_email(campo_post('email'));

    if (!csrf_valido()) {
        $errorGeneral = 'El formulario expiró. Volvé a intentarlo.';
    } elseif (!es_gmail_valido($email)) {
        $errores['email'] = MSJ_EMAIL;
    } else {
        $usuario = buscar_usuario_por_email($email);

        if ($usuario !== null) {
            $token  = crear_recuperacion((int) $usuario['id_usuario']);
            $enlace = 'restablecer.php?token=' . urlencode($token);
            // En un servidor real acá se enviaría el enlace por correo (por ejemplo con PHPMailer).
        }

        // Siempre se muestra el mismo mensaje para no revelar qué correos existen.
        $enviado = true;
    }
}

encabezado('Recuperar contraseña');
?>
        <h1>Recuperar contraseña</h1>

        <?php mostrar_mensajes($errorGeneral); ?>

        <?php if ($enviado): ?>
            <div class="alerta alerta-exito" role="status">
                Si el correo <strong><?= e($email) ?></strong> está registrado, se generó un enlace
                para restablecer la contraseña. Es válido por <?= TOKEN_EXPIRA_MINUTOS ?> minutos.
            </div>

            <?php if (MODO_LOCAL && $enlace !== null): ?>
                <div class="alerta alerta-info">
                    <strong>Modo local (XAMPP):</strong> como no hay servidor de correo configurado,
                    el enlace se muestra acá en lugar de enviarse por email.
                </div>
                <a class="boton boton-enlace" href="<?= e($enlace) ?>">Restablecer contraseña ahora</a>
            <?php endif; ?>
        <?php else: ?>
            <p class="subtitulo">Ingresá el correo con el que te registraste y te daremos un enlace para crear una contraseña nueva.</p>

            <form method="post" action="recuperar.php" class="formulario" data-validar>
                <?= campo_csrf() ?>

                <div class="campo">
                    <label for="email" class="sr-only">Correo</label>
                    <input type="email" id="email" name="email" placeholder="Correo @gmail.com"
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

                <button type="submit" class="boton" data-texto-envio="Generando enlace...">Enviar enlace</button>
            </form>
        <?php endif; ?>

        <nav class="enlaces">
            <a href="index.php">Volver a iniciar sesión</a>
        </nav>
<?php
pie();
