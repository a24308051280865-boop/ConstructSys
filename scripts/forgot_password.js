/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/*                                                                  Scripts para el Proyecto ConstrucSys                                                                     */
/*                                                                                                                                                                           */
/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/* Autor: Magallanes López Carlos Gabriel                                                                                                                                    */
/* Versión del Proyecto: 1.0                                                                                                                                                 */
/* Correo: cgmagallanes23@gmail.com                                                                                                                                          */
/* Ultima Modificación: 06/06/2026                                                                                                                                           */
/*                                                                                                                                                                           */
/*****************************************************************************************************************************************************************************/

// Expresión regular simple para validar formato de correo electrónico
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// IDs del HTML actual
const errorBanner = document.getElementById('error-banner');
const errorMsg = document.getElementById('error-msg');
const successBanner = document.getElementById('success-banner');
const successTitle = document.getElementById('success-title');
const successMsg = document.getElementById('success-msg');
const formSection = document.getElementById('form-section');
const emailInput = document.getElementById('fp-email');
const btnSubmit = document.getElementById('btn-submit');
const langToggle = document.getElementById('lang-toggle');
const langLabel = document.getElementById('lang-label');

// Estado del idioma actual (por defecto: español)
let currentLang = 'es';

// Diccionario de traducciones ES / EN
const translations = {
    es: {
        cardTitle: 'Restablecer Contraseña',
        cardSub: 'Ingresa tu correo y te enviaremos un enlace de restablecimiento',
        labelEmail: 'Correo Electrónico',
        placeholder: 'usuario@empresa.com',
        fieldHint: 'Usa el correo electrónico asociado con tu cuenta.',
        btnText: 'Enviar Enlace de Restablecimiento',
        successTitle: 'Enlace enviado exitosamente',
        successMsg: (email) => `Enviamos un enlace a ${email}. Revisa tu bandeja de entrada.`,
        errRequired: 'El correo es obligatorio.',
        errFormat: 'Ingresa un correo electrónico válido.',
        errServer: 'No se pudo conectar con el servidor.',
        backLink: 'Volver a iniciar sesión',
        langLabel: 'ES',
        logoSub: 'Sistema de Gestión de Obras',
    },
    en: {
        cardTitle: 'Reset Password',
        cardSub: 'Enter your email and we\'ll send you a reset link',
        labelEmail: 'Email Address',
        placeholder: 'user@company.com',
        fieldHint: 'Use the email address associated with your account.',
        btnText: 'Send Reset Link',
        successTitle: 'Link sent successfully',
        successMsg: (email) => `We sent a link to ${email}. Check your inbox.`,
        errRequired: 'Email is required.',
        errFormat: 'Enter a valid email address.',
        errServer: 'Could not connect to the server.',
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
    document.getElementById('card-sub').textContent = t.cardSub;
    document.querySelector('label[for="fp-email"]').textContent = t.labelEmail;
    emailInput.placeholder = t.placeholder;
    document.querySelector('.field-hint').textContent  = t.fieldHint;
    document.querySelector('.btn-text').textContent = t.btnText;
    document.querySelector('.back-link span').textContent= t.backLink;
    document.getElementById('logo-sub').textContent = t.logoSub;
    successTitle.textContent = t.successTitle;
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
 * Muestra un mensaje de éxito indicando que se ha enviado el enlace de restablecimiento.
 * 
 * @param {string} email - Dirección de correo electrónico.
 */
function showSuccess(email) {
    const t = translations[currentLang];
    successTitle.textContent = t.successTitle;
    successMsg.textContent = t.successMsg(email);
    successBanner.classList.add('show');
    formSection.style.display = 'none';
}

/**
 * Maneja el proceso de restablecimiento de contraseña: valida el correo, envía la solicitud al backend y muestra mensajes según el resultado.
 */
async function handleReset() {
    
    // Primero ocultamos cualquier error previo
    hideError();
    
    // Validación básica del correo electrónico
    const t = translations[currentLang];
    const email = emailInput.value.trim();
    if (!email) return showError(t.errRequired);
    if (!emailRegex.test(email)) return showError(t.errFormat);

    // Deshabilitamos el botón y mostramos un estado de carga
    btnSubmit.classList.add('loading');
    btnSubmit.disabled = true;

    // Enviamos la solicitud al backend
    try {
        const res = await fetch('../api/auth/password/forgot.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({email, lang: currentLang}),
        });
        const data = await res.json();
        showSuccess(email);
    } catch {
        showError(t.errServer);
        btnSubmit.classList.remove('loading');
        btnSubmit.disabled = false;
    }
}

// Eventos
emailInput.addEventListener('input', hideError);
emailInput.addEventListener('keydown', event => { if (event.key === 'Enter') handleReset(); });
btnSubmit.addEventListener('click', handleReset);
langToggle.addEventListener('click', toggleLang);

// Inicialización
applyTranslations();

/*****************************************************************************************************************************************************************************/