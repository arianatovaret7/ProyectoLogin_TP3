<?php
/**
 * Validaciones del lado del servidor.
 * Repiten las reglas de assets/js/validaciones.js: aunque el usuario
 * desactive JavaScript o manipule el HTML, los datos se vuelven a validar acá.
 */
declare(strict_types=1);

const REGEX_GMAIL  = '/^[a-z0-9._+]+@gmail\.com$/';
const REGEX_NOMBRE = "/^[\\p{L}][\\p{L}\\s'-]{1,49}$/u";

const MSJ_NOMBRE_OBLIGATORIO   = 'El nombre es obligatorio';
const MSJ_APELLIDO_OBLIGATORIO = 'El apellido es obligatorio';
const MSJ_EMAIL                = 'Email @gmail.com obligatorio';
const MSJ_PASSWORD_LOGIN       = 'La contraseña es obligatoria (mín 8)';
const MSJ_PASSWORD_SEGURA      = 'Mín 8 chars, mayúscula, minúscula, número y símbolo';
const MSJ_PASSWORD_REPETIR     = 'Repetí la contraseña';
const MSJ_PASSWORD_DISTINTA    = 'Las contraseñas no coinciden';

/** Quita espacios sobrantes y pasa el correo a minúsculas. */
function normalizar_email(string $email): string
{
    return mb_strtolower(trim($email));
}

/** Quita espacios al inicio/fin y espacios dobles internos. */
function normalizar_texto(string $texto): string
{
    return trim((string) preg_replace('/\s+/u', ' ', $texto));
}

function es_gmail_valido(string $email): bool
{
    return mb_strlen($email) <= 100
        && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        && preg_match(REGEX_GMAIL, $email) === 1;
}

function es_nombre_valido(string $texto): bool
{
    return preg_match(REGEX_NOMBRE, $texto) === 1;
}

/** Mínimo 8 caracteres, una mayúscula, una minúscula, un número y un símbolo. */
function es_password_segura(string $password): bool
{
    return mb_strlen($password) >= 8
        && strlen($password) <= 72             // límite de bcrypt (72 bytes)
        && preg_match('/[a-z]/', $password) === 1
        && preg_match('/[A-Z]/', $password) === 1
        && preg_match('/\d/', $password) === 1
        && preg_match('/[^A-Za-z0-9]/', $password) === 1;
}

/**
 * Valida los datos del formulario "Crear Cuenta".
 * Devuelve un arreglo campo => mensaje (vacío si todo está bien).
 */
function validar_registro(array $datos): array
{
    $errores = [];

    if ($datos['nombre'] === '') {
        $errores['nombre'] = MSJ_NOMBRE_OBLIGATORIO;
    } elseif (!es_nombre_valido($datos['nombre'])) {
        $errores['nombre'] = 'Solo letras (2 a 50 caracteres)';
    }

    if ($datos['apellido'] === '') {
        $errores['apellido'] = MSJ_APELLIDO_OBLIGATORIO;
    } elseif (!es_nombre_valido($datos['apellido'])) {
        $errores['apellido'] = 'Solo letras (2 a 50 caracteres)';
    }

    if (!es_gmail_valido($datos['email'])) {
        $errores['email'] = MSJ_EMAIL;
    }

    $errores += validar_nueva_password($datos['password'], $datos['password2']);

    return $errores;
}

/** Valida contraseña nueva + confirmación (registro y restablecimiento). */
function validar_nueva_password(string $password, string $password2): array
{
    $errores = [];

    if (!es_password_segura($password)) {
        $errores['password'] = MSJ_PASSWORD_SEGURA;
    }

    if ($password2 === '') {
        $errores['password2'] = MSJ_PASSWORD_REPETIR;
    } elseif (!hash_equals($password, $password2)) {
        $errores['password2'] = MSJ_PASSWORD_DISTINTA;
    }

    return $errores;
}
