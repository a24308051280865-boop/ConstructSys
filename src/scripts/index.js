/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/*                                                                  Scripts para el Proyecto ConstrucSys                                                                     */
/*                                                                                                                                                                           */
/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/* Autor: Magallanes López Carlos Gabriel                                                                                                                                    */
/* Versión del Proyecto: 1.0                                                                                                                                                 */
/* Correo: cgmagallanes23@gmail.com                                                                                                                                          */
/* Ultima Modificación: 22/05/2026                                                                                                                                           */
/*                                                                                                                                                                           */
/*****************************************************************************************************************************************************************************/

/* Configuración de internacionalización */
const i18n = {
	es: {
		title: 'ConstructSys',
		subtitle: 'Sistema de Gestión de Obras',
		cardTitle: 'Iniciar Sesión',
		cardSub: 'Ingresa tus credenciales para acceder al sistema',
		emailLabel: 'Correo Electrónico',
		emailPh: 'usuario@empresa.com',
		passLabel: 'Contraseña',
		forgot: '¿Olvidaste tu contraseña?',
		btnLogin: 'Acceder al Sistema',
		errEmpty: 'Correo y contraseña son obligatorios.',
		errServer: 'No se pudo conectar con el servidor.',
		errAuth: 'Error de autenticación.',
		welcome: 'Bienvenido',
		btnRegister: 'Crear Cuenta',
		btnGoogle: 'Continuar con Google',
	},
	en: {
		title: 'ConstructSys',
		subtitle: 'Construction Management System',
		cardTitle: 'Sign In',
		cardSub: 'Enter your credentials to access the system',
		emailLabel: 'Email Address',
		emailPh: 'user@company.com',
		passLabel: 'Password',
		forgot: 'Forgot your password?',
		btnLogin: 'Access to the System',
		errEmpty: 'Email and password are required.',
		errServer: 'Could not connect to the server.',
		errAuth: 'Authentication error.',
		welcome: 'Welcome',
		btnRegister: 'Create Account',
		btnGoogle: 'Continue with Google',
	}
};

// Idioma actual (por defecto español)
let currentLang = 'es';

/**
 * Aplicar traducciones correspondientes en el DOM.
 * 
 * @param {'es' | 'en'} language - El idioma a aplicar (es o en).
 */
function applyLanguage(language) {
	
	// Obtener las traducciones para el idioma seleccionado
	const translation = i18n[language];
	
	// Actualizar el texto de los elementos en la página con las traducciones correspondientes
	document.querySelector('.logo-company').textContent = translation.title;
	document.querySelector('.logo-sub').textContent = translation.subtitle;
	document.querySelector('.card-header-login h1').textContent = translation.cardTitle;
	document.querySelector('.card-header-login p').textContent = translation.cardSub;
	document.querySelector('label[for="email"]').textContent = translation.emailLabel;
	document.querySelector('.btn-register').lastChild.textContent = ' ' + translation.btnRegister;
	document.querySelector('.btn-google').lastChild.textContent = ' ' + translation.btnGoogle;
	
	// Asignar el placeholder del campo de email con la traducción correspondiente
	document.getElementById('email').placeholder = translation.emailPh;
	
	// Actualizar el texto del label de contraseña y otros elementos relacionados con la contraseña
	document.querySelector('label[for="password"]').textContent = translation.passLabel;
	document.querySelector('.link-subtle').textContent = translation.forgot;
	document.querySelector('.btn-text').textContent = translation.btnLogin;

	// Actualizar el texto del banner de error con la traducción correspondiente
	document.getElementById('lang-label').textContent = language.toUpperCase();

}

// Aplicar el idioma inicial al cargar la página
const langToggle = document.getElementById('lang-toggle');
langToggle.addEventListener('click', () => {
    currentLang = currentLang === 'es' ? 'en' : 'es';
    langToggle.classList.toggle('active', currentLang === 'en');
    applyLanguage(currentLang);
});

// Obtener los Elementos del DOM
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const btnLogin = document.getElementById('btn-login');
const errorBanner = document.getElementById('error-banner');
const errorMsg = document.getElementById('error-msg');
const togglePw = document.getElementById('toggle-pw');
const iconEye = document.getElementById('icon-eye');
const iconEyeOff = document.getElementById('icon-eye-off');

// Toggle visibilidad contraseña 
togglePw.addEventListener('click', () => {
	const visible = passwordInput.type === 'text';
	passwordInput.type = visible ? 'password' : 'text';                   // Cambia el tipo del input para mostrar u ocultar la contraseña
	iconEye.style.display = visible ? 'none' : 'block';                   // Se comporta como block para mantener el tamaño del contenedor
	iconEyeOff.style.display = visible ? 'block' : 'none';                // Se comporta como block para mantener el tamaño del contenedor
});

// Ocultar error al escribir
[emailInput, passwordInput].forEach(element =>
    element.addEventListener('input', () => errorBanner.classList.remove('show'))
);

