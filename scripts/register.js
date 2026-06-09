/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/*                                                                  Scripts para el Proyecto ConstrucSys                                                                     */
/*                                                                                                                                                                           */
/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/* Autor: Magallanes López Carlos Gabriel                                                                                                                                    */
/* Versión del Proyecto: 1.0                                                                                                                                                 */
/* Correo: cgmagallanes23@gmail.com                                                                                                                                          */
/* Ultima Modificación: 25/05/2026                                                                                                                                           */
/*                                                                                                                                                                           */
/******************************************************************************************************************************************************************************/

// Diccionario de traducciones para español e inglés
const i18n = {
	es: {
		tagline: 'Sistema de Gestión de Obra',
		heading: 'Crear Cuenta',
		subheading: 'Registra tu empresa y accede de inmediato',
		sec_company: 'Empresa',
		sec_admin: 'Administrador',
		sec_access: 'Acceso',
		lbl_company: 'Nombre de la empresa',
		lbl_name: 'Nombre',
		lbl_surname: 'Apellido',
		lbl_email: 'Correo electrónico',
		lbl_password: 'Contraseña',
		btn_submit: 'Crear Cuenta',
		footer_login_prompt: '¿Ya tienes cuenta?',
		footer_login_link: 'Inicia sesión',
		footer_secure: 'Conexión segura',
		str_weak: 'Débil',
		str_fair: 'Regular',
		str_good: 'Buena',
		str_strong: 'Fuerte',
		err_required: 'Todos los campos son obligatorios.',
		err_email: 'El correo no tiene un formato válido.',
		err_short_pw: 'La contraseña debe tener al menos 8 caracteres.',
		err_server: 'Ocurrió un error inesperado.',
		err_network: 'No se pudo conectar con el servidor.',
		toast_success: 'Cuenta creada. Redirigiendo…',
	},
	en: {
		tagline: 'Construction Management System',
		heading: 'Create Account',
		subheading: 'Register your company and get instant access',
		sec_company: 'Company',
		sec_admin: 'Administrator',
		sec_access: 'Access',
		lbl_company: 'Company name',
		lbl_name: 'First name',
		lbl_surname: 'Last name',
		lbl_email: 'Email address',
		lbl_password: 'Password',
		btn_submit: 'Create Account',
		footer_login_prompt: 'Already have an account?',
		footer_login_link: 'Sign in',
		footer_secure: 'Secure connection',
		str_weak: 'Weak',
		str_fair: 'Fair',
		str_good: 'Good',
		str_strong: 'Strong',
		err_required: 'All fields are required.',
		err_email: 'Please enter a valid email address.',
		err_short_pw: 'Password must be at least 8 characters.',
		err_server: 'An unexpected error occurred.',
		err_network: 'Could not connect to the server.',
		toast_success: 'Account created. Redirecting…',
	}
};

// Idioma inicial (español)
let language = 'es';

/**
 * Aplicar traducciones correspondientes en el DOM.
 * 
 * @param {'es' | 'en'} language - El idioma a aplicar (es o en).
 */
function applyLanguage(newLanguage) {

	// Actualizar el idioma global
    language = newLanguage; 
	const translation = i18n[language];
	document.querySelectorAll('[data-i18n]').forEach(element => {
			const key = element.dataset.i18n;
			if (translation[key] !== undefined) element.textContent = translation[key];
	});

	// Actualizar los placeholders de los inputs
	document.querySelectorAll('input[data-ph-es]').forEach(element => {
		element.placeholder = language === 'en' ? element.dataset.phEn : element.dataset.phEs;
	});

	// Actualizar el estado del toggle de idioma
	document.getElementById('langLabel').textContent = language === 'es' ? 'EN' : 'ES';
	document.getElementById('togglePw').setAttribute('aria-label', language === 'es' ? 'Mostrar contraseña' : 'Show password'); 
	
	// Guardar el idioma seleccionado
	updateStrength(document.getElementById('password').value);

}

// Aplicar el idioma inicial al cargar la página
document.getElementById('langToggle').addEventListener('click', function () {
	this.classList.toggle('active');
	applyLanguage(language === 'es' ? 'en' : 'es');
});

