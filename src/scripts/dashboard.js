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

// Autenticación — leer sesión del Local Storage
const cs_user  = JSON.parse(localStorage.getItem('cs_user') || '{}');
const cs_token = localStorage.getItem('cs_token');
const DB       = cs_user.db_name;
if (!cs_token || !DB) window.location.href = '../views/login.html';

// Estado del idioma actual (por defecto: español)
let currentLang = 'es';

// Estado del modal de edición
let editModule = null;
let editId     = null;

// URL base para la API
const API = '../api/index.php?module=';

/*****************************************************************************************************************************************************************************/

// Mapa módulo → tabla en BD
const MODULE_MAP = {
    proyectos:      'proyectos',
    contratos:      'contratos',
    licitaciones:   'licitaciones',
    hitos:          'hitos_proyecto',
    bitacora:       'bitacora_obra',
    documentos:     'documentos_proyecto',
    riesgos:        'riesgos_proyecto',
    permisos:       'permisos_licencias',
    empleados:      'empleados',
    departamentos:  'departamentos',
    asignaciones:   'asignaciones',
    ausencias:      'ausencias',
    capacitaciones: 'capacitaciones',
    emp_cap:        'empleado_capacitacion',
    accidentes:     'accidentes_laborales',
    nomina:         'nomina',
    detalle_nomina: 'detalle_nomina',
    prestamos:      'prestamos_empleados',
    facturas:       'facturas',
    pagos:          'pagos',
    retenciones:    'retenciones',
    cotizaciones:   'cotizaciones_proveedor',
    garantias:      'garantias',
    proveedores:    'proveedores',
    contactos:      'contactos_proveedor',
    subcontratistas:'subcontratistas',
    contratos_sub:  'contratos_subcontratista',
    equipos:        'equipos',
    mantenimiento:  'mantenimiento_equipos',
    almacenes:      'almacenes',
    clientes:       'clientes',
    seguridad:      'incidentes_seguridad',
};

// Clave primaria (PK) por módulo
const PK = {
    proyectos:      'id_proyecto',
    contratos:      'id_contrato',
    licitaciones:   'id_licitacion',
    hitos:          'id_hito',
    bitacora:       'id_bitacora',
    documentos:     'id_documento',
    riesgos:        'id_riesgo',
    permisos:       'id_permiso',
    empleados:      'id_empleado',
    departamentos:  'id_departamento',
    asignaciones:   'id_asignacion',
    ausencias:      'id_ausencia',
    capacitaciones: 'id_capacitacion',
    emp_cap:        'id_emp_cap',
    accidentes:     'id_accidente',
    nomina:         'id_nomina',
    detalle_nomina: 'id_detalle_nomina',
    prestamos:      'id_prestamo',
    facturas:       'id_factura',
    pagos:          'id_pago',
    retenciones:    'id_retencion',
    cotizaciones:   'id_cotizacion',
    garantias:      'id_garantia',
    proveedores:    'id_proveedor',
    contactos:      'id_contacto',
    subcontratistas:'id_subcontratista',
    contratos_sub:  'id_contrato_sub',
    equipos:        'id_equipo',
    mantenimiento:  'id_mantenimiento',
    almacenes:      'id_almacen',
    clientes:       'id_cliente',
    seguridad:      'id_incidente',
};

// Etiquetas de navegación por módulo
const PAGES = {
    dashboard:      'Panel General',
    proyectos:      'Proyectos',
    contratos:      'Contratos',
    licitaciones:   'Licitaciones',
    hitos:          'Hitos',
    bitacora:       'Bitácora de Obra',
    documentos:     'Documentos',
    riesgos:        'Riesgos',
    permisos:       'Permisos / Licencias',
    empleados:      'Empleados',
    departamentos:  'Departamentos',
    asignaciones:   'Asignaciones',
    ausencias:      'Ausencias',
    capacitaciones: 'Capacitaciones',
    emp_cap:        'Registro Capacitación',
    accidentes:     'Accidentes Laborales',
    nomina:         'Nómina',
    detalle_nomina: 'Detalle de Nómina',
    prestamos:      'Préstamos',
    facturas:       'Facturas',
    pagos:          'Pagos',
    retenciones:    'Retenciones',
    cotizaciones:   'Cotizaciones',
    garantias:      'Garantías',
    proveedores:    'Proveedores',
    contactos:      'Contactos Prov./Sub.',
    subcontratistas:'Subcontratistas',
    contratos_sub:  'Contratos Sub.',
    equipos:        'Equipos',
    mantenimiento:  'Mantenimiento',
    almacenes:      'Almacenes',
    clientes:       'Clientes',
    seguridad:      'Incidentes de Seguridad',
    tienda:         'Tienda',
    danger:         'Eliminar Cuenta',
};

// Módulos que tienen tabla SQL (excluye dashboard, tienda y danger)
const SQL_MODULES = Object.keys(PAGES).filter(key => key !== 'dashboard' && key !== 'tienda' && key !== 'danger');

/*****************************************************************************************************************************************************************************/

// Selects a poblar según módulo
const PAGE_SELECTS = {
    proyectos:      { clientes: ['proy-cliente'], empleados: ['proy-director'], licitaciones: ['proy-licitacion'] },
    contratos:      { proyectos: ['cont-proyecto'], clientes: ['cont-cliente'], licitaciones: ['cont-licitacion'] },
    licitaciones:   { clientes: ['lic-cliente'], empleados: ['lic-responsable'], proyectos: ['lic-proyecto'] },
    hitos:          { proyectos: ['hito-proyecto'], empleados: ['hito-responsable'] },
    bitacora:       { proyectos: ['bit-proyecto'], empleados: ['bit-emitido'] },
    documentos:     { proyectos: ['doc-proyecto'], empleados: ['doc-empleado'] },
    riesgos:        { proyectos: ['riesgo-proyecto'], empleados: ['riesgo-responsable'] },
    permisos:       { proyectos: ['perm-proyecto'] },
    empleados:      { departamentos: ['emp-departamento'], empleados: ['emp-supervisor'] },
    departamentos:  { empleados: ['dept-responsable'] },
    asignaciones:   { empleados: ['asig-empleado'], proyectos: ['asig-proyecto'] },
    ausencias:      { empleados: ['aus-empleado', 'aus-aprobada'], proyectos: ['aus-proyecto'] },
    capacitaciones: { proyectos: ['cap-proyecto'] },
    emp_cap:        { empleados: ['empcap-empleado'], capacitaciones: ['empcap-capacitacion'] },
    accidentes:     { proyectos: ['acc-proyecto'], empleados: ['acc-empleado'] },
    nomina:         { empleados: ['nom-aprobada'] },
    detalle_nomina: { nomina: ['det-nomina'], empleados: ['det-empleado'] },
    prestamos:      { empleados: ['prest-empleado', 'prest-autorizado'] },
    facturas:       { proveedores: ['fac-proveedor'], clientes: ['fac-cliente'], proyectos: ['fac-proyecto'], contratos: ['fac-contrato'] },
    pagos:          { facturas: ['pago-factura'], empleados: ['pago-empleado'] },
    retenciones:    { contratos: ['ret-contrato'], facturas: ['ret-factura'] },
    cotizaciones:   { proveedores: ['cot-proveedor'], proyectos: ['cot-proyecto'] },
    garantias:      { proyectos: ['gar-proyecto'], contratos: ['gar-contrato'] },
    contactos:      { proveedores: ['cont2-proveedor'], subcontratistas: ['cont2-sub'] },
    contratos_sub:  { subcontratistas: ['csub-sub'], proyectos: ['csub-proyecto'] },
    equipos:        { proyectos: ['eq-proyecto'] },
    mantenimiento:  { equipos: ['mant-equipo'], empleados: ['mant-tecnico'] },
    almacenes:      { empleados: ['alm-responsable'], proyectos: ['alm-proyecto'] },
    seguridad:      { proyectos: ['seg-proyecto'], empleados: ['seg-empleado'] },
};