// Submit con Enter
passwordInput.addEventListener('keydown', event => {
	if (event.key === 'Enter') handleLogin();
});

// Submit con Enter desde el campo de email, enfocando el password
emailInput.addEventListener('keydown', event => {
    if (event.key === 'Enter') passwordInput.focus();
});

// Lógica de login
btnLogin.addEventListener('click', handleLogin);

/**
 * Maneja el proceso de inicio de sesión de ConstrucSys.
 * 
 * @async
 * @returns {Promise<void>} No retorna ningún valor, maneja la redirección internamente.
 */
async function handleLogin() {
    
	// Obtener y limpiar los valores de los campos
	const email  = emailInput.value.trim();
    const password = passwordInput.value.trim();

    // Validación básica en el cliente 
	if (!email || !password){ 
		showError(i18n[currentLang].errEmpty)
		return;
	};

    // Estado de carga
    btnLogin.disabled = true;
    btnLogin.classList.add('loading');
    errorBanner.classList.remove('show');
  
    try {

		// Enviar solicitud al servidor
		const response = await fetch('api/auth/login.php', {              // Endpoint de autenticación
			method: 'POST',                                                 // Método HTTP de Request
			headers: {
        'Content-Type': 'application/json', 
      },                                  
			body: JSON.stringify({email, password}),                       // Convertir los datos a JSON para enviarlos al servidor
		});

		// Obtener la respuesta del servidor
		const data = await response.json();

		// Manejar la respuesta del servidor
		if (data.success) {

			// Guardar el token y la información del usuario en sesión
			localStorage.setItem('cs_token', data.token);
			localStorage.setItem('cs_user',  JSON.stringify(data.user));

			// Mostrar mensaje de bienvenida y redirigir al dashboard
			showToast('success', `Bienvenido, ${data.user.nombre}`);
			
			// Redirigir al dashboard después de un breve retraso para mostrar el toast
			setTimeout(
				() => window.location.href = 'views/dashboard.html', 
				1200
			);
		} 
		else showError(data.error || i18n[currentLang].errAuth);
      

    } catch {
		showError(i18n[currentLang].errServer);
    } finally {
		btnLogin.disabled = false;
		btnLogin.classList.remove('loading');
    }

}

/**
 * Mostrar un mensaje de error en el banner.
 * 
 * @param {string} msg - El mensaje de error a mostrar.
 */
function showError(msg) {
    errorMsg.textContent = msg;
    errorBanner.classList.add('show');
    passwordInput.value = '';
    passwordInput.focus();
}

/**
 * Muestra una notificación temporal (Toast) en la pantalla.
 * 
 * @param {'success' | 'error'} type - El tipo de alerta (éxito o error).
 * @param {string} message - El mensaje de texto que se va a mostrar.
 */
function showToast(type, message) {
    
	// Crear el elemento del Toast
	const toast = document.createElement('div');
    
	// Asignar la clase correspondiente según el tipo de mensaje
	toast.className = `toast ${type}`;
    toast.innerHTML = `
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        ${type === 'success'
          ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
          : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'}
      </svg>
      <span>${message}</span>`;
    
	// Agregar el Toast al contenedor
	document.getElementById('toast-container').appendChild(toast);
    
	// Eliminar el Toast después de 3.5 segundos
	setTimeout(() => toast.remove(), 3500);

}

// Obtener parámetros de la URL para manejar el flujo de autenticación con Google
const urlParams = new URLSearchParams(window.location.search);
const googleToken = urlParams.get('google_token');
const googleRol = urlParams.get('rol');
const googleError = urlParams.get('auth_error');

// Reinciar la URL al valor incial
if (googleError) {
    window.history.replaceState({}, '', window.location.pathname);                       // Limpiar los parámetros de la URL para evitar mostrar el error repetidamente
    showError(decodeURIComponent(googleError));
}
// Si se recibe un token de Google, guardarlo y redirigir al usuario según su rol
if (googleToken) {    
	localStorage.setItem('cs_token', googleToken);                                       // Guardar el token de Google en localStorage para mantener la sesión del usuario
    window.history.replaceState({}, '', window.location.pathname);                       // Limpiar los parámetros de la URL para evitar mostrar el token repetidamente
	const payload = JSON.parse(atob(googleToken.split('.')[1]));                         // Decodificar el payload del token JWT para obtener la información del usuario                    
    localStorage.setItem('cs_user', JSON.stringify({                                     // Guardar la información del usuario en localStorage 
        id: payload.sub,
        nombre: payload.name,
        email: payload.email,
        rol: payload.rol,
        db_name: payload.db,   
    }));
	showToast('success', `Bienvenido, ${payload.name}`);
	const routes = {
        admin: 'views/dashboard.html',
        empleado: 'views/dashboard.html',
        cliente: 'views/tienda.html',
    };
    
	// Redirigir al usuario a la página correspondiente según su rol 
	setTimeout(
        () => window.location.href = routes[googleRol] ?? 'views/dashboard.html', 1200
    );
}

/*****************************************************************************************************************************************************************************/