/* =====================================================================
   Proyecto Login - Validaciones del lado del cliente
   ---------------------------------------------------------------------
   - Verifica que los campos obligatorios no estén vacíos.
   - Verifica el formato de correo @gmail.com.
   - Verifica que la contraseña sea segura y que ambas contraseñas coincidan.
   - Muestra los errores debajo de cada campo y bloquea el envío si hay errores.
   Las mismas reglas se vuelven a validar en PHP (includes/validaciones.php).
   ===================================================================== */
'use strict';

(function () {
    /** Reglas de validación: cada una recibe el valor y devuelve true si es válido. */
    const REGLAS = {
        requerido: (valor) => valor.trim() !== '',
        gmail: (valor) => /^[a-z0-9._+]+@gmail\.com$/i.test(valor.trim()),
        min8: (valor) => valor.length >= 8,
        nombre: (valor) => /^[\p{L}][\p{L}\s'-]{1,49}$/u.test(valor.trim()),
        passwordSegura: (valor) => Object.values(requisitosPassword(valor)).every(Boolean),
    };

    /** Detalle de cada requisito de la contraseña (para la lista con tildes). */
    function requisitosPassword(valor) {
        return {
            largo: valor.length >= 8,
            mayuscula: /[A-Z]/.test(valor),
            minuscula: /[a-z]/.test(valor),
            numero: /\d/.test(valor),
            simbolo: /[^A-Za-z0-9]/.test(valor),
        };
    }

    /** "passwordSegura" -> "msjPasswordSegura" (lee el atributo data-msj-password-segura). */
    function claveMensaje(regla) {
        return 'msj' + regla.charAt(0).toUpperCase() + regla.slice(1);
    }

    function mostrarError(campo, mensaje) {
        const contenedorError = document.getElementById('error-' + campo.id);
        if (contenedorError) {
            contenedorError.textContent = mensaje;
        }
        campo.classList.toggle('invalido', mensaje !== '');
        campo.classList.toggle('valido', mensaje === '' && campo.value !== '');
        campo.setAttribute('aria-invalid', mensaje !== '' ? 'true' : 'false');
    }

    /** Valida un campo según su atributo data-reglas. Devuelve true si es válido. */
    function validarCampo(campo) {
        const reglas = (campo.dataset.reglas || '').split(/\s+/).filter(Boolean);
        let mensaje = '';

        for (const regla of reglas) {
            let valido = true;

            if (regla === 'coincide') {
                const original = document.getElementById(campo.dataset.coincide);
                valido = original !== null && campo.value === original.value;
            } else if (REGLAS[regla]) {
                valido = REGLAS[regla](campo.value);
            }

            if (!valido) {
                mensaje = campo.dataset[claveMensaje(regla)] || 'Campo inválido';
                break;
            }
        }

        mostrarError(campo, mensaje);
        return mensaje === '';
    }

    /** Actualiza la lista de requisitos (✔ / ○) mientras se escribe. */
    function actualizarRequisitos(campo) {
        const lista = document.querySelector('.requisitos[data-para="' + campo.id + '"]');
        if (!lista) {
            return;
        }
        const estado = requisitosPassword(campo.value);
        lista.querySelectorAll('li[data-regla]').forEach((item) => {
            item.classList.toggle('cumple', Boolean(estado[item.dataset.regla]));
        });
    }

    function prepararFormulario(formulario) {
        const campos = Array.from(formulario.querySelectorAll('[data-reglas]'));
        const tocados = new WeakSet();

        campos.forEach((campo) => {
            // Al salir del campo se valida y queda marcado como "tocado".
            campo.addEventListener('blur', () => {
                if (campo.type === 'email') {
                    campo.value = campo.value.trim().toLowerCase();
                }
                tocados.add(campo);
                validarCampo(campo);
            });

            // Mientras se escribe, se revalida solo si ya fue tocado.
            campo.addEventListener('input', () => {
                actualizarRequisitos(campo);
                if (tocados.has(campo)) {
                    validarCampo(campo);
                }
                // Si cambia la contraseña, revalidar el campo "Repetir contraseña".
                campos
                    .filter((otro) => otro.dataset.coincide === campo.id && tocados.has(otro))
                    .forEach(validarCampo);
            });

            // Cuando el navegador bloquea el envío por "required"/"pattern",
            // se muestra también nuestro mensaje debajo del campo.
            campo.addEventListener('invalid', () => {
                tocados.add(campo);
                validarCampo(campo);
            });

            actualizarRequisitos(campo);
        });

        formulario.addEventListener('submit', (evento) => {
            let primerInvalido = null;

            campos.forEach((campo) => {
                tocados.add(campo);
                if (!validarCampo(campo) && primerInvalido === null) {
                    primerInvalido = campo;
                }
            });

            if (primerInvalido !== null) {
                evento.preventDefault();
                primerInvalido.focus();
                return;
            }

            // Evita el doble envío del formulario.
            const boton = formulario.querySelector('button[type="submit"]');
            if (boton) {
                boton.disabled = true;
                if (boton.dataset.textoEnvio) {
                    boton.textContent = boton.dataset.textoEnvio;
                }
            }
        });
    }

    /** Botones para mostrar u ocultar la contraseña. */
    function prepararBotonesVer() {
        document.querySelectorAll('.ver-password').forEach((boton) => {
            boton.addEventListener('click', () => {
                const campo = document.getElementById(boton.dataset.objetivo);
                if (!campo) {
                    return;
                }
                const mostrar = campo.type === 'password';
                campo.type = mostrar ? 'text' : 'password';
                boton.classList.toggle('activo', mostrar);
                const texto = mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña';
                boton.setAttribute('aria-label', texto);
                boton.title = texto;
                campo.focus();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form[data-validar]').forEach(prepararFormulario);
        prepararBotonesVer();
    });

    // Si el usuario vuelve con el botón "Atrás", se reactivan los botones de envío.
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form[data-validar] button[type="submit"][disabled]').forEach((boton) => {
            boton.disabled = false;
        });
    });
})();