// Placeholder por tipo de select
const PLACEHOLDER = {
    proyectos:      '— Seleccionar proyecto —',
    clientes:       '— Seleccionar cliente —',
    empleados:      '— Seleccionar empleado —',
    proveedores:    '— Seleccionar proveedor —',
    subcontratistas:'— Seleccionar subcontratista —',
    contratos:      '— Seleccionar contrato —',
    licitaciones:   '— Seleccionar licitación —',
    departamentos:  '— Seleccionar departamento —',
    capacitaciones: '— Seleccionar capacitación —',
    nomina:         '— Seleccionar período —',
    facturas:       '— Seleccionar factura —',
    equipos:        '— Seleccionar equipo —',
};

/*****************************************************************************************************************************************************************************/

// Campos editables por módulo (refleja update_fields del backend)
const UPDATE_FIELDS = {
    proyectos:      ['nombre_proyecto','descripcion','tipo_proyecto','estado','ubicacion','ciudad','estado_republica','pais','fecha_inicio_estimada','fecha_fin_estimada','fecha_inicio_real','fecha_fin_real','presupuesto_estimado','costo_actual','id_director','porcentaje_avance'],
    contratos:      ['fecha_fin_estimada','fecha_fin_real','monto_total','estado','descripcion_alcance','penalizacion_dia','anticipo_pct','notas'],
    licitaciones:   ['nombre','descripcion','fecha_cierre','fecha_fallo','monto_estimado','monto_ofertado','estado','id_responsable','id_proyecto','observaciones'],
    hitos:          ['nombre','descripcion','fecha_estimada','fecha_real','porcentaje_avance','monto_cobro','id_responsable','estado','notas'],
    bitacora:       ['descripcion','condiciones_clima','personal_presente','equipos_activos','foto'],
    documentos:     ['nombre','descripcion','version','activo'],
    riesgos:        ['descripcion','categoria','probabilidad','impacto','plan_mitigacion','responsable','estado'],
    permisos:       ['fecha_vencimiento','estado','notas'],
    empleados:      ['nombre','apellido_paterno','apellido_materno','telefono','email','direccion','puesto_cargo','id_departamento','id_supervisor','tipo_contrato','fecha_fin_contrato','salario_base','banco','clabe_interbancaria','activo'],
    departamentos:  ['nombre','descripcion','id_responsable','activo'],
    asignaciones:   ['fecha_fin','rol_en_proyecto','horas_semanales','activo'],
    ausencias:      ['fecha_fin','dias_habiles','folio_imss','id_proyecto','observaciones','estado','aprobada_por'],
    capacitaciones: ['nombre','descripcion','instructor','duracion_horas','fecha_inicio','fecha_fin','costo','id_proyecto','obligatoria','activo'],
    emp_cap:        ['fecha_asistencia','calificacion','aprobado'],
    accidentes:     ['descripcion','parte_cuerpo','dias_incapacidad','requirio_hospitalizacion','folio_imss','acciones_correctivas','costo_estimado','testigos','estado'],
    nomina:         ['fecha_pago','total_percepciones','total_deducciones','total_neto','estado','aprobada','aprobada_por'],
    detalle_nomina: ['salario_base','horas_extra','monto_horas_extra','bonos','total_percepciones','isr','infonavit','otras_deducciones','total_deducciones','neto_pagar'],
    prestamos:      ['monto_pendiente','numero_pagos','monto_descuento_quincenal','fecha_liquidacion_est','estado','notas'],
    facturas:       ['fecha_vencimiento','estado','notas'],
    pagos:          ['metodo_pago','referencia_banco','comprobante'],
    retenciones:    ['fecha_liberacion','monto_liberado','estado','notas'],
    cotizaciones:   ['fecha_vencimiento','estado','total_cotizado','notas'],
    garantias:      ['aseguradora','monto','porcentaje','fecha_vencimiento','estado','notas'],
    proveedores:    ['razon_social','tipo_proveedor','telefono','email','direccion','ciudad','estado','pais','calificacion','cuenta_bancaria','clabe','banco','notas','activo'],
    contactos:      ['nombre','cargo','telefono','email','es_principal','activo'],
    subcontratistas:['razon_social','contacto_principal','telefono','email','especialidad','calificacion','cuenta_bancaria','clabe','banco','activo','notas'],
    contratos_sub:  ['descripcion_trabajo','fecha_fin_est','monto_contratado','estado'],
    equipos:        ['nombre','tipo','id_proyecto','estado','costo_hora','propio_rentado','proxima_revision','activo'],
    mantenimiento:  ['fecha_fin','descripcion','costo','id_tecnico','proveedor_serv','estado','observaciones'],
    almacenes:      ['nombre','ubicacion','responsable','activo'],
    clientes:       ['tipo_persona','nombre_razon_social','nombre_contacto','telefono','email','direccion','ciudad','estado','pais','codigo_postal','calificacion','notas','activo'],
    seguridad:      ['descripcion','acciones_tomadas','costo_incidente','estado'],
};

