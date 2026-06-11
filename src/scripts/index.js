/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/*                                                               Módulo de Traducciones para ConstructSys                                                                    */
/*                                                                                                                                                                           */
/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/* Autor: Magallanes López Carlos Gabriel                                                                                                                                    */
/* Versión del Proyecto: 1.0                                                                                                                                                 */
/* Correo: cgmagallanes23@gmail.com                                                                                                                                          */
/* Ultima Modificación: 10/06/2026                                                                                                                                           */
/*                                                                                                                                                                           */
/*****************************************************************************************************************************************************************************/

// Referencias a los elementos del DOM
const langToggle = document.getElementById('lang-toggle');
const langLabel  = document.getElementById('lang-label');

// Estado del idioma — se inicializa desde localStorage o según el navegador
let currentLang = localStorage.getItem('cs_lang')
                    ?? (navigator.language?.startsWith('en') ? 'en' : 'es');

// Diccionario de traducciones ES / EN
const translations = {

    // ─────────────────────────────────────────────────────────────────────────
    // Español
    // ─────────────────────────────────────────────────────────────────────────
    es: {

        /* Nav */
        navFeatures:    'Módulos',
        navAbout:       'Nosotros',
        navStore:       'ConstructStore',
        navLogin:       'Iniciar Sesión',
        navRegister:    'Comenzar Ahora',

        /* Hero */
        heroEyebrow:    'Sistema de Gestión de Obras · Plataforma en la Nube',
        heroTitle1:     'Tu constructora,',
        heroTitle2:     'sin límites.',
        heroTagline:    'Desde la licitación hasta el cierre de obra — todo en un solo lugar',
        heroDesc:       'ConstructSys es la plataforma todo-en-uno diseñada para constructoras que quieren dejar atrás las hojas de cálculo y los procesos desconectados. Control total desde cualquier dispositivo, desde el primer día.',
        heroBtnPrimary: 'Registrarse',
        heroBtnOutline: 'Iniciar Sesión',
        scrollHint:     'Explorar',

        /* Stats */
        statCloudNum:   '100%',
        statCloudLbl:   'En la Nube',
        statInstNum:    '0',
        statInstLbl:    'Instalaciones Requeridas',
        statPlatNum:    '1',
        statPlatLbl:    'Sola Plataforma',

        /* Features */
        featEyebrow:    'Módulos del Sistema',
        featTitle:      'Todo lo que necesita<br>tu constructora',
        featSub:        'Un ecosistema completo de herramientas integradas para que tu empresa opere sin límites.',
        featProjTitle:  'Gestión de Proyectos',
        featProjDesc:   'Controla cada obra en tiempo real: avances, plazos, recursos y documentos, todo centralizado y accesible desde cualquier lugar.',
        featTeamTitle:  'Administración de Equipo',
        featTeamDesc:   'Gestiona tu personal, asignaciones por obra y roles. Lleva la nómina integrada con control de asistencia y deducciones.',
        featFinTitle:   'Control Financiero',
        featFinDesc:    'Facturas, pagos y retenciones en un solo módulo. Visibilidad completa del flujo de caja por proyecto y por empresa.',
        featSupTitle:   'Proveedores y Subcontratistas',
        featSupDesc:    'Directorio centralizado, contratos, órdenes de compra y seguimiento de entregas. Nunca pierdas de vista a tus proveedores estratégicos.',
        featPrivTitle:  'Espacio Privado',
        featPrivDesc:   'Cada constructora tiene su propio entorno aislado. Tus datos son exclusivamente tuyos, completamente separados de cualquier otro cliente.',
        featAccTitle:   'Acceso Universal',
        featAccDesc:    'Sin instalaciones, sin licencias por dispositivo. Accede desde computadora, tablet o celular con solo una conexión a internet.',

        /* Sección Nosotros */
        copyEyebrow:    'ConstructSys',
        copyTitle:      'La operación de tu constructora,<br>funcionando sin límites',
        copyP1a:        'ConstructSys es la ',
        copyP1hl1:      'plataforma de gestión todo-en-uno',
        copyP1b:        ' diseñada para constructoras que quieren dejar atrás las hojas de Excel y los procesos desconectados. Desde el primer día, tu empresa tiene un sistema completo en la nube: controla tus proyectos, administra a tu equipo, lleva la nómina, gestiona proveedores y subcontratistas, y mantén el control financiero con facturas, pagos y retenciones — ',
        copyP1hl2:      'todo desde un solo lugar y sin instalar nada.',
        copyP2a:        'Cada constructora que entra a ConstructSys obtiene su propio espacio privado. ',
        copyP2hl:       'Tus datos son exclusivamente tuyos',
        copyP2b:        ', completamente separados de cualquier otro cliente de la plataforma. El acceso es seguro, rápido y disponible desde cualquier dispositivo con internet.',
        copyP3a:        'ConstructSys no es solo una plataforma — es la operación de tu constructora funcionando sin límites, ',
        copyP3hl:       'desde la licitación hasta el cierre de obra.',

        /* ConstructStore */
        storeBadge:     'Integrado en tu Dashboard',
        storeTitle:     'ConstructStore — Materiales<br>al precio del proveedor directo',
        storeDesc:      'Tu propia tienda en línea, ConstructStore está integrado directamente en tu dashboard: cemento, acero, block, tubería y más. Sin intermediarios, sin salir del sistema.',
        storeTag1:      'Cemento',
        storeTag2:      'Acero',
        storeTag3:      'Block',
        storeTag4:      'Tubería',
        storeTag5:      'Y más...',

        /* Final CTA */
        finalEyebrow:   'Empieza Hoy',
        finalTitle:     '¿Listo para operar',
        finalTitleEm:   'sin Límites?',
        finalDesc:      'Únete a ConstructSys y lleva la gestión de tu constructora al siguiente nivel. Sin instalaciones, sin complicaciones — tu sistema listo desde el primer día.',
        finalBtn:       'Registrarse',

        /* Footer */
        footerSub:      'Sistema de Gestión de Obras',
        footerDevLbl:   'Desarrollado por',
        footerCopy:     'Todos los derechos reservados.',
        footerTagline:  'Desde la licitación hasta el cierre de obra',

        /* Toggle */
        langLabel:      'ES',
    },

    // ─────────────────────────────────────────────────────────────────────────
    // English
    // ─────────────────────────────────────────────────────────────────────────
    en: {

        /* Nav */
        navFeatures:    'Modules',
        navAbout:       'About',
        navStore:       'ConstructStore',
        navLogin:       'Log In',
        navRegister:    'Get Started',

        /* Hero */
        heroEyebrow:    'Construction Management System · Cloud Platform',
        heroTitle1:     'Your construction firm,',
        heroTitle2:     'without limits.',
        heroTagline:    'From bidding to project close-out — all in one place',
        heroDesc:       'ConstructSys is the all-in-one platform built for construction companies that want to leave spreadsheets and disconnected processes behind. Full control from any device, from day one.',
        heroBtnPrimary: 'Sign Up',
        heroBtnOutline: 'Log In',
        scrollHint:     'Explore',

        /* Stats */
        statCloudNum:   '100%',
        statCloudLbl:   'Cloud-Based',
        statInstNum:    '0',
        statInstLbl:    'Installations Required',
        statPlatNum:    '1',
        statPlatLbl:    'Single Platform',

        /* Features */
        featEyebrow:    'System Modules',
        featTitle:      'Everything your<br>construction firm needs',
        featSub:        'A complete ecosystem of integrated tools so your company can operate without limits.',
        featProjTitle:  'Project Management',
        featProjDesc:   'Control every project in real time: progress, deadlines, resources, and documents — all centralized and accessible from anywhere.',
        featTeamTitle:  'Team Administration',
        featTeamDesc:   'Manage your staff, project assignments, and roles. Run payroll with built-in attendance tracking and deductions.',
        featFinTitle:   'Financial Control',
        featFinDesc:    'Invoices, payments, and retentions in one module. Full cash-flow visibility per project and per company.',
        featSupTitle:   'Suppliers & Subcontractors',
        featSupDesc:    'Centralized directory, contracts, purchase orders, and delivery tracking. Never lose sight of your strategic suppliers.',
        featPrivTitle:  'Private Workspace',
        featPrivDesc:   'Every construction firm gets its own isolated environment. Your data is exclusively yours, completely separate from any other client.',
        featAccTitle:   'Universal Access',
        featAccDesc:    'No installations, no per-device licenses. Access from desktop, tablet, or phone — all you need is an internet connection.',

        /* About Section */
        copyEyebrow:    'ConstructSys',
        copyTitle:      'Your construction operation,<br>running without limits',
        copyP1a:        'ConstructSys is the ',
        copyP1hl1:      'all-in-one management platform',
        copyP1b:        ' built for construction companies that want to leave Excel sheets and disconnected processes behind. From day one, your company has a complete cloud system: manage your projects, administer your team, run payroll, handle suppliers and subcontractors, and keep financial control with invoices, payments, and retentions — ',
        copyP1hl2:      'all from one place, nothing to install.',
        copyP2a:        'Every company that joins ConstructSys gets its own private workspace. ',
        copyP2hl:       'Your data is exclusively yours',
        copyP2b:        ', completely separated from any other client on the platform. Access is secure, fast, and available from any internet-connected device.',
        copyP3a:        'ConstructSys is more than a platform — it\'s your construction operation running without limits, ',
        copyP3hl:       'from bidding to project close-out.',

        /* ConstructStore */
        storeBadge:     'Integrated in your Dashboard',
        storeTitle:     'ConstructStore — Materials<br>at direct supplier prices',
        storeDesc:      'Your own online store, ConstructStore is integrated directly into your dashboard: cement, steel, block, piping and more. No middlemen, no leaving the system.',
        storeTag1:      'Cement',
        storeTag2:      'Steel',
        storeTag3:      'Block',
        storeTag4:      'Piping',
        storeTag5:      'And more...',

        /* Final CTA */
        finalEyebrow:   'Start Today',
        finalTitle:     'Ready to operate',
        finalTitleEm:   'without Limits?',
        finalDesc:      'Join ConstructSys and take your construction management to the next level. No installations, no complications — your system ready from day one.',
        finalBtn:       'Sign Up',

        /* Footer */
        footerSub:      'Construction Management System',
        footerDevLbl:   'Developed by',
        footerCopy:     'All rights reserved.',
        footerTagline:  'From bidding to project close-out',

        /* Toggle */
        langLabel:      'EN',
    },
};

