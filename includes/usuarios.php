<?php
/**
 * Acceso a datos (modelo): todas las consultas SQL del proyecto.
 * Tablas: usuarios, accesos y recuperaciones (ver database/proyecto_login.sql).
 */
declare(strict_types=1);

// =====================================================================
// USUARIOS
// =====================================================================

function buscar_usuario_por_email(string $email): ?array
{
    $sql = 'SELECT id_usuario, nombre, apellido, email, password_hash,
                   intentos_fallidos, bloqueado_hasta, fecha_alta
              FROM usuarios
             WHERE email = :email
             LIMIT 1';
    $consulta = db()->prepare($sql);
    $consulta->execute([':email' => $email]);
    $usuario = $consulta->fetch();

    return $usuario === false ? null : $usuario;
}

function existe_email(string $email): bool
{
    $consulta = db()->prepare('SELECT 1 FROM usuarios WHERE email = :email LIMIT 1');
    $consulta->execute([':email' => $email]);
    return $consulta->fetchColumn() !== false;
}

/** Inserta el usuario nuevo. La contraseña se guarda encriptada (bcrypt). */
function crear_usuario(string $nombre, string $apellido, string $email, string $password): int
{
    $sql = 'INSERT INTO usuarios (nombre, apellido, email, password_hash, fecha_alta)
            VALUES (:nombre, :apellido, :email, :password_hash, :fecha_alta)';
    $consulta = db()->prepare($sql);
    $consulta->execute([
        ':nombre'        => $nombre,
        ':apellido'      => $apellido,
        ':email'         => $email,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':fecha_alta'    => ahora(),
    ]);

    return (int) db()->lastInsertId();
}

function actualizar_password(int $idUsuario, string $password): void
{
    $sql = 'UPDATE usuarios
               SET password_hash = :password_hash,
                   intentos_fallidos = 0,
                   bloqueado_hasta = NULL
             WHERE id_usuario = :id';
    db()->prepare($sql)->execute([
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':id'            => $idUsuario,
    ]);
}

// =====================================================================
// BLOQUEO POR INTENTOS FALLIDOS
// =====================================================================

function esta_bloqueado(array $usuario): bool
{
    return !empty($usuario['bloqueado_hasta'])
        && strtotime($usuario['bloqueado_hasta']) > time();
}

function minutos_de_bloqueo_restantes(array $usuario): int
{
    $segundos = strtotime((string) $usuario['bloqueado_hasta']) - time();
    return max(1, (int) ceil($segundos / 60));
}

/**
 * Suma un intento fallido. Al llegar a MAX_INTENTOS bloquea la cuenta
 * durante BLOQUEO_MINUTOS y reinicia el contador.
 * Devuelve true si la cuenta quedó bloqueada.
 */
function registrar_intento_fallido(array $usuario): bool
{
    $intentos = (int) $usuario['intentos_fallidos'] + 1;

    if ($intentos >= MAX_INTENTOS) {
        $sql = 'UPDATE usuarios
                   SET intentos_fallidos = 0, bloqueado_hasta = :hasta
                 WHERE id_usuario = :id';
        db()->prepare($sql)->execute([
            ':hasta' => ahora(BLOQUEO_MINUTOS * 60),
            ':id'    => $usuario['id_usuario'],
        ]);
        return true;
    }

    $sql = 'UPDATE usuarios SET intentos_fallidos = :intentos WHERE id_usuario = :id';
    db()->prepare($sql)->execute([':intentos' => $intentos, ':id' => $usuario['id_usuario']]);
    return false;
}

function reiniciar_intentos(int $idUsuario): void
{
    $sql = 'UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = :id';
    db()->prepare($sql)->execute([':id' => $idUsuario]);
}

// =====================================================================
// ACCESOS (historial de inicios de sesión)
// =====================================================================

function registrar_acceso(int $idUsuario, bool $exitoso): void
{
    $sql = 'INSERT INTO accesos (id_usuario, fecha_acceso, ip, navegador, exitoso)
            VALUES (:id, :fecha, :ip, :navegador, :exitoso)';
    db()->prepare($sql)->execute([
        ':id'        => $idUsuario,
        ':fecha'     => ahora(),
        ':ip'        => ip_cliente(),
        ':navegador' => navegador_cliente(),
        ':exitoso'   => $exitoso ? 1 : 0,
    ]);
}

/** Fecha del último ingreso correcto (antes del actual), o null si es el primero. */
function ultimo_acceso_exitoso(int $idUsuario): ?string
{
    $sql = 'SELECT fecha_acceso FROM accesos
             WHERE id_usuario = :id AND exitoso = 1
             ORDER BY fecha_acceso DESC, id_acceso DESC
             LIMIT 1';
    $consulta = db()->prepare($sql);
    $consulta->execute([':id' => $idUsuario]);
    $fecha = $consulta->fetchColumn();

    return $fecha === false ? null : (string) $fecha;
}

function ultimos_accesos(int $idUsuario, int $cantidad = 5): array
{
    $sql = 'SELECT fecha_acceso, ip, exitoso FROM accesos
             WHERE id_usuario = :id
             ORDER BY fecha_acceso DESC, id_acceso DESC
             LIMIT :cantidad';
    $consulta = db()->prepare($sql);
    $consulta->bindValue(':id', $idUsuario, PDO::PARAM_INT);
    $consulta->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
    $consulta->execute();

    return $consulta->fetchAll();
}

// =====================================================================
// RECUPERACIONES (restablecer contraseña)
// =====================================================================

/**
 * Genera un token aleatorio para restablecer la contraseña.
 * En la base se guarda solo su hash SHA-256; el token real va en el enlace.
 */
function crear_recuperacion(int $idUsuario): string
{
    // Invalida cualquier enlace anterior que siga pendiente.
    $sql = 'UPDATE recuperaciones SET usado = 1 WHERE id_usuario = :id AND usado = 0';
    db()->prepare($sql)->execute([':id' => $idUsuario]);

    $token = bin2hex(random_bytes(32));

    $sql = 'INSERT INTO recuperaciones (id_usuario, token_hash, fecha_creacion, fecha_expiracion, usado)
            VALUES (:id, :token_hash, :creacion, :expiracion, 0)';
    db()->prepare($sql)->execute([
        ':id'         => $idUsuario,
        ':token_hash' => hash('sha256', $token),
        ':creacion'   => ahora(),
        ':expiracion' => ahora(TOKEN_EXPIRA_MINUTOS * 60),
    ]);

    return $token;
}

/** Devuelve la recuperación vigente (no usada y no vencida) para ese token. */
function buscar_recuperacion_vigente(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }

    $sql = 'SELECT r.id_recuperacion, r.id_usuario, u.nombre, u.email
              FROM recuperaciones r
              INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
             WHERE r.token_hash = :token_hash
               AND r.usado = 0
               AND r.fecha_expiracion > :ahora
             LIMIT 1';
    $consulta = db()->prepare($sql);
    $consulta->execute([':token_hash' => hash('sha256', $token), ':ahora' => ahora()]);
    $fila = $consulta->fetch();

    return $fila === false ? null : $fila;
}

function marcar_recuperacion_usada(int $idRecuperacion): void
{
    $sql = 'UPDATE recuperaciones SET usado = 1 WHERE id_recuperacion = :id';
    db()->prepare($sql)->execute([':id' => $idRecuperacion]);
}