// Tipo de input por nombre de campo
const FIELD_META = {
    // Selects locales
    estado:          { type:'select', options:['Activo','Inactivo','Vigente','Por_Vencer','Vencida','Pagada','Pagada_Parcial','Pendiente','Aprobada','Completado','Ganada','Liquidado','Cumplido','Firmado','En_Ejecucion','Presentada','En_Preparacion','Programado','En_Proceso','Reportado','Solicitada','Disponible','Cancelado','Cancelada','Baja','Perdida','Materializado','Ejecutada','Revocado','Rechazada','Cerrado','Pausado','En_Riesgo','Mitigado','Borrador','Retenido','Liberado_Parcial','Liberado','Licitacion','Planificacion'] },
    activo:          { type:'select', options:['1','0'],   labels:['Activo','Inactivo'] },
    aprobada:        { type:'select', options:['1','0'],   labels:['Sí','No'] },
    aprobado:        { type:'select', options:['1','0'],   labels:['Sí','No'] },
    obligatoria:     { type:'select', options:['1','0'],   labels:['Sí','No'] },
    es_principal:    { type:'select', options:['1','0'],   labels:['Sí','No'] },
    requirio_hospitalizacion: { type:'select', options:['1','0'], labels:['Sí','No'] },
    tipo_contrato:   { type:'select', options:['Planta','Temporal','Honorarios','Practicante','Subcontratado','Confianza','Otro'] },
    tipo_proveedor:  { type:'select', options:['Material','Equipos','Servicios','Logística','Tecnología','Otro'] },
    tipo_persona:    { type:'select', options:['Fisica','Moral'] },
    moneda:          { type:'select', options:['MXN','USD','EUR'] },
    metodo_pago:     { type:'select', options:['Transferencia','Cheque','Efectivo','Tarjeta'] },
    propio_rentado:  { type:'select', options:['Propio','Rentado'] },
    probabilidad:    { type:'select', options:['Baja','Media','Alta'] },
    impacto:         { type:'select', options:['Bajo','Medio','Alto'] },
    categoria:       { type:'select', options:['Tecnico','Financiero','Legal','Ambiental','Seguridad','Clima','Proveedor','Otro'] },
    tipo_nomina:     { type:'select', options:['Ordinaria','Extraordinaria','Finiquito'] },
    // Fechas
    fecha_inicio:           { type:'date' },
    fecha_fin:              { type:'date' },
    fecha_estimada:         { type:'date' },
    fecha_real:             { type:'date' },
    fecha_pago:             { type:'date' },
    fecha_vencimiento:      { type:'date' },
    fecha_retencion:        { type:'date' },
    fecha_liberacion:       { type:'date' },
    fecha_liquidacion_est:  { type:'date' },
    fecha_fin_contrato:     { type:'date' },
    fecha_fin_estimada:     { type:'date' },
    fecha_fin_real:         { type:'date' },
    fecha_fin_est:          { type:'date' },
    proxima_revision:       { type:'date' },
    fecha_inicio_estimada:  { type:'date' },
    fecha_inicio_real:      { type:'date' },
    fecha_cierre:           { type:'date' },
    fecha_fallo:            { type:'date' },
    // Numéricos
    salario_base:            { type:'number' },
    monto_total:             { type:'number' },
    monto:                   { type:'number' },
    costo:                   { type:'number' },
    costo_hora:              { type:'number' },
    costo_estimado:          { type:'number' },
    costo_incidente:         { type:'number' },
    total_cotizado:          { type:'number' },
    monto_cobro:             { type:'number' },
    presupuesto_estimado:    { type:'number' },
    costo_actual:            { type:'number' },
    penalizacion_dia:        { type:'number' },
    anticipo_pct:            { type:'number' },
    porcentaje_avance:       { type:'number' },
    porcentaje:              { type:'number' },
    calificacion:            { type:'number' },
    horas_semanales:         { type:'number' },
    dias_habiles:            { type:'number' },
    dias_incapacidad:        { type:'number' },
    duracion_horas:          { type:'number' },
    numero_pagos:            { type:'number' },
    monto_pendiente:         { type:'number' },
    monto_liberado:          { type:'number' },
    monto_contratado:        { type:'number' },
    monto_descuento_quincenal:{ type:'number' },
    total_percepciones:      { type:'number' },
    total_deducciones:       { type:'number' },
    total_neto:              { type:'number' },
    neto_pagar:              { type:'number' },
    isr:                     { type:'number' },
    imss:                    { type:'number' },
    infonavit:               { type:'number' },
    otras_deducciones:       { type:'number' },
    bonos:                   { type:'number' },
    horas_extra:             { type:'number' },
    monto_horas_extra:       { type:'number' },
    personal_presente:       { type:'number' },
    // Textareas
    descripcion:             { type:'textarea' },
    descripcion_alcance:     { type:'textarea' },
    descripcion_trabajo:     { type:'textarea' },
    plan_mitigacion:         { type:'textarea' },
    acciones_correctivas:    { type:'textarea' },
    acciones_tomadas:        { type:'textarea' },
    observaciones:           { type:'textarea' },
    notas:                   { type:'textarea' },
    testigos:                { type:'textarea' },
};

// Campos FK que se resuelven con selects remotos
const REMOTE_FIELD_MAP = {
    id_director:    'proy-director',
    id_responsable: { proyectos:'lic-responsable', hitos:'hito-responsable', licitaciones:'lic-responsable', riesgos:'riesgo-responsable' },
    id_proyecto:    { licitaciones:'lic-proyecto', hitos:'hito-proyecto', bitacora:'bit-proyecto', documentos:'doc-proyecto', riesgos:'riesgo-proyecto', ausencias:'aus-proyecto', capacitaciones:'cap-proyecto', accidentes:'acc-proyecto', equipos:'eq-proyecto', almacenes:'alm-proyecto', contratos_sub:'csub-proyecto', cotizaciones:'cot-proyecto', garantias:'gar-proyecto' },
    id_departamento:'emp-departamento',
    id_supervisor:  'emp-supervisor',
    id_tecnico:     'mant-tecnico',
    aprobada_por:   { nomina:'nom-aprobada', ausencias:'aus-aprobada' },
    responsable:    'riesgo-responsable',
};

/*****************************************************************************************************************************************************************************/

/**
 * Realiza una solicitud a la API REST del sistema.
 *
 * @param {string}      module - Módulo a consultar (ej. 'proyectos', 'empleados').
 * @param {string}      method - Método HTTP ('GET', 'POST', 'PUT', 'DELETE').
 * @param {object|null} body   - Cuerpo de la solicitud para POST y PUT.
 * @param {number|null} id     - ID del registro para operaciones específicas.
 *
 * @returns {Promise<Array|object>} Respuesta de la API en JSON, o array vacío si hay error.
 */
async function apiFetch(module, method = 'GET', body = null, id = null) {
    try {
        const key = MODULE_MAP[module] ?? module;
        let url   = `${API}${DB}_${key}`;
        if (id) url += `&id=${id}`;

        const opts = {
            method,
            headers: {
                'Content-Type':   'application/json',
                'Authorization':  `Bearer ${cs_token}`,
                'X-Internal-Key': 'ConstructSys_Internal_2026_!xK9',
            },
        };
        if (body) opts.body = JSON.stringify(body);

        const res  = await fetch(url, opts);
        const text = await res.text();
        console.log('RAW:', text);
        return JSON.parse(text);

    } catch {
        toast('Error de conexión con el servidor.', 'error');
        return [];
    }
}

/**
 * Devuelve el texto visible para una opción de select dado su tipo y registro.
 *
 * @param {string} tipo - Tipo de módulo ('proyectos', 'empleados', etc.).
 * @param {object} r    - Registro de datos de la API.
 *
 * @returns {string} Etiqueta formateada para mostrar en el select.
 */
function labelFn(tipo, r) {
    switch (tipo) {
        case 'proyectos':     return `[${r.id_proyecto}] ${r.nombre_proyecto}`;
        case 'clientes':      return `[${r.id_cliente}] ${r.nombre_razon_social}`;
        case 'empleados':     return `[${r.id_empleado}] ${r.nombre} ${r.apellido_paterno || ''}`;
        case 'proveedores':   return `[${r.id_proveedor}] ${r.razon_social}`;
        case 'subcontratistas':return `[${r.id_subcontratista}] ${r.razon_social}`;
        case 'contratos':     return `[${r.id_contrato}] ${r.numero_contrato}`;
        case 'licitaciones':  return `[${r.id_licitacion}] ${r.numero_licitacion}`;
        case 'departamentos': return `[${r.id_departamento}] ${r.nombre}`;
        case 'capacitaciones':return `[${r.id_capacitacion}] ${r.nombre}`;
        case 'nomina':        return `[${r.id_nomina}] ${r.periodo}`;
        case 'facturas':      return `[${r.id_factura}] ${r.numero_factura}`;
        case 'equipos':       return `[${r.id_equipo}] ${r.nombre}`;
        default:              return `[${Object.values(r)[0]}]`;
    }
}