// Obtener referencias a elementos del DOM para el manejo de la contraseña y su fortaleza
const pwInput = document.getElementById('password');
const toggleBtn = document.getElementById('togglePw');
const eyeIcon = document.getElementById('eyeIcon');
const eyeOpen = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
const eyeClosed = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`;
let pwVisible = false;

// Manejar el evento de clic en el botón de mostrar/ocultar contraseña
toggleBtn.addEventListener('click', () => {
	pwVisible = !pwVisible;
	pwInput.type = pwVisible ? 'text' : 'password';
	eyeIcon.innerHTML = pwVisible ? eyeClosed : eyeOpen;
});

// Obtener referencias a elementos del DOM para el indicador de fortaleza de la contraseña
const strengthEl = document.getElementById('pwStrength');
const strengthLbl = document.getElementById('strengthLabel');
const bars = ['bar1', 'bar2', 'bar3', 'bar4'].map(id => document.getElementById(id));
const levelKeys = ['str_weak', 'str_fair', 'str_good', 'str_strong'];
const levelCls = ['weak', 'fair', 'good', 'strong'];
const levelClr = { weak: '#c0392b', fair: '#e67e22', good: '#f1c40f', strong: '#27ae60' };

/**
 * Evalúa la fortaleza de una contraseña y devuelve un puntaje de 0 a 4.
 * 
 * @param {string} password - La contraseña a evaluar.
 */
function evaluatePassword(password) {
	
	// Inicializar el puntaje de fortaleza
	let score = 0;

	// Criterios de evaluación:
	if (password.length >= 8) score++;
	if (password.length >= 12) score++;
	if (/[A-Z]/.test(password) && /[a-z]/.test(password)) score++;
	if (/[0-9]/.test(password)) score++;
	if (/[^A-Za-z0-9]/.test(password)) score++;

	// Limitar el puntaje máximo a 4 para simplificar la visualización
	return Math.min(4, score);

}

/** 
 * Actualiza las barras y etiqueta del indicador de fortaleza
 * 
 * @param {string} value - El valor actual del campo de contraseña.
 */  
function updateStrength(value) {

	// Evaluar la fortaleza de la contraseña y determinar si se debe mostrar el indicador
	const score = evaluatePassword(value);
	const show = value.length > 0;

	// Mostrar u ocultar el contenedor del indicador de fortaleza
	strengthEl.classList.toggle('visible', show);
 
	// Actualizar las clases de las barras según el puntaje obtenido
	bars.forEach((bar, index) => {
		bar.className = 'strength-bar';
		if (show && index < score) bar.classList.add(levelCls[score - 1]);
	});

	// Actualizar la etiqueta de fortaleza con el texto y color correspondiente	
	if (show) {
		const key = levelKeys[Math.max(0, score - 1)];
		strengthLbl.textContent = i18n[language][key];
		strengthLbl.style.color = levelClr[levelCls[Math.max(0, score - 1)]];
	} 
	else {
		strengthLbl.textContent = '—';
		strengthLbl.style.color = 'var(--text3)';
	}

}

// Actualizar el indicador de fortaleza y progreso al ingresar texto de contraseña
pwInput.addEventListener('input', () => {
	updateStrength(pwInput.value);
	updateDots(pwInput.value.length);
});

/** 
 * Actualiza el estado visual de los puntos del indicador de progreso según los campos completados
 * 
 * @param {int} passwordLength - La longitud actual del campo de contraseña para determinar el progreso del paso 3
 */ 
function updateDots(passwordLength) {
	
	// Obtener referencias a los campos relevantes para determinar el estado de cada paso
	const name = document.getElementById('firstName');
	const surname = document.getElementById('lastName');
	const email = document.getElementById('email');
	
	// Determinar el estado de cada paso del indicador de progreso
	const companyState = document.getElementById('company').value.trim() !== '';
	const nameState = name.value.trim() !== '' && surname.value.trim() !== '' && email.value.trim() !== '';
	const passwordState = passwordLength >= 8;

	// Actualizar el estado visual de cada punto del indicador de progreso
	setDot('dot1', companyState ? 'done' : 'active');
	setDot('dot2', nameState ? 'done' : (companyState ? 'active' : ''));
	setDot('dot3', passwordState ? 'done' : (nameState ? 'active' : ''));

}

/** 
 *  Actualiza la clase de un punto del indicador de progreso para reflejar su estado 
 * 
 * @param {int} id - El ID del punto a actualizar (dot1, dot2 o dot3).
 * @param {string} state - El estado a aplicar ('active', 'done' o '').
 */ 
function setDot(id, state) {
  
	// Obtener el elemento del punto por su ID y restablecer sus clases
	const element = document.getElementById(id);
	element.className = 'step-dot';
	
	// Agregar la clase correspondiente según el estado indicado
	if (state === 'active') element.classList.add('active');
	if (state === 'done') element.classList.add('done');

}

// Actualizar el indicador de progreso al ingresar texto en los campos relevantes
['company', 'firstName', 'lastName', 'email'].forEach(id =>
	document.getElementById(id).addEventListener('input', () => updateDots(pwInput.value.length))
);

// Manejar el evento de envío del formulario de registro
document.getElementById('registerForm').addEventListener('submit', async (event) => {
	
	// Prevenir el comportamiento predeterminado del formulario para manejarlo con JavaScript
	event.preventDefault();
	hideError();
	
	// Obtener las traducciones correspondientes al idioma actual para mostrar mensajes de error y éxito
	const translation = i18n[language];

	// Obtener y limpiar los valores de los campos del formulario
	const company = document.getElementById('company').value.trim();
	const firstName = document.getElementById('firstName').value.trim();
	const lastName = document.getElementById('lastName').value.trim();
	const email = document.getElementById('email').value.trim();
	const password = document.getElementById('password').value;

	// Validar que todos los campos estén completos y tengan el formato correcto antes de enviar la solicitud
	if (!company || !firstName || !lastName || !email || !password) return showError(translation.err_required);
	if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return showError(translation.err_email);
	if (password.length < 8) return showError(translation.err_short_pw);

	// Deshabilitar el botón de envío y mostrar un estado de carga mientras se procesa la solicitud
	const btn = document.getElementById('submitBtn');
	btn.classList.add('loading');
	btn.disabled = true;
 
	// Intentar enviar la solicitud de registro al servidor y manejar la respuesta
	try {

		// Hacer el request POST al endpoint de registro con los datos del formulario en formato JSON
		const res = await fetch('../api/auth/register.php', {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify({company, firstName, lastName, email, password}),
		});
 
		// Parsear la respuesta JSON del servidor 
		const data = await res.json();

		// Redirigir según el resultado de la solicitud
		if (data.success) {

			// Guardar el token de autenticación y los datos del usuario en localStorage para mantener la sesión iniciada
			localStorage.setItem('cs_token', data.token);
			localStorage.setItem('cs_user',  JSON.stringify(data.user));

			// Toast de bienvenida con nombre
			const welcomeMsg = language === 'es'
				? `¡Bienvenido, ${data.user.firstName}! Tu cuenta ha sido creada.`
				: `Welcome, ${data.user.firstName}! Your account has been created.`;
			showToast(welcomeMsg, 'success');

			setTimeout(() => {window.location.href = '../views/dashboard.html';}, 1800);

		} 
		else {
			showError(data.error || translation.err_server);
			btn.classList.remove('loading');
			btn.disabled = false;
		}
	} catch {
		showError(translation.err_network);
		btn.classList.remove('loading');
		btn.disabled = false;
	}
});

/** 
 *  Muestra un mensaje de error en un banner visible en la parte superior de la página
 * 
 * @param {string} msg - El mensaje de error a mostrar en el banner de error.
 */
function showError(msg) {
	document.getElementById('errorMsg').textContent = msg;
	document.getElementById('errorBanner').classList.add('show');
}

/** 
 *  Oculta el banner de error eliminando la clase que lo hace visible
 */
function hideError() {
	document.getElementById('errorBanner').classList.remove('show');
}

/** 
 *  Muestra un mensaje en un toast temporal
 * 
 * @param {string} msg - El mensaje a mostrar en el toast.
 * @param {string} type - El tipo de mensaje ('success' para éxito, 'error' para error) que determina el estilo del toast.
 */
function showToast(msg, type = '') {
	
	// Obtener el contenedor de toasts y crear un nuevo elemento para el toast actual
	const toastContainer = document.getElementById('toast-container');
	const toast = document.createElement('div');
	
	// Configurar el contenido y estilo del toast según el tipo de mensaje
	toast.className = `toast ${type}`;
	toast.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
		${type === 'success'
		? '<polyline points="20 6 9 17 4 12"/>'
		: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'}
	</svg> ${msg}`;

	// Agregar el toast al contenedor y programar su eliminación después de 4 segundos
	toastContainer.appendChild(toast);
	setTimeout(() => toast.remove(), 4000);

}

/******************************************************************************************************************************************************************************/