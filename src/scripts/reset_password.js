/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/*                                                                  Scripts para el Proyecto ConstrucSys                                                                     */
/*                                                                                                                                                                           */
/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/* Autor: Magallanes López Carlos Gabriel                                                                                                                                    */
/* Versión del Proyecto: 1.0                                                                                                                                                 */
/* Correo: cgmagallanes23@gmail.com                                                                                                                                          */
/* Ultima Modificación: 09/06/2026                                                                                                                                           */
/*                                                                                                                                                                           */
/*****************************************************************************************************************************************************************************/

// Obtener el token de la URL
const token = new URLSearchParams(window.location.search).get('token');

// IDs del HTML actual
const errorBanner = document.getElementById('error-banner');
const errorMsg = document.getElementById('error-msg');
const successBanner = document.getElementById('success-banner');
const formSection = document.getElementById('form-section');
const btnSubmit = document.getElementById('btn-submit');
const passwordInput = document.getElementById('password');
const confirmInput = document.getElementById('confirm');
const langToggle = document.getElementById('lang-toggle');
const langLabel = document.getElementById('lang-label');

// Estado del idioma actual (por defecto: español)
let currentLang = 'es';

// Diccionario de traducciones ES / EN
const translations = {
    es: {
        cardTitle: 'Nueva Contraseña',
        cardSub: 'Crea una nueva contraseña segura para tu cuenta',
        labelPassword: 'Nueva Contraseña',
        labelConfirm: 'Confirmar Contraseña',
        placeholderPw: 'Mínimo 8 caracteres',
        placeholderCf: 'Repite la contraseña',
        btnText: 'Guardar Nueva Contraseña',
        successTitle: 'Contraseña actualizada',
        successMsg: 'Redirigiendo al inicio de sesión...',
        errNoToken: 'Enlace inválido.',
        errInvToken: 'Este enlace es inválido o ha expirado. Solicita uno nuevo.',
        errVerify: 'No se pudo verificar el enlace.',
        errRequired: 'Completa ambos campos.',
        errMinLength: 'La contraseña debe tener al menos 8 caracteres.',
        errMatch: 'Las contraseñas no coinciden.',
        errServer: 'No se pudo conectar con el servidor.',
        errUpdate: 'Error al actualizar la contraseña.',
        backLink: 'Volver a iniciar sesión',
        langLabel: 'ES',
        logoSub: 'Sistema de Gestión de Obras',
    },
    en: {
        cardTitle: 'New Password',
        cardSub: 'Create a new secure password for your account',
        labelPassword: 'New Password',
        labelConfirm: 'Confirm Password',
        placeholderPw: 'At least 8 characters',
        placeholderCf: 'Repeat your password',
        btnText: 'Save New Password',
        successTitle: 'Password updated',
        successMsg: 'Redirecting to login...',
        errNoToken: 'Invalid link.',
        errInvToken: 'This link is invalid or has expired. Request a new one.',
        errVerify: 'Could not verify the link.',
        errRequired: 'Please fill in both fields.',
        errMinLength: 'Password must be at least 8 characters.',
        errMatch: 'Passwords do not match.',
        errServer: 'Could not connect to the server.',
        errUpdate: 'Error updating the password.',
        backLink: 'Back to login',
        langLabel: 'EN',
        logoSub: 'Construction Management System',
    },
};

/**
 * Aplica las traducciones del idioma activo a todos los elementos de la UI.
 */
function applyTranslations() {
    const t = translations[currentLang];
    document.getElementById('card-title').textContent = t.cardTitle;
    document.getElementById('card-sub').textContent  = t.cardSub;
    document.querySelector('label[for="password"]').textContent  = t.labelPassword;
    document.querySelector('label[for="confirm"]').textContent = t.labelConfirm;
    passwordInput.placeholder = t.placeholderPw;
    confirmInput.placeholder = t.placeholderCf;
    document.querySelector('.btn-text').textContent = t.btnText;
    document.getElementById('success-title').textContent = t.successTitle;
    document.getElementById('success-msg').textContent = t.successMsg;
    document.querySelector('.back-link span').textContent = t.backLink;
    document.getElementById('logo-sub').textContent = t.logoSub;
    langLabel.textContent = t.langLabel;
    document.documentElement.lang = currentLang;
}

/**
 * Alterna entre los idiomas disponibles (ES ↔ EN) y re-aplica las traducciones.
 */
function toggleLang() {
    currentLang = currentLang === 'es' ? 'en' : 'es';
    langToggle.classList.toggle('active', currentLang === 'en');
    applyTranslations();
}

/**
 * Muestra un error fatal que impide continuar con el proceso de restablecimiento de contraseña. Oculta el formulario y muestra un mensaje de error.
 */
function showFatalError(msg) {
    errorMsg.textContent = msg;
    errorBanner.classList.add('show');
    formSection.style.display = 'none';
}

/**
 * Muestra un mensaje de error en el banner correspondiente.
 * 
 * @param {string} msg - Mensaje de error a mostrar.
 */
function showError(msg) {
    errorMsg.textContent = msg;
    errorBanner.classList.add('show');
}

/**
 * Oculta el banner de error.
 */
function hideError() {errorBanner.classList.remove('show');}

/**
 * Valida el token al cargar la página. Si el token es inválido o ha expirado, muestra un mensaje de error y oculta el formulario.
 */
async function validateToken() {
    const t = translations[currentLang];

    // Si no hay token, mostramos un error fatal
    if (!token) return showFatalError(t.errNoToken);

    // Verificar el token con el servidor
    try {
        const res = await fetch(`../api/auth/password/reset.php?token=${encodeURIComponent(token)}`);
        const data = await res.json();
        if (!data.valid) showFatalError(t.errInvToken);
    } catch {
        showFatalError(t.errVerify);
    }
}

/**
 * Maneja el proceso de restablecimiento de contraseña: valida los campos, envía la solicitud al backend y muestra mensajes según el resultado.
 */
async function handleReset() {
    
    // Primero ocultamos cualquier error previo
    hideError();

    // Validación básica de los campos
    const t = translations[currentLang];
    const password = passwordInput.value.trim();
    const confirm = confirmInput.value.trim();

    if (!password || !confirm) return showError(t.errRequired);
    if (password.length < 8) return showError(t.errMinLength);
    if (password !== confirm) return showError(t.errMatch);

    // Mostrar estado de carga
    btnSubmit.classList.add('loading');
    btnSubmit.disabled = true;

    // Enviamos el token y las nuevas contraseñas al backend
    try {
        const res = await fetch('../api/auth/password/reset.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({token, password, confirm}),
        });
        const data = await res.json();

        // Si el restablecimiento fue exitoso, mostramos el mensaje de éxito y redirigimos al login
        if (data.success) {
            formSection.style.display = 'none';
            successBanner.classList.add('show');
            setTimeout(() => window.location.href = '../views/login.html', 2500);
        } else {
            showError(data.error || t.errUpdate);
            btnSubmit.classList.remove('loading');
            btnSubmit.disabled = false;
        }

    } catch {
        showError(t.errServer);
        btnSubmit.classList.remove('loading');
        btnSubmit.disabled = false;
    }
}

// Eventos
[passwordInput, confirmInput].forEach(input => input.addEventListener('input', hideError));
confirmInput.addEventListener('keydown', event => { if (event.key === 'Enter') handleReset(); });
btnSubmit.addEventListener('click', handleReset);
langToggle.addEventListener('click', toggleLang);

// Inicialización
applyTranslations();
validateToken();

/*****************************************************************************************************************************************************************************/