/**
 * Devuelve el valor de la PK de un registro dado su tipo.
 *
 * @param {string} tipo - Tipo de módulo.
 * @param {object} r    - Registro de datos de la API.
 *
 * @returns {*} Valor de la clave primaria.
 */
function pkFn(tipo, r) {
    const map = {
        proyectos:     'id_proyecto',
        clientes:      'id_cliente',
        empleados:     'id_empleado',
        proveedores:   'id_proveedor',
        subcontratistas:'id_subcontratista',
        contratos:     'id_contrato',
        licitaciones:  'id_licitacion',
        departamentos: 'id_departamento',
        capacitaciones:'id_capacitacion',
        nomina:        'id_nomina',
        facturas:      'id_factura',
        equipos:       'id_equipo',
    };
    return r[map[tipo]] ?? Object.values(r)[0];
}

/**
 * Resuelve el ID del select remoto correspondiente a un campo FK en el módulo activo.
 *
 * @param {string} field  - Nombre del campo FK (ej. 'id_proyecto').
 * @param {string} module - Módulo activo.
 *
 * @returns {string|null} ID del elemento <select>, o null si no aplica.
 */
function resolveRemoteSelId(field, module) {
    const entry = REMOTE_FIELD_MAP[field];
    if (!entry) return null;
    if (typeof entry === 'string') return entry;
    return entry[module] ?? null;
}

/*****************************************************************************************************************************************************************************/

/**
 * Puebla un elemento <select> con datos obtenidos de la API.
 *
 * @param {string} selectId   - ID del elemento <select> en el DOM.
 * @param {string} tipo       - Módulo a consultar para obtener las opciones.
 * @param {string} placeholder - Texto de la opción vacía inicial.
 */
async function poblarSelect(selectId, tipo, placeholder) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    const cur = sel.value;
    sel.innerHTML = `<option value="">${placeholder}</option>`;
    const data = await apiFetch(tipo);
    if (Array.isArray(data)) {
        data.forEach(r => {
            const o       = document.createElement('option');
            o.value       = pkFn(tipo, r);
            o.textContent = labelFn(tipo, r);
            sel.appendChild(o);
        });
    }
    if (cur) sel.value = cur;
}

/**
 * Carga todos los selects del módulo indicado según la configuración de PAGE_SELECTS.
 *
 * @param {string} key - Clave del módulo activo.
 */
async function cargarSelectsDelModulo(key) {
    const cfg = PAGE_SELECTS[key];
    if (!cfg) return;
    for (const [tipo, ids] of Object.entries(cfg)) {
        for (const id of ids) {
            await poblarSelect(id, tipo, PLACEHOLDER[tipo] || `— Seleccionar ${tipo} —`);
        }
    }
}

/*****************************************************************************************************************************************************************************/

/**
 * Devuelve un badge HTML con clase de color según el valor de estado.
 *
 * @param {string} val - Valor del estado (ej. 'Activo', 'Cancelado').
 *
 * @returns {string} HTML del badge correspondiente.
 */
function badgeStatus(val) {
    if (!val) return '<span class="badge badge-gray">—</span>';
    const v   = val.toString();
    const map = {
        Activo:'badge-green',       Activa:'badge-green',       Vigente:'badge-green',
        Pagada:'badge-green',       Aprobada:'badge-green',     Completado:'badge-green',
        Ganada:'badge-green',       Liquidado:'badge-green',    Cumplido:'badge-green',
        Firmado:'badge-green',      En_Ejecucion:'badge-blue',  Presentada:'badge-blue',
        En_Preparacion:'badge-blue',Programado:'badge-blue',    En_Proceso:'badge-blue',
        Reportado:'badge-blue',     Solicitada:'badge-blue',    Disponible:'badge-blue',
        Inactivo:'badge-red',       Vencida:'badge-red',        Cancelado:'badge-red',
        Cancelada:'badge-red',      Baja:'badge-red',           Perdida:'badge-red',
        Materializado:'badge-red',  Ejecutada:'badge-red',      Revocado:'badge-red',
        Rechazada:'badge-red',      Cerrado:'badge-red',        Pausado:'badge-gold',
        En_Riesgo:'badge-gold',     Por_Vencer:'badge-gold',    Mitigado:'badge-gold',
        Borrador:'badge-gray',      Pendiente:'badge-gray',     Retenido:'badge-gray',
        Licitacion:'badge-gold',    Planificacion:'badge-gold',
    };
    const cls = map[v] || 'badge-gold';
    return `<span class="badge ${cls}">${v.replace(/_/g, ' ')}</span>`;
}

/**
 * Formatea un valor numérico como moneda mexicana (MXN).
 *
 * @param {number|string} v - Valor a formatear.
 *
 * @returns {string} Cadena con formato de moneda (ej. '$1,234.56').
 */
function fmtMXN(v) {
    return parseFloat(v || 0).toLocaleString('es-MX', { style:'currency', currency:'MXN' });
}

/*****************************************************************************************************************************************************************************/

/**
 * Muestra una notificación tipo toast en pantalla.
 *
 * @param {string} msg  - Mensaje a mostrar.
 * @param {string} type - Tipo de toast: 'success' (verde) o 'error' (rojo).
 */
function toast(msg, type = 'success') {
    const tc = document.getElementById('toast-container');
    const t  = document.createElement('div');
    t.className = 'toast' + (type === 'error' ? ' error' : '');
    t.innerHTML = (type === 'success' ? '✔' : '⚠') + ' ' + msg;
    tc.appendChild(t);
    setTimeout(() => {
        t.style.animation = 'slideOut .3s ease forwards';
        setTimeout(() => t.remove(), 300);
    }, 3200);
}

/**
 * Activa el observer de Intersection para los elementos con clase .fade-in
 * que aún no son visibles, animándolos al entrar al viewport.
 */
function observeFadeIns() {
    const io = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); }
        });
    }, { threshold: 0.05 });
    document.querySelectorAll('.fade-in:not(.visible)').forEach(el => io.observe(el));
}

/*****************************************************************************************************************************************************************************/

/**
 * Navega a la página indicada: activa el panel correspondiente, actualiza la
 * barra superior, carga la tabla SQL y los selects del módulo si aplica.
 *
 * @param {string} key - Clave del módulo destino (ej. 'proyectos', 'dashboard').
 */
function navigate(key) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('page-' + key)?.classList.add('active');
    document.querySelector(`[data-page="${key}"]`)?.classList.add('active');
    document.getElementById('topbar-title').textContent = PAGES[key] || key;
    document.getElementById('topbar-bc').textContent    = 'Inicio / ' + (PAGES[key] || key);
    window.scrollTo({ top: 0, behavior: 'smooth' });
    observeFadeIns();
    if (SQL_MODULES.includes(key)) {
        cargarTabla(key);
        cargarSelectsDelModulo(key);
    }
    if (key === 'danger') initDangerZone();
    closeSidebar();
}

