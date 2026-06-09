/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/*                                                                  Scripts para el Proyecto ConstrucSys                                                                     */
/*                                                                                                                                                                           */
/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/* Autor: Magallanes López Carlos Gabriel                                                                                                                                    */
/* Versión del Proyecto: 1.0                                                                                                                                                 */
/* Correo: cgmagallanes23@gmail.com                                                                                                                                          */
/* Ultima Modificación: 07/06/2026                                                                                                                                           */
/*                                                                                                                                                                           */
/*****************************************************************************************************************************************************************************/

// Referencias a los elementos del DOM
const langToggle = document.getElementById('lang-toggle');
const langLabel  = document.getElementById('lang-label');

// Estado del idioma — se inicializa según el navegador
let currentLang = navigator.language?.startsWith('en') ? 'en' : 'es';

// Diccionario de traducciones ES / EN
const translations = {
    es: {
        logoSub: 'Sistema de Gestión de Obras',
        titleSuccess: 'Cuenta recuperada',
        subSuccess: 'Tu cuenta está activa nuevamente.',
        msgSuccess: 'Tu cuenta fue recuperada exitosamente. Ya puedes iniciar sesión con tus credenciales habituales.',
        titleInvalid: 'Enlace inválido',
        subInvalid: '',
        msgInvalid: 'Este enlace ya fue usado, expiró o no es válido. Si tu cuenta fue eliminada hace menos de 30 días escríbenos a soporte.',
        backLabel: 'Volver a iniciar sesión',
        langLabel: 'ES',
    },
    en: {
        logoSub: 'Construction Management System',
        titleSuccess: 'Account recovered',
        subSuccess: 'Your account is active again.',
        msgSuccess: 'Your account was successfully recovered. You can now sign in with your usual credentials.',
        titleInvalid: 'Invalid link',
        subInvalid: '',
        msgInvalid: 'This link has already been used, expired, or is not valid. If your account was deleted less than 30 days ago, contact support.',
        backLabel: 'Back to sign in',
        langLabel: 'EN',
    },
};

/**
 * Aplica las traducciones del idioma activo a los elementos estáticos de la UI.
 */
function applyTranslations() {
    const translation = translations[currentLang];
    document.getElementById('logo-sub').textContent = translation.logoSub;
    document.getElementById('back-label').textContent = translation.backLabel;
    langLabel.textContent = translation.langLabel;
    document.getElementById('html').setAttribute('lang', currentLang);
}

/**
 * Alterna entre los idiomas disponibles (ES ↔ EN) y re-aplica las traducciones.
 */
function toggleLang() {
    currentLang = currentLang === 'es' ? 'en' : 'es';
    langToggle.classList.toggle('active', currentLang === 'en');
    applyTranslations();
}

// Leer el status que mandó cancel-deletion.php en la URL
const status = new URLSearchParams(window.location.search).get('status');

// Limpiar la URL para no exponer el status en el historial
window.history.replaceState({}, '', window.location.pathname);

/**
 * Muestra el resultado de la cancelación según el status recibido en la URL.
 */
function renderResult() {
    const translation = translations[currentLang];
    if (status === 'success') {
        document.getElementById('card-title').textContent    = translation.titleSuccess;
        document.getElementById('card-sub').textContent      = translation.subSuccess;
        document.getElementById('success-title').textContent = translation.titleSuccess;
        document.getElementById('success-msg').textContent   = translation.msgSuccess;
        document.getElementById('success-banner').classList.add('show');

    } else {
        document.getElementById('card-title').textContent = translation.titleInvalid;
        document.getElementById('card-sub').textContent   = translation.subInvalid;
        document.getElementById('error-msg').textContent  = translation.msgInvalid;
        document.getElementById('error-banner').classList.add('show');

    }
}

// Eventos
langToggle.addEventListener('click', toggleLang);

// Inicialización
applyTranslations();
renderResult();

/*****************************************************************************************************************************************************************************/