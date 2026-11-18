<?php
/**
 * registro.php - Pantalla "Crear Cuenta".
 * Da de alta un usuario nuevo (nombre, apellido, correo y contraseña).
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

redirigir_si_logueado();

$datos = [
    'nombre'    => '',
    'apellido'  => '',
    'email'     => '',
    'password'  => '',
    'password2' => '',
];
$errores      = [];
$errorGeneral = null;

if (es_post()) {
    $datos = [
        'nombre'    => normalizar_texto(campo_post('nombre')),
        'apellido'  => normalizar_texto(campo_post('apellido')),
        'email'     => normalizar_email(campo_post('email')),
        'password'  => campo_post('password'),
        'password2' => campo_post('password2'),
    ];

    if (!csrf_valido()) {
        $errorGeneral = 'El formulario expiró. Volvé a intentarlo.';
    } else {
        $errores = validar_registro($datos);

        if (!isset($errores['email']) && existe_email($datos['email'])) {
            $errores['email'] = 'Ese correo ya está registrado';
        }

        if (!$errores) {
            try {
                crear_usuario($datos['nombre'], $datos['apellido'], $datos['email'], $datos['password']);
                set_flash('exito', '¡Cuenta creada con éxito! Ya podés iniciar sesión.');
                redirigir('index.php');
            } catch (PDOException $ex) {
                // 23000 = clave única duplicada (dos registros simultáneos con el mismo correo).
                if ($ex->getCode() === '23000') {
                    $errores['email'] = 'Ese correo ya está registrado';
                } else {
                    error_log('[Proyecto Login] Error al registrar: ' . $ex->getMessage());
                    $errorGeneral = 'No se pudo crear la cuenta. Intentá nuevamente.';
                }
            }
        }
    }
}

/** Atributo class para marcar en rojo los campos con error. */
$clase = static fn (string $campo): string => isset($errores[$campo]) ? 'invalido' : '';

encabezado('Crear Cuenta');
?>
        <h1>Crear Cuenta</h1>

        <?php mostrar_mensajes($errorGeneral); ?>

        <form method="post" action="registro.php" class="formulario" data-validar autocomplete="on">
            <?= campo_csrf() ?>

            <div class="campo">
                <label for="nombre" class="sr-only">Nombre</label>
                <input type="text" id="nombre" name="nombre" placeholder="Nombre"
                       value="<?= e($datos['nombre']) ?>"
                       required maxlength="50" autocomplete="given-name" autofocus
                       data-reglas="requerido nombre"
                       data-msj-requerido="<?= e(MSJ_NOMBRE_OBLIGATORIO) ?>"
                       data-msj-nombre="Solo letras (2 a 50 caracteres)"
                       aria-describedby="error-nombre" class="<?= $clase('nombre') ?>">
                <small class="error" id="error-nombre" aria-live="polite"><?= e($errores['nombre'] ?? '') ?></small>
            </div>

            <div class="campo">
                <label for="apellido" class="sr-only">Apellido</label>
                <input type="text" id="apellido" name="apellido" placeholder="Apellido"
                       value="<?= e($datos['apellido']) ?>"
                       required maxlength="50" autocomplete="family-name"
                       data-reglas="requerido nombre"
                       data-msj-requerido="<?= e(MSJ_APELLIDO_OBLIGATORIO) ?>"
                       data-msj-nombre="Solo letras (2 a 50 caracteres)"
                       aria-describedby="error-apellido" class="<?= $clase('apellido') ?>">
                <small class="error" id="error-apellido" aria-live="polite"><?= e($errores['apellido'] ?? '') ?></small>
            </div>

            <div class="campo">
                <label for="email" class="sr-only">Correo</label>
                <input type="email" id="email" name="email" placeholder="Correo @gmail.com"
                       value="<?= e($datos['email']) ?>"
                       required maxlength="100" autocomplete="email"
                       pattern="[A-Za-z0-9._+]+@gmail\.com"
                       title="Ingresá un correo que termine en @gmail.com"
                       data-reglas="requerido gmail"
                       data-msj-requerido="<?= e(MSJ_EMAIL) ?>"
                       data-msj-gmail="<?= e(MSJ_EMAIL) ?>"
                       aria-describedby="error-email" class="<?= $clase('email') ?>">
                <small class="error" id="error-email" aria-live="polite"><?= e($errores['email'] ?? '') ?></small>
            </div>

            <div class="campo">
                <label for="password" class="sr-only">Contraseña</label>
                <div class="con-boton">
                    <input type="password" id="password" name="password" placeholder="Contraseña"
                           required minlength="8" maxlength="72" autocomplete="new-password"
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

            <button type="submit" class="boton" data-texto-envio="Creando cuenta...">Crear Cuenta</button>
        </form>

        <nav class="enlaces">
            <a href="index.php">¿Ya tenés cuenta? Iniciar sesión</a>
        </nav>
<?php
pie();