/**
 * Alterna la visibilidad del sidebar lateral.
 * En móvil desliza sobre el contenido; en desktop colapsa empujando el main.
 */
function toggleSidebar() {
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('overlay');
    const main     = document.querySelector('.main');
    const isMobile = window.innerWidth <= 900;
    if (isMobile) {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
    } else {
        sidebar.classList.toggle('collapsed');
        main.classList.toggle('expanded');
    }
}

/**
 * Cierra el sidebar en dispositivos móviles al navegar a una página.
 */
function closeSidebar() {
    const isMobile = window.innerWidth <= 900;
    if (isMobile) {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('overlay').classList.remove('open');
    }
}

/*****************************************************************************************************************************************************************************/

/**
 * Obtiene los datos del módulo desde la API y renderiza la tabla correspondiente.
 *
 * @param {string} module - Clave del módulo a cargar.
 */
async function cargarTabla(module) {
    const datos = await apiFetch(module);
    renderTable(module, Array.isArray(datos) ? datos : []);
    updateBadge(module, Array.isArray(datos) ? datos.length : 0);
}

/**
 * Ejecuta una búsqueda y re-renderiza la tabla con los resultados filtrados.
 *
 * @param {string} module - Clave del módulo a buscar.
 */
async function buscarEnTabla(module) {
    const datos = await apiFetch(module);
    renderTable(module, Array.isArray(datos) ? datos : []);
}

/**
 * Actualiza el badge numérico de un módulo en el sidebar.
 *
 * @param {string} module - Clave del módulo.
 * @param {number} count  - Número de registros a mostrar.
 */
function updateBadge(module, count) {
    const el = document.getElementById('badge-' + module);
    if (el) el.textContent = count;
}

/**
 * Renderiza las filas de la tabla del módulo, aplicando el filtro de búsqueda activo.
 *
 * @param {string} module - Clave del módulo.
 * @param {Array}  datos  - Array de registros a renderizar.
 */
