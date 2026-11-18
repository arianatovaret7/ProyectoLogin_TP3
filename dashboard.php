<?php
/**
 * dashboard.php - Vista protegida del usuario logueado.
 * Muestra el mensaje de bienvenida y el botón para cerrar sesión.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

requerir_login();

// Evita que el navegador muestre esta página desde la caché luego de cerrar sesión.
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$usuario  = $_SESSION['usuario'];
$accesos  = ultimos_accesos($usuario['id']);

encabezado('Dashboard', 'tarjeta-ancha');
?>
        <h1>Bienvenido <?= e($usuario['nombre']) ?> a tu dashboard</h1>
        <p class="subtitulo">Esta es una vista protegida. Aquí podés agregar contenido del sistema.</p>

        <?php mostrar_mensajes(); ?>

        <div class="panel">
            <h2>Datos de tu cuenta</h2>
            <dl class="datos">
                <dt>Nombre completo</dt>
                <dd><?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?></dd>

                <dt>Correo</dt>
                <dd><?= e($usuario['email']) ?></dd>

                <dt>Cuenta creada</dt>
                <dd><?= e(formatear_fecha($usuario['fecha_alta'], false)) ?></dd>

                <dt>Último ingreso</dt>
                <dd><?= $usuario['ultimo_acceso'] === null
                        ? 'Este es tu primer ingreso 🎉'
                        : e(formatear_fecha($usuario['ultimo_acceso'])) ?></dd>
            </dl>
        </div>

        <?php if ($accesos): ?>
        <div class="panel">
            <h2>Actividad reciente</h2>
            <div class="tabla-scroll">
                <table class="tabla">
                    <thead>
                        <tr><th scope="col">Fecha y hora</th><th scope="col">IP</th><th scope="col">Resultado</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($accesos as $acceso): ?>
                        <tr>
                            <td><?= e(formatear_fecha($acceso['fecha_acceso'])) ?></td>
                            <td><?= e($acceso['ip']) ?></td>
                            <td>
                                <?php if ((int) $acceso['exitoso'] === 1): ?>
                                    <span class="etiqueta etiqueta-ok">Correcto</span>
                                <?php else: ?>
                                    <span class="etiqueta etiqueta-error">Fallido</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <form method="post" action="logout.php">
            <?= campo_csrf() ?>
            <button type="submit" class="boton">Cerrar sesión</button>
        </form>
<?php
pie();