/**
 * Aplica las traducciones del idioma activo a todos los elementos marcados con data-i18n.
 * Los elementos con data-i18n-html reciben innerHTML para admitir etiquetas como <br>.
 */
function applyTranslations() {
    const t = translations[currentLang];
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key   = el.dataset.i18n;
        const value = t[key] ?? translations['es'][key] ?? key;
        if (el.dataset.i18nHtml !== undefined) {
            el.innerHTML  = value;
        } else {
            el.textContent = value;
        }
    });
    document.documentElement.setAttribute('lang', currentLang);
}

/**
 * Sincroniza el estado visual del botón toggle con el idioma activo.
 */
function syncToggle() {
    if (!langToggle || !langLabel) return;
    langToggle.classList.toggle('active', currentLang === 'en');
    langLabel.textContent = translations[currentLang].langLabel;
}

/**
 * Alterna entre los idiomas disponibles (ES ↔ EN), persiste la elección
 * en localStorage y re-aplica las traducciones.
 */
function toggleLang() {
    currentLang = currentLang === 'es' ? 'en' : 'es';
    localStorage.setItem('cs_lang', currentLang);
    applyTranslations();
    syncToggle();
}

// Eventos
langToggle?.addEventListener('click', toggleLang);

// Inicialización
applyTranslations();
syncToggle();

/*****************************************************************************************************************************************************************************/