function renderTable(module, datos) {
    const query = (document.getElementById('search-' + module)?.value || '').toLowerCase();
    const data  = datos.filter(r => JSON.stringify(r).toLowerCase().includes(query));
    const tbody = document.getElementById('tbody-' + module);
    if (!tbody) return;
    const pk = PK[module];
    if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="99"><div class="empty-state"><p>No hay registros que mostrar.</p></div></td></tr>`;
        return;
    }
    let html = '';
    data.forEach(r => {
        const id   = r[pk];
        const edit = `<button class="btn-edit" onclick="openEditModal('${module}',${id})" title="Editar registro">🔧</button>`;
        const del  = `<button class="btn btn-danger btn-sm" onclick="deleteRecord('${module}',${id})">Eliminar</button>`;
        switch (module) {
            case 'proyectos':
                html += `<tr><td>${id}</td><td>${r.codigo_proyecto||'—'}</td><td>${r.nombre_proyecto}</td><td><span class="badge badge-gold">${r.tipo_proyecto||'—'}</span></td><td>${badgeStatus(r.estado)}</td><td>${r.ciudad}</td><td>${r.porcentaje_avance||0}%</td><td>${r.fecha_inicio_estimada||'—'}</td><td>${r.fecha_fin_estimada||'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'contratos':
                html += `<tr><td>${id}</td><td>${r.numero_contrato}</td><td><span class="badge badge-gold">${(r.tipo_contrato||'').replace(/_/g,' ')}</span></td><td>${badgeStatus(r.estado)}</td><td>${fmtMXN(r.monto_total)}</td><td>${r.moneda||'MXN'}</td><td>${r.fecha_firma||'—'}</td><td>${r.fecha_inicio||'—'}</td><td>${r.fecha_fin_estimada||'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'licitaciones':
                html += `<tr><td>${id}</td><td>${r.numero_licitacion}</td><td>${r.nombre}</td><td><span class="badge badge-gold">${r.tipo||'—'}</span></td><td>${badgeStatus(r.estado)}</td><td>${r.fecha_cierre||'—'}</td><td>${fmtMXN(r.monto_estimado)}</td><td>${fmtMXN(r.monto_ofertado)}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'hitos':
                html += `<tr><td>${id}</td><td>${r.nombre}</td><td><span class="badge badge-gold">${(r.tipo||'').replace(/_/g,' ')}</span></td><td>${badgeStatus(r.estado)}</td><td>${r.fecha_estimada||'—'}</td><td>${r.fecha_real||'—'}</td><td>${r.porcentaje_avance||0}%</td><td>${fmtMXN(r.monto_cobro)}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'bitacora':
                html += `<tr><td>${id}</td><td>${r.numero_nota||'—'}</td><td>${r.fecha||'—'}</td><td><span class="badge badge-gold">${r.tipo_nota||'—'}</span></td><td>${r.personal_presente||'—'}</td><td>${r.condiciones_clima||'—'}</td><td style="max-width:200px;overflow:hidden;text-overflow:ellipsis">${r.descripcion||'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'documentos':
                html += `<tr><td>${id}</td><td>${r.nombre}</td><td><span class="badge badge-gold">${r.tipo_documento||'—'}</span></td><td>${r.version||'1.0'}</td><td>${r.fecha_subida||'—'}</td><td>${r.activo=='1'?'<span class="badge badge-green">Activo</span>':'<span class="badge badge-red">Inactivo</span>'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'riesgos':
                html += `<tr><td>${id}</td><td><span class="badge badge-gold">${r.categoria||'—'}</span></td><td>${r.probabilidad||'—'}</td><td>${r.impacto||'—'}</td><td>${r.nivel_riesgo?`<span class="badge ${r.nivel_riesgo==='Rojo'?'badge-red':r.nivel_riesgo==='Verde'?'badge-green':'badge-gold'}">${r.nivel_riesgo}</span>`:'—'}</td><td>${badgeStatus(r.estado)}</td><td>${r.fecha_identificacion||'—'}</td><td style="max-width:180px;overflow:hidden;text-overflow:ellipsis">${r.descripcion||'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'permisos':
                html += `<tr><td>${id}</td><td>${r.tipo_permiso||'—'}</td><td>${r.numero_permiso||'—'}</td><td>${r.autoridad_emisora||'—'}</td><td>${r.fecha_emision||'—'}</td><td>${r.fecha_vencimiento||'—'}</td><td>${badgeStatus(r.estado)}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'empleados':
                html += `<tr><td>${id}</td><td>${r.numero_empleado||'—'}</td><td>${r.nombre} ${r.apellido_paterno||''}</td><td>${r.puesto_cargo||'—'}</td><td><span class="badge badge-gold">${(r.tipo_contrato||'').replace(/_/g,' ')}</span></td><td>${fmtMXN(r.salario_base)}</td><td>${r.fecha_contratacion||'—'}</td><td>${r.activo=='1'?'<span class="badge badge-green">Activo</span>':'<span class="badge badge-red">Baja</span>'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'departamentos':
                html += `<tr><td>${id}</td><td>${r.nombre}</td><td>${r.activo=='1'?'<span class="badge badge-green">Activo</span>':'<span class="badge badge-red">Inactivo</span>'}</td><td style="max-width:240px;overflow:hidden;text-overflow:ellipsis">${r.descripcion||'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'asignaciones':
                html += `<tr><td>${id}</td><td>${r.id_empleado||'—'}</td><td>${r.id_proyecto||'—'}</td><td>${r.rol_en_proyecto||'—'}</td><td>${r.horas_semanales||'—'}</td><td>${r.fecha_inicio||'—'}</td><td>${r.fecha_fin||'—'}</td><td>${r.activo=='1'?'<span class="badge badge-green">Activo</span>':'<span class="badge badge-red">Inactivo</span>'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'ausencias':
                html += `<tr><td>${id}</td><td>${r.id_empleado||'—'}</td><td><span class="badge badge-gold">${(r.tipo||'').replace(/_/g,' ')}</span></td><td>${badgeStatus(r.estado)}</td><td>${r.fecha_inicio||'—'}</td><td>${r.fecha_fin||'—'}</td><td>${r.dias_habiles||0}</td><td>${r.folio_imss||'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'capacitaciones':
                html += `<tr><td>${id}</td><td>${r.nombre}</td><td><span class="badge badge-gold">${r.tipo||'—'}</span></td><td>${r.instructor||'—'}</td><td>${r.duracion_horas||0}</td><td>${r.fecha_inicio||'—'}</td><td>${r.fecha_fin||'—'}</td><td>${fmtMXN(r.costo)}</td><td>${r.obligatoria=='1'?'<span class="badge badge-red">Sí</span>':'<span class="badge badge-gray">No</span>'}</td><td>${r.activo=='1'?'<span class="badge badge-green">Activa</span>':'<span class="badge badge-red">Inactiva</span>'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'emp_cap':
                html += `<tr><td>${id}</td><td>${r.id_empleado||'—'}</td><td>${r.id_capacitacion||'—'}</td><td>${r.fecha_asistencia||'—'}</td><td>${r.calificacion||'—'}</td><td>${r.aprobado=='1'?'<span class="badge badge-green">Sí</span>':r.aprobado=='0'?'<span class="badge badge-red">No</span>':'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'accidentes':
                html += `<tr><td>${id}</td><td><span class="badge badge-gold">${(r.tipo_accidente||'').replace(/_/g,' ')}</span></td><td>${badgeStatus(r.estado)}</td><td>${r.fecha_accidente||'—'}</td><td>${r.parte_cuerpo||'—'}</td><td>${r.dias_incapacidad||0}</td><td>${r.requirio_hospitalizacion=='1'?'<span class="badge badge-red">Sí</span>':'<span class="badge badge-green">No</span>'}</td><td>${r.folio_imss||'—'}</td><td>${fmtMXN(r.costo_estimado)}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'nomina':
                html += `<tr><td>${id}</td><td>${r.periodo||'—'}</td><td><span class="badge badge-gold">${r.tipo_nomina||'—'}</span></td><td>${badgeStatus(r.estado)}</td><td>${r.fecha_pago||'—'}</td><td>${fmtMXN(r.total_percepciones)}</td><td>${fmtMXN(r.total_deducciones)}</td><td>${fmtMXN(r.total_neto)}</td><td>${r.aprobada=='1'?'<span class="badge badge-green">Sí</span>':'<span class="badge badge-gray">No</span>'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'detalle_nomina':
                html += `<tr><td>${id}</td><td>${r.id_nomina||'—'}</td><td>${r.id_empleado||'—'}</td><td>${fmtMXN(r.salario_base)}</td><td>${r.horas_extra||0}</td><td>${fmtMXN(r.bonos)}</td><td>${fmtMXN(r.total_percepciones)}</td><td>${fmtMXN(r.total_deducciones)}</td><td>${fmtMXN(r.neto_pagar)}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'prestamos':
                html += `<tr><td>${id}</td><td>${r.id_empleado||'—'}</td><td><span class="badge badge-gold">${(r.tipo||'').replace(/_/g,' ')}</span></td><td>${badgeStatus(r.estado)}</td><td>${fmtMXN(r.monto_total)}</td><td>${fmtMXN(r.monto_pendiente)}</td><td>${r.numero_pagos||'—'}</td><td>${fmtMXN(r.monto_descuento_quincenal)}</td><td>${r.fecha_otorgamiento||'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'facturas':
                html += `<tr><td>${id}</td><td>${r.numero_factura}</td><td><span class="badge badge-gold">${r.tipo_factura||'—'}</span></td><td>${badgeStatus(r.estado)}</td><td>${r.fecha_emision||'—'}</td><td>${r.fecha_vencimiento||'—'}</td><td>${fmtMXN(r.subtotal)}</td><td>${fmtMXN(r.iva)}</td><td>${fmtMXN(r.total)}</td><td>${r.moneda||'MXN'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'pagos':
                html += `<tr><td>${id}</td><td>${r.id_factura||'—'}</td><td><span class="badge badge-gold">${r.metodo_pago||'—'}</span></td><td>${r.fecha_pago||'—'}</td><td>${fmtMXN(r.monto)}</td><td>${r.referencia_banco||'—'}</td><td>${r.comprobante||'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'retenciones':
                html += `<tr><td>${id}</td><td>${r.id_contrato||'—'}</td><td><span class="badge badge-gold">${(r.tipo||'').replace(/_/g,' ')}</span></td><td>${badgeStatus(r.estado)}</td><td>${fmtMXN(r.monto)}</td><td>${fmtMXN(r.monto_liberado)}</td><td>${r.porcentaje||'—'}%</td><td>${r.fecha_retencion||'—'}</td><td>${r.fecha_liberacion||'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'cotizaciones':
                html += `<tr><td>${id}</td><td>${r.numero_cotizacion}</td><td>${r.id_proveedor||'—'}</td><td>${badgeStatus(r.estado)}</td><td>${r.fecha_solicitud||'—'}</td><td>${r.fecha_vencimiento||'—'}</td><td>${fmtMXN(r.total_cotizado)}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'garantias':
                html += `<tr><td>${id}</td><td><span class="badge badge-gold">${(r.tipo||'').replace(/_/g,' ')}</span></td><td>${r.numero_poliza||'—'}</td><td>${r.aseguradora||'—'}</td><td>${fmtMXN(r.monto)}</td><td>${r.fecha_inicio||'—'}</td><td>${r.fecha_vencimiento||'—'}</td><td>${badgeStatus(r.estado)}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'proveedores':
                html += `<tr><td>${id}</td><td>${r.razon_social}</td><td>${r.rfc}</td><td><span class="badge badge-gold">${r.tipo_proveedor||'—'}</span></td><td>${r.ciudad}</td><td>${r.email||'—'}</td><td>${r.calificacion?'⭐'.repeat(+r.calificacion):'—'}</td><td>${r.activo=='1'?'<span class="badge badge-green">Activo</span>':'<span class="badge badge-red">Inactivo</span>'}</td><td>${r.fecha_alta||'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'contactos':
                html += `<tr><td>${id}</td><td>${r.nombre} ${r.apellidos||''}</td><td>${r.cargo||'—'}</td><td>${r.telefono||'—'}</td><td>${r.email||'—'}</td><td>${r.es_principal=='1'?'<span class="badge badge-green">Sí</span>':'<span class="badge badge-gray">No</span>'}</td><td>${r.activo=='1'?'<span class="badge badge-green">Activo</span>':'<span class="badge badge-red">Inactivo</span>'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'subcontratistas':
                html += `<tr><td>${id}</td><td>${r.razon_social}</td><td>${r.rfc}</td><td>${r.especialidad||'—'}</td><td>${r.contacto_principal||'—'}</td><td>${r.telefono||'—'}</td><td>${r.calificacion?'⭐'.repeat(+r.calificacion):'—'}</td><td>${r.activo=='1'?'<span class="badge badge-green">Activo</span>':'<span class="badge badge-red">Inactivo</span>'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'contratos_sub':
                html += `<tr><td>${id}</td><td>${r.id_subcontratista||'—'}</td><td>${r.numero_contrato}</td><td>${badgeStatus(r.estado)}</td><td>${fmtMXN(r.monto_contratado)}</td><td>${r.fecha_inicio||'—'}</td><td>${r.fecha_fin_est||'—'}</td><td style="max-width:180px;overflow:hidden;text-overflow:ellipsis">${r.descripcion_trabajo||'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'equipos':
                html += `<tr><td>${id}</td><td>${r.codigo||'—'}</td><td>${r.nombre}</td><td><span class="badge badge-gold">${r.tipo||'—'}</span></td><td>${r.marca||'—'}</td><td>${r.anio_fabricacion||'—'}</td><td>${badgeStatus(r.estado)}</td><td><span class="badge badge-gray">${r.propio_rentado||'—'}</span></td><td>${r.proxima_revision||'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'mantenimiento':
                html += `<tr><td>${id}</td><td>${r.id_equipo||'—'}</td><td><span class="badge badge-gold">${r.tipo_mant||'—'}</span></td><td>${badgeStatus(r.estado)}</td><td>${r.fecha_inicio||'—'}</td><td>${r.fecha_fin||'—'}</td><td>${fmtMXN(r.costo)}</td><td>${r.proveedor_serv||'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'almacenes':
                html += `<tr><td>${id}</td><td>${r.nombre}</td><td>${r.ubicacion||'—'}</td><td>${r.activo=='1'?'<span class="badge badge-green">Activo</span>':'<span class="badge badge-red">Inactivo</span>'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'clientes':
                html += `<tr><td>${id}</td><td><span class="badge badge-gold">${r.tipo_persona||'—'}</span></td><td>${r.nombre_razon_social}</td><td>${r.rfc||'—'}</td><td>${r.telefono||'—'}</td><td>${r.ciudad||'—'}</td><td>${r.calificacion?'⭐'.repeat(+r.calificacion):'—'}</td><td>${r.activo=='1'?'<span class="badge badge-green">Activo</span>':'<span class="badge badge-red">Inactivo</span>'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            case 'seguridad':
                html += `<tr><td>${id}</td><td>${r.tipo_incidente||'—'}</td><td>${badgeStatus(r.estado)}</td><td>${r.fecha_hora||'—'}</td><td>${fmtMXN(r.costo_incidente)}</td><td style="max-width:200px;overflow:hidden;text-overflow:ellipsis">${r.descripcion||'—'}</td><td><div class="td-actions">${edit}${del}</div></td></tr>`; break;
            default:
                html += `<tr><td>${id}</td><td colspan="98">${JSON.stringify(r)}</td></tr>`;
        }
    });
    tbody.innerHTML = html;
}

/*****************************************************************************************************************************************************************************/

/**
 * Consulta todos los módulos y actualiza los contadores del dashboard y badges del sidebar.
 */
async function cargarDashboard() {
    const pares = [
        ['proyectos','dash-proy'],     ['contratos','dash-cont'],     ['licitaciones','dash-lic'],
        ['hitos','dash-hitos'],        ['bitacora','dash-bit'],        ['documentos','dash-doc'],
        ['riesgos','dash-riesgos'],    ['permisos','dash-perm'],       ['empleados','dash-emp'],
        ['departamentos','dash-dept'], ['asignaciones','dash-asig'],   ['ausencias','dash-aus'],
        ['capacitaciones','dash-cap'], ['emp_cap','dash-empcap'],      ['accidentes','dash-acc'],
        ['nomina','dash-nom'],         ['detalle_nomina','dash-detnom'],['prestamos','dash-prest'],
        ['facturas','dash-fac'],       ['pagos','dash-pag'],           ['retenciones','dash-ret'],
        ['cotizaciones','dash-cot'],   ['garantias','dash-gar'],       ['clientes','dash-cli'],
        ['proveedores','dash-prov'],   ['contactos','dash-cont2'],     ['subcontratistas','dash-sub'],
        ['contratos_sub','dash-csub'], ['equipos','dash-eq'],          ['mantenimiento','dash-mant'],
        ['almacenes','dash-alm'],      ['seguridad','dash-seg'],
    ];
    for (const [mod, elId] of pares) {
        const datos = await apiFetch(mod);
        const n     = Array.isArray(datos) ? datos.length : 0;
        const el    = document.getElementById(elId);
        if (el) el.textContent = n;
        updateBadge(mod, n);
    }
}

/*****************************************************************************************************************************************************************************/

/**
 * Envía el formulario de creación de un módulo al backend (POST).
 * Valida el formulario, construye el objeto de datos y recarga la tabla tras el guardado.
 *
 * @param {string} module - Clave del módulo cuyo formulario se va a enviar.
 *
 * @returns {boolean} Siempre retorna false para evitar el envío nativo del formulario.
 */
async function submitForm(module) {
    const form = document.getElementById('form-' + module);
    if (!form.checkValidity()) { form.reportValidity(); return false; }
    const obj = {};
    new FormData(form).forEach((v, k) => { obj[k] = typeof v === 'string' ? v.trim() : v; });
    const result = await apiFetch(module, 'POST', obj);
    if (result && result.error) {
        toast('Error: ' + result.error, 'error');
    } else {
        toast('Registro guardado exitosamente.');
        form.reset();
        await cargarTabla(module);
        await cargarDashboard();
    }
    return false;
}

/**
 * Elimina un registro del módulo indicado tras confirmar con el usuario.
 *
 * @param {string} module - Clave del módulo.
 * @param {number} id     - ID del registro a eliminar.
 */
async function deleteRecord(module, id) {
    if (!confirm('¿Seguro que deseas eliminar este registro?')) return;
    const result = await apiFetch(module, 'DELETE', null, id);
    if (result && result.error) {
        toast('Error al eliminar: ' + result.error, 'error');
    } else {
        toast('Registro eliminado.', 'error');
        await cargarTabla(module);
        await cargarDashboard();
    }
}

/*****************************************************************************************************************************************************************************/

/**
 * Abre el modal de edición para el registro indicado, construye el formulario
 * dinámico y puebla los selects remotos con el valor actual del registro.
 *
 * @param {string} module - Clave del módulo.
 * @param {number} id     - ID del registro a editar.
 */
async function openEditModal(module, id) {
    editModule = module;
    editId     = id;

    const datos  = await apiFetch(module);
    const pk     = PK[module];
    const record = Array.isArray(datos) ? datos.find(r => String(r[pk]) === String(id)) : null;

    if (!record) { toast('No se encontró el registro.', 'error'); return; }

    document.getElementById('edit-form-container').innerHTML = buildEditForm(module, record);

    // Poblar selects remotos dentro del modal y restaurar el valor actual
    const cfg = PAGE_SELECTS[module] || {};
    for (const [tipo, selIds] of Object.entries(cfg)) {
        for (const originalSelId of selIds) {
            const originalSel = document.getElementById(originalSelId);
            if (!originalSel) continue;
            const fieldName = originalSel.name;
            if (UPDATE_FIELDS[module]?.includes(fieldName)) {
                await poblarSelect('edit-' + originalSelId, tipo, PLACEHOLDER[tipo] || '— Seleccionar —');
                const editSel = document.getElementById('edit-' + originalSelId);
                if (editSel && record[fieldName] !== undefined && record[fieldName] !== null) {
                    editSel.value = String(record[fieldName]);
                }
            }
        }
    }

    document.getElementById('edit-modal').classList.add('open');
}

/**
 * Cierra el modal de edición y limpia el estado.
 */
function closeEditModal() {
    document.getElementById('edit-modal').classList.remove('open');
    editModule = null;
    editId     = null;
}

/**
 * Construye dinámicamente el HTML del formulario de edición según los campos
 * definidos en UPDATE_FIELDS y los metadatos de FIELD_META.
 *
 * @param {string} module - Clave del módulo.
 * @param {object} record - Registro con los valores actuales a pre-llenar.
 *
 * @returns {string} HTML del formulario listo para insertar en el DOM.
 */
function buildEditForm(module, record) {
    const fields = UPDATE_FIELDS[module] || [];
    let html = '<div class="form-grid">';

    for (const field of fields) {
        const val          = record[field] ?? '';
        const label        = field.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        const meta         = FIELD_META[field];
        const remoteSelId  = resolveRemoteSelId(field, module);

        if (remoteSelId) {
            html += `
                <div class="form-group">
                    <label>${label}</label>
                    <select id="edit-${remoteSelId}" name="${field}">
                        <option value="">— Cargando… —</option>
                    </select>
                </div>`;

        } else if (meta?.type === 'select') {
            const opts = meta.options.map((o, i) => {
                const lbl = meta.labels ? meta.labels[i] : o.replace(/_/g, ' ');
                return `<option value="${o}"${String(val) === String(o) ? ' selected' : ''}>${lbl}</option>`;
            }).join('');
            html += `
                <div class="form-group">
                    <label>${label}</label>
                    <select name="${field}">${opts}</select>
                </div>`;

        } else if (meta?.type === 'textarea') {
            html += `
                <div class="form-group full">
                    <label>${label}</label>
                    <textarea name="${field}">${val}</textarea>
                </div>`;

        } else if (meta?.type === 'date') {
            html += `
                <div class="form-group">
                    <label>${label}</label>
                    <input type="date" name="${field}" value="${val}"/>
                </div>`;

        } else if (meta?.type === 'number') {
            html += `
                <div class="form-group">
                    <label>${label}</label>
                    <input type="number" name="${field}" value="${val}" step="0.01" min="0"/>
                </div>`;

        } else {
            html += `
                <div class="form-group">
                    <label>${label}</label>
                    <input type="text" name="${field}" value="${val}"/>
                </div>`;
        }
    }

    html += '</div>';
    return html;
}

/**
 * Recoge los valores del formulario del modal y envía un PUT al backend para actualizar el registro.
 */
async function submitEdit() {
    if (!editModule || !editId) return;

    const container = document.getElementById('edit-form-container');
    const obj       = {};
    container.querySelectorAll('input, select, textarea').forEach(el => {
        if (el.name) obj[el.name] = el.value.trim();
    });

    const result = await apiFetch(editModule, 'PUT', obj, editId);
    if (result && result.error) {
        toast('Error: ' + result.error, 'error');
    } else {
        toast('Registro actualizado correctamente.');
        closeEditModal();
        await cargarTabla(editModule);
        await cargarDashboard();
    }
}

/**
 * Filtra las tarjetas del dashboard según el texto ingresado en el buscador.
 *
 * @param {string} query - Texto de búsqueda.
 */
function filtrarDashboard(query) {
    const q = query.trim().toLowerCase();
    document.querySelectorAll('#stats-grid .stat-card').forEach(card => {
        const label = (card.dataset.label || '').toLowerCase();
        card.classList.toggle('hidden', q !== '' && !label.includes(q));
    });
}

/**
 * Filtra los botones de accesos rápidos del dashboard según el texto ingresado.
 *
 * @param {string} query - Texto de búsqueda.
 */
function filtrarAccesos(query) {
    const q = query.trim().toLowerCase();
    document.querySelectorAll('#quick-grid .btn').forEach(btn => {
        const label = (btn.dataset.label || '').toLowerCase();
        btn.classList.toggle('hidden', q !== '' && !label.includes(q));
    });
}

/**
 * Inicializa el campo de confirmación de la Zona de Peligro, habilitando el botón
 * de eliminación solo cuando el nombre de empresa coincide exactamente.
 */
function initDangerZone() {
    const input = document.getElementById('danger-empresa-input');
    const btn = document.getElementById('btn-delete-account');
    const hint  = document.getElementById('danger-hint');
    if (!input || !btn) return;

    const nombre = cs_user.empresa ?? '';
    input.placeholder = nombre;

    input.addEventListener('input', () => {
        const match      = input.value.trim() === nombre;
        btn.disabled     = !match;
        hint.textContent = input.value.trim() === ''
            ? ''
            : match
                ? ''
                : (currentLang === 'en' ? 'Name does not match.' : 'El nombre no coincide.');
        hint.style.color = match ? '#22c55e' : '#dc2626';
    });
}

/**
 * Envía la solicitud de eliminación de cuenta al backend.
 * Limpia el Local Storage y redirige al login si la operación es exitosa.
 */
async function requestElimination() {
    const btn   = document.getElementById('btn-delete-account');
    const input = document.getElementById('danger-empresa-input');
    if (!btn || btn.disabled) return;

    btn.disabled = true;
    btn.classList.add('loading');

    try {
        const res = await fetch('../api/auth/delete/request.php', {
            method:  'POST',
            headers: {
                'Content-Type':   'application/json',
                'Authorization':  `Bearer ${cs_token}`,
                'X-Internal-Key': 'ConstructSys_Internal_2026_!xK9',
            },
            body: JSON.stringify({ lang: currentLang }),
        });
        const data = await res.json();

        if (data.success) {
            localStorage.clear();
            toast(
                currentLang === 'en'
                    ? 'Account deleted. Check your email to recover it within 30 days.'
                    : 'Cuenta eliminada. Revisa tu correo para recuperarla en los próximos 30 días.',
                'success'
            );
            setTimeout(() => { window.location.href = '../views/login.html'; }, 3500);
        } else {
            toast(data.error || 'Error.', 'error');
            btn.disabled = false;
            btn.classList.remove('loading');
        }

    } catch {
        toast(currentLang === 'en' ? 'Connection error.' : 'Error de conexión.', 'error');
        btn.disabled = false;
    } finally {
        btn.classList.remove('loading');
    }
}

// Eventos
document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('topbar-date').textContent =
        new Date().toLocaleDateString('es-MX', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    setTimeout(observeFadeIns, 80);
    cargarDashboard();
});
cargarDashboard();
observeFadeIns();

/*****************************************************************************************************************************************************************************/
