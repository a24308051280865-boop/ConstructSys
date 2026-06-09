<?php
    
// Declaraciones de tipo para verificación de tipos estrictos
declare(strict_types = 1);

// Cargar ENV 
require_once __DIR__ . '/env.php';

// Cargar las variables de entorno desde el archivo .env
require_once __DIR__ . '/../utils/helpers.php';

// Definir constantes a partir de las variables de entorno para configuraciones sensibles y claves secretas
define('JWT_SECRET', getEnvValue('JWT_SECRET'));
define('GOOGLE_CLIENT_ID', getEnvValue('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', getEnvValue('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI', getEnvValue('GOOGLE_REDIRECT_URI'));
define('INTERNAL_API_KEY', getEnvValue('INTERNAL_API_KEY'));
define('INTERNAL_DB_HOST', getEnvValue('DB_HOST'));
define('INTERNAL_DB_NAME', getEnvValue('DB_NAME'));
define('INTERNAL_DB_USER', getEnvValue('DB_USER'));
define('INTERNAL_DB_PASS', getEnvValue('DB_PASS'));
define('MONGO_URI', getEnvValue('MONGO_URI')); 
define('INTERNAL_MONGO_DB', getEnvValue('INTERNAL_MONGO_DB'));  
define('BREVO_API_KEY', getEnvValue('BREVO_API_KEY'));
define('BREVO_FROM_EMAIL', getEnvValue('BREVO_FROM_EMAIL'));
define('BREVO_FROM_NAME', getEnvValue('BREVO_FROM_NAME'));
define('APP_URL', getEnvValue('APP_URL'));

/**
 * Traducciones para los correos de eliminación de cuenta.
 * @var array<string, array<string, string>>
 */
const DELETION_EMAIL_MSSG = [
    'es' => [
        'tagline' => 'Sistema de Gestión de Obras',
        'hi' => 'Hola',
        'subject_request' => 'Confirma la eliminación de tu cuenta — ConstructSys',
        'subject_confirm' => 'Cuenta programada para eliminación — ConstructSys',
        'subject_cancel' => 'Solicitud de eliminación cancelada — ConstructSys',
        'body_request' => 'Recibimos una solicitud para <strong style="color:#f0ede8;">eliminar permanentemente</strong> la cuenta de la empresa',
        'grace_info' => 'Si confirmas, tu cuenta será desactivada y tu base de datos archivada por <strong style="color:#f0ede8;">30 días</strong>, durante los cuales podrás solicitar su recuperación.',
        'btn_confirm' => 'Sí, eliminar mi cuenta',
        'btn_cancel' => 'Cancelar solicitud',
        'expires' => 'Este enlace expira en <strong>24 horas</strong>.',
        'ignore' => 'Si no solicitaste esto, ignora este correo — tu cuenta está segura.',
        'footer' => 'ConstructSys · Sistema de Gestión de Obras',
        'body_confirmed' => 'ha sido desactivada y está programada para eliminación permanente el',
        'recovery_title' => '¿Cambiaste de opinión?',
        'recovery_body' => 'Tienes <strong style="color:#f0ede8;">30 días</strong> para recuperar tu cuenta. Escríbenos antes del',
        'recovery_subject' => 'Recuperar cuenta',
        'permanent_warn' => 'Después de esa fecha los datos se eliminarán permanentemente y no podrán recuperarse.',
        'body_cancel' => 'La solicitud de eliminación de la empresa',
        'body_cancel_end' => 'ha sido <strong style="color:#f0ede8;">cancelada</strong>. Tu cuenta continúa activa sin cambios.',
        'subject_recovered' => 'Cuenta recuperada exitosamente — ConstructSys',
        'body_recovered' => 'ha sido <strong style="color:#22c55e;">recuperada exitosamente</strong>. Ya puedes iniciar sesión normalmente.',
        'recovered_badge' => 'recuperada exitosamente',
        'subject_deletion' => 'Tu cuenta ha sido eliminada — recuperación disponible',
        'body_deletion_start' => 'Tu cuenta',
        'body_deletion_end' => 'ha sido eliminada. Tus datos están respaldados hasta el',
        'body_recovery_cta' => 'Si fue un error, puedes recuperar tu cuenta antes de esa fecha:',
        'btn_recover' => 'Recuperar mi cuenta',
        'expires_recovery_start' => 'Este enlace expira el',
        'expires_recovery_end' => 'Después de esa fecha, los datos se eliminan permanentemente sin posibilidad de recuperación.',
    ],
    'en' => [
        'tagline' => 'Construction Management System',
        'hi' => 'Hello',
        'subject_request' => 'Confirm your account deletion — ConstructSys',
        'subject_confirm' => 'Account scheduled for deletion — ConstructSys',
        'subject_cancel' => 'Deletion request cancelled — ConstructSys',
        'body_request' => 'We received a request to <strong style="color:#f0ede8;">permanently delete</strong> the account of company',
        'grace_info' => 'If you confirm, your account will be deactivated and your database archived for <strong style="color:#f0ede8;">30 days</strong>, during which you can request recovery.',
        'btn_confirm' => 'Yes, delete my account',
        'btn_cancel' => 'Cancel request',
        'expires' => 'This link expires in <strong>24 hours</strong>.',
        'ignore' => 'If you did not request this, ignore this email — your account is safe.',
        'footer' => 'ConstructSys · Construction Management System',
        'body_confirmed' => 'has been deactivated and is scheduled for permanent deletion on',
        'recovery_title' => 'Changed your mind?',
        'recovery_body' => 'You have <strong style="color:#f0ede8;">30 days</strong> to recover your account. Contact us before',
        'recovery_subject' => 'Recover account',
        'permanent_warn' => 'After that date, data will be permanently deleted and cannot be recovered.',
        'body_cancel' => 'The deletion request for company',
        'body_cancel_end' => 'has been <strong style="color:#f0ede8;">cancelled</strong>. Your account remains active.',
        'subject_recovered' => 'Account successfully recovered — ConstructSys',
        'body_recovered' => 'has been <strong style="color:#22c55e;">successfully recovered</strong>. You can now sign in normally.',
        'recovered_badge' => 'successfully recovered',
        'subject_deletion' => 'Your account has been deleted — recovery available',
        'body_deletion_start' => 'Your account',
        'body_deletion_end' => 'has been deleted. Your data is backed up until',
        'body_recovery_cta' => 'If this was a mistake, you can recover your account before that date:',
        'btn_recover' => 'Recover my account',
        'expires_recovery_start' => 'This link expires on',
        'expires_recovery_end'   => 'After that date, data is permanently deleted and cannot be recovered.'
    ],
];

/**
 * Textos para correos electrónicos de restablecimiento de contraseña y cuentas vinculadas a Google, con soporte para múltiples idiomas. 
 * @var array<string, array<string, string>>
 */
const EMAIL_MSSG = [
    'es' => [
        'subject_reset' => 'Restablece tu contraseña — ConstructSys',
        'subject_google' => 'Cuenta vinculada a Google — ConstructSys',
        'hi' => 'Hola',
        'reset_body' => 'Recibimos una solicitud para restablecer la contraseña de tu cuenta.',
        'btn' => 'Restablecer Contraseña',
        'expires' => 'Este enlace expira en <strong style="color:#9e9890;">15 minutos</strong>.',
        'ignore' => 'Si no solicitaste esto, ignora este correo — tu cuenta está segura.',
        'google_body' => 'Tu cuenta de <strong style="color:#f0ede8;">ConstructSys</strong> está vinculada a Google. No tienes contraseña local, por lo que no puedes restablecerla.',
        'google_hint' => 'Por favor usa el botón <strong style="color:#f0ede8;">"Continuar con Google"</strong> en la pantalla de inicio de sesión.',
        'footer' => 'ConstructSys — Sistema de Gestión de Obras',
        'tagline' => 'SISTEMA DE GESTIÓN DE OBRAS',
    ],
    'en' => [
        'subject_reset' => 'Reset your password — ConstructSys',
        'subject_google' => 'Account linked to Google — ConstructSys',
        'hi' => 'Hi',
        'reset_body' => 'We received a request to reset the password for your account.',
        'btn' => 'Reset Password',
        'expires' => 'This link expires in <strong style="color:#9e9890;">15 minutes</strong>.',
        'ignore' => 'If you didn\'t request this, ignore this email — your account is safe.',
        'google_body' => 'Your <strong style="color:#f0ede8;">ConstructSys</strong> account is linked to Google. You don\'t have a local password, so you can\'t reset one.',
        'google_hint' => 'Please use the <strong style="color:#f0ede8;">"Continue with Google"</strong> button on the sign-in screen.',
        'footer' => 'ConstructSys — Construction Management System',
        'tagline' => 'CONSTRUCTION MANAGEMENT SYSTEM',
    ],
];

/**
 * Opciones de PDO requeridas para interacciones seguras y eficientes con la base de datos. 
 * @var array<int, int>
 */
const REQUIRED_PDO_OPTIONS = [PDO::ATTR_ERRMODE, PDO::ATTR_DEFAULT_FETCH_MODE, PDO::ATTR_EMULATE_PREPARES];

/**
 * Claves de esquema requeridas para configuraciones de tablas de base de datos. 
 * @var list<string>
 */
const REQUIRED_SCHEMA_KEYS = ['select', 'insert', 'insert_fields', 'delete'];

/**
 * Reflexión de la clase PDO para inspeccionar sus constantes.
 * @var ReflectionClass
 */
const PDO_REFLECTION = new ReflectionClass(PDO::class);

/**
 * Opciones de PDO predeterminadas para interacciones seguras y eficientes con la base de datos.
 * @var array<int, mixed>
 */
const DEFAULT_PDO_OPTIONS = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,                                                   // Permitir que mySQL maneje declaraciones preparadas para mayor seguridad y rendimiento
]; 

/**
 * Claves requeridas para cada entrada de configuración de base de datos en la constante DATABASES.
 * @var list<string>
 */
const REQUIRED_DB_KEYS = ['host', 'name', 'user', 'password', 'tables', 'options'];

/**
 * Claves de cadena requeridas para cada entrada de configuración de base de datos en la constante DATABASES, excluyendo 'password' que puede ser una cadena vacía.
 * @var list<string>
 */
const REQUIRED_STR_DB_KEYS = ['host', 'name', 'user', 'driver'];

/**
 * Mapa de tipo para deserializar documentos BSON en matrices PHP simples.
 * @var array<string, string>
 */
const TYPE_MAP = [
    'root' => 'array',
    'document' => 'array',
    'array' => 'array',
];

/**
 * Claves requeridas específicas de una entrada de configuración de base de datos de MongoDB.
 * @var list<string>
 */
const REQUIRED_MONGO_KEYS = ['uri', 'collections'];

/**
 * Claves requeridas para cada entrada de configuración de colección de MongoDB.
 * @var list<string>
 */
const REQUIRED_COLLECTION_KEYS = ['collection', 'sort', 'insert_fields', 'delete_field'];

/**
 * Controladores de base de datos compatibles.
 * @var list<string>
 */
const SUPPORTED_DRIVERS = ['mysql', 'mongodb'];

/**
 * Valores válidos para PDO::ATTR_ERRMODE.
 * @var list<int>
 */
const VALID_ERROR_MODES = [
    PDO::ERRMODE_SILENT,
    PDO::ERRMODE_EXCEPTION
];

/**
 * Valores válidos para PDO::ATTR_DEFAULT_FETCH_MODE.
 * @var list<int>
 */
const VALID_FETCH_MODES = [
    PDO::FETCH_ASSOC,
    PDO::FETCH_OBJ,
    PDO::FETCH_BOTH,
    PDO::FETCH_NUM
];

/**
 * Claves de operación SQL requeridas para cada configuración de tabla en una entrada de base de datos MySQL.
 * @var list<string>
 */
const REQUIRED_SQL_KEYS = ['select', 'insert', 'delete'];

/**
 * Algoritmo de firma utilizado para la firma HMAC.
 * @var string
 */
const SIGN_ALGORITHM = 'HS256';

/**
 * Algoritmo de hash pasado a hash_hmac().
 * @var string
 */
const HASH_ALGORITHM = 'sha256';

/**
 * Tiempo de expiración del token en segundos.
 * Predeterminado: 8 horas — adecuado para un turno de trabajo completo.
 * @var int
 */
const TOKEN_EXPIRATION = 60 * 60 * 8;

/**
 * Roles de usuario permitidos para registro y validación.
 * @var list<string>
 */
const ALLOWED_ROLES = ['admin', 'empleado', 'cliente'];

/**
 * Factor de costo de bcrypt para hash de contraseña.
 * 12 es el mínimo recomendado para producción a partir de 2026.
 * @var int
 */
const BCRYPT_COST = 12;

/**
 * Tablas SQL requeridas para la base de datos de la industria de la construcción.
 * @var list<string>
 */
const SQL_TABLES = [
    'clientes',
    'departamentos',
    'empleados',
    'proveedores',
    'subcontratistas',
    'licitaciones',
    'proyectos',
    'hitos_proyecto',
    'nomina',
    'detalle_nomina',
    'capacitaciones',
    'empleado_capacitacion',
    'asignaciones',
    'accidentes_laborales',
    'ausencias',
    'prestamos_empleados',
    'contratos',
    'contratos_subcontratista',
    'garantias',
    'retenciones',
    'bitacora_obra',
    'riesgos_proyecto',
    'incidentes_seguridad',
    'permisos_licencias',
    'documentos_proyecto',
    'almacenes',
    'cotizaciones_proveedor',
    'contactos_proveedor',
    'facturas',
    'pagos',
    'equipos',
    'mantenimiento_equipos',
];

/**
 * Esquema de tablas para cada tenant SQL.
 * @var array<string, array<string, mixed>>
 */
const TENANT_TABLE_SCHEMA = [
    
    'clientes' => [
        'select' => 'SELECT * FROM clientes ORDER BY id_cliente DESC',
        'insert' => 'INSERT INTO clientes (
            tipo_persona, nombre_razon_social, rfc, curp, nombre_contacto, telefono, email, direccion, ciudad, estado, pais, 
            codigo_postal, calificacion, notas, activo, fecha_alta
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'tipo_persona', 'nombre_razon_social', 'rfc', 'curp', 'nombre_contacto', 'telefono', 'email', 'direccion', 'ciudad',
            'estado', 'pais', 'codigo_postal', 'calificacion', 'notas', 'activo', 'fecha_alta'
        ],
        'update' => 'UPDATE clientes SET 
            tipo_persona=?, nombre_razon_social=?, nombre_contacto=?, telefono=?, email=?, direccion=?, ciudad=?, estado=?, pais=?,
            codigo_postal=?, calificacion=?, notas=?, activo=? 
        WHERE id_cliente=?',
        'update_fields' => [
            'tipo_persona', 'nombre_razon_social', 'nombre_contacto', 'telefono', 'email', 'direccion', 'ciudad', 'estado', 'pais',
            'codigo_postal', 'calificacion', 'notas', 'activo'
        ],
        'delete' => 'DELETE FROM clientes WHERE id_cliente = ?',
    ],
    
    'departamentos' => [
        'select' => 'SELECT * FROM departamentos ORDER BY id_departamento DESC',
        'insert' => 'INSERT INTO departamentos (nombre, descripcion, id_responsable, activo) VALUES (?,?,?,?)',
        'insert_fields' => [
            'nombre', 'descripcion', 'id_responsable', 'activo'
        ],
        'update' => 'UPDATE departamentos SET nombre=?, descripcion=?, id_responsable=?, activo=? WHERE id_departamento=?',
        'update_fields' => [
            'nombre', 'descripcion', 'id_responsable', 'activo'
        ],
        'delete' => 'DELETE FROM departamentos WHERE id_departamento = ?',
    ],
    
    'empleados' => [
        'select' => 'SELECT * FROM empleados ORDER BY id_empleado DESC',
        'insert' => 'INSERT INTO empleados (
            numero_empleado, nombre,apellido_paterno, apellido_materno, rfc, curp, nss, fecha_nacimiento, genero, telefono,
            email, direccion, puesto_cargo, id_departamento, id_supervisor, tipo_contrato, fecha_contratacion, fecha_fin_contrato,
            salario_base, banco, clabe_interbancaria, activo
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'numero_empleado', 'nombre', 'apellido_paterno', 'apellido_materno', 'rfc', 'curp', 'nss', 'fecha_nacimiento', 'genero',
            'telefono', 'email', 'direccion', 'puesto_cargo', 'id_departamento', 'id_supervisor', 'tipo_contrato', 'fecha_contratacion',
            'fecha_fin_contrato', 'salario_base', 'banco', 'clabe_interbancaria', 'activo'
        ],
        'update' => 'UPDATE empleados SET 
            nombre=?, apellido_paterno=?, apellido_materno=?, telefono=?, email=?, direccion=?, puesto_cargo=?, id_departamento=?, 
            id_supervisor=?, tipo_contrato=?, fecha_fin_contrato=?, salario_base=?, banco=?, clabe_interbancaria=?, activo=? 
        WHERE id_empleado=?',
        'update_fields' => ['nombre','apellido_paterno','apellido_materno','telefono','email','direccion','puesto_cargo','id_departamento','id_supervisor','tipo_contrato','fecha_fin_contrato','salario_base','banco','clabe_interbancaria','activo'],
        'delete' => 'DELETE FROM empleados WHERE id_empleado = ?',
    ],
    
    'proveedores' => [
        'select' => 'SELECT * FROM proveedores ORDER BY id_proveedor DESC',
        'insert' => 'INSERT INTO proveedores (
            razon_social, rfc, tipo_proveedor, telefono, email, direccion, ciudad, estado,pais, calificacion, cuenta_bancaria, 
            clabe, banco, notas, activo, fecha_alta
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'razon_social', 'rfc', 'tipo_proveedor', 'telefono', 'email', 'direccion', 'ciudad', 'estado', 'pais', 'calificacion',
            'cuenta_bancaria', 'clabe', 'banco', 'notas', 'activo', 'fecha_alta'
        ],
        'update' => 'UPDATE proveedores SET 
            razon_social=?, tipo_proveedor=?, telefono=?, email=?, direccion=?, ciudad=?, estado=?, pais=?, calificacion=?, 
            cuenta_bancaria=?, clabe=?, banco=?, notas=?, activo=? 
        WHERE id_proveedor=?',
        'update_fields' => [
            'razon_social', 'tipo_proveedor', 'telefono', 'email', 'direccion', 'ciudad', 'estado', 'pais', 'calificacion', 
            'cuenta_bancaria', 'clabe', 'banco', 'notas', 'activo'
        ],
        'delete' => 'DELETE FROM proveedores WHERE id_proveedor = ?',
    ],
    
    'subcontratistas' => [
        'select' => 'SELECT * FROM subcontratistas ORDER BY id_subcontratista DESC',
        'insert' => 'INSERT INTO subcontratistas (
            razon_social, rfc, contacto_principal, telefono, email, especialidad, calificacion, cuenta_bancaria, clabe, banco, activo, 
            notas
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'razon_social', 'rfc', 'contacto_principal', 'telefono', 'email', 'especialidad', 'calificacion', 'cuenta_bancaria', 'clabe',
            'banco', 'activo', 'notas'
        ],
        'update' => 'UPDATE subcontratistas SET 
            razon_social=?, contacto_principal=?, telefono=?, email=?, especialidad=?, calificacion=?, cuenta_bancaria=?, clabe=?, banco=?,
            activo=?, notas=? 
        WHERE id_subcontratista=?',
        'update_fields' => [
            'razon_social', 'contacto_principal', 'telefono', 'email', 'especialidad', 'calificacion', 'cuenta_bancaria', 'clabe', 'banco',
            'activo', 'notas'
        ],
        'delete' => 'DELETE FROM subcontratistas WHERE id_subcontratista = ?',
    ],

    'licitaciones' => [
        'select' => 'SELECT * FROM licitaciones ORDER BY id_licitacion DESC',
        'insert' => 'INSERT INTO licitaciones (
            numero_licitacion, nombre, descripcion, id_cliente, tipo, fecha_publicacion, fecha_cierre, fecha_fallo, monto_estimado, 
            monto_ofertado, estado, id_responsable, id_proyecto, observaciones, created_by, updated_by
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'numero_licitacion', 'nombre', 'descripcion', 'id_cliente', 'tipo', 'fecha_publicacion', 'fecha_cierre', 'fecha_fallo',
            'monto_estimado', 'monto_ofertado', 'estado', 'id_responsable', 'id_proyecto', 'observaciones', 'created_by',
            'updated_by'
        ],
        'update' => 'UPDATE licitaciones SET 
            nombre=?, descripcion=?, fecha_cierre=?, fecha_fallo=?, monto_estimado=?, monto_ofertado=?, estado=?, id_responsable=?,
            id_proyecto=?, observaciones=?, updated_by=? 
        WHERE id_licitacion=?',
        'update_fields' => [
            'nombre', 'descripcion', 'fecha_cierre', 'fecha_fallo', 'monto_estimado', 'monto_ofertado', 'estado', 'id_responsable',
            'id_proyecto', 'observaciones', 'updated_by'
        ],
        'delete' => 'DELETE FROM licitaciones WHERE id_licitacion = ?',
    ],

    'proyectos' => [
        'select' => 'SELECT * FROM proyectos ORDER BY id_proyecto DESC',
        'insert' => 'INSERT INTO proyectos (
            codigo_proyecto, nombre_proyecto, descripcion, tipo_proyecto, estado, ubicacion, ciudad, estado_republica, pais, 
            fecha_inicio_estimada, fecha_fin_estimada, fecha_inicio_real, fecha_fin_real, presupuesto_estimado, costo_actual, id_cliente,
            id_director, id_licitacion, porcentaje_avance, created_by, updated_by
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'codigo_proyecto', 'nombre_proyecto', 'descripcion', 'tipo_proyecto', 'estado', 'ubicacion', 'ciudad', 'estado_republica',
            'pais', 'fecha_inicio_estimada', 'fecha_fin_estimada', 'fecha_inicio_real', 'fecha_fin_real', 'presupuesto_estimado', 
            'costo_actual', 'id_cliente', 'id_director', 'id_licitacion', 'porcentaje_avance', 'created_by', 'updated_by'
        ],
        'update' => 'UPDATE proyectos SET 
            nombre_proyecto=?, descripcion=?, tipo_proyecto=?, estado=?, ubicacion=?, ciudad=?, estado_republica=?, pais=?, 
            fecha_inicio_estimada=?, fecha_fin_estimada=?,fecha_inicio_real=?, fecha_fin_real=?, presupuesto_estimado=?, costo_actual=?,
            id_director=?, porcentaje_avance=?, updated_by=? WHERE id_proyecto=?',
        'update_fields' => [
            'nombre_proyecto', 'descripcion', 'tipo_proyecto', 'estado', 'ubicacion', 'ciudad', 'estado_republica', 'pais',
            'fecha_inicio_estimada', 'fecha_fin_estimada', 'fecha_inicio_real', 'fecha_fin_real', 'presupuesto_estimado', 'costo_actual',
            'id_director', 'porcentaje_avance', 'updated_by'
        ],
        'delete' => 'DELETE FROM proyectos WHERE id_proyecto = ?',
    ],

    'hitos_proyecto' => [
        'select' => 'SELECT * FROM hitos_proyecto ORDER BY id_hito DESC',
        'insert' => 'INSERT INTO hitos_proyecto (
            id_proyecto, nombre, descripcion, tipo, fecha_estimada, fecha_real, porcentaje_avance, monto_cobro, id_responsable, estado,
            notas, created_by, updated_by
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'id_proyecto', 'nombre', 'descripcion', 'tipo', 'fecha_estimada', 'fecha_real', 'porcentaje_avance', 'monto_cobro', 
            'id_responsable', 'estado', 'notas', 'created_by', 'updated_by'
        ],
        'update' => 'UPDATE hitos_proyecto SET 
            nombre=?, descripcion=?, fecha_estimada=?, fecha_real=?, porcentaje_avance=?, monto_cobro=?, id_responsable=?, estado=?, 
            notas=?, updated_by=? 
        WHERE id_hito=?',
        'update_fields' => [
            'nombre', 'descripcion', 'fecha_estimada', 'fecha_real', 'porcentaje_avance', 'monto_cobro', 'id_responsable', 'estado',
            'notas', 'updated_by'
        ],
        'delete' => 'DELETE FROM hitos_proyecto WHERE id_hito = ?',
    ],

    'nomina' => [
        'select' => 'SELECT * FROM nomina ORDER BY id_nomina DESC',
        'insert' => 'INSERT INTO nomina (
            periodo, fecha_inicio, fecha_fin, fecha_pago, tipo_nomina, total_percepciones, total_deducciones, total_neto, estado,aprobada,
            aprobada_por, created_by, updated_by
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'periodo', 'fecha_inicio', 'fecha_fin', 'fecha_pago', 'tipo_nomina', 'total_percepciones', 'total_deducciones', 'total_neto',
            'estado', 'aprobada', 'aprobada_por', 'created_by', 'updated_by'
        ],
        'update' => 'UPDATE nomina SET fecha_pago=?, total_percepciones=?, total_deducciones=?, total_neto=?, estado=?, aprobada=?, 
            aprobada_por=?, updated_by=? 
        WHERE id_nomina=?',
        'update_fields' => [
            'fecha_pago', 'total_percepciones', 'total_deducciones', 'total_neto', 'estado', 'aprobada', 'aprobada_por', 'updated_by'
        ],
        'delete' => 'DELETE FROM nomina WHERE id_nomina = ?',
    ],

    'detalle_nomina' => [
        'select' => 'SELECT * FROM detalle_nomina ORDER BY id_detalle_nomina DESC',
        'insert' => 'INSERT INTO detalle_nomina (
            id_nomina, id_empleado, salario_base, horas_extra, monto_horas_extra, bonos, total_percepciones, isr, imss, infonavit, 
            otras_deducciones, total_deducciones, neto_pagar
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'id_nomina', 'id_empleado', 'salario_base', 'horas_extra', 'monto_horas_extra', 'bonos', 'total_percepciones', 'isr', 'imss',
            'infonavit', 'otras_deducciones', 'total_deducciones', 'neto_pagar'
        ],
        'update' => 'UPDATE detalle_nomina SET 
            salario_base=?, horas_extra=?, monto_horas_extra=?, bonos=?, total_percepciones=?, isr=?, infonavit=?, otras_deducciones=?, 
            total_deducciones=?, neto_pagar=? 
        WHERE id_detalle_nomina=?',
        'update_fields' => [
            'salario_base', 'horas_extra', 'monto_horas_extra', 'bonos', 'total_percepciones', 'isr', 'infonavit', 'otras_deducciones', 
            'total_deducciones', 'neto_pagar'
        ],
        'delete' => 'DELETE FROM detalle_nomina WHERE id_detalle_nomina = ?',
    ],

    'capacitaciones' => [
        'select' => 'SELECT * FROM capacitaciones ORDER BY id_capacitacion DESC',
        'insert' => 'INSERT INTO capacitaciones (
            nombre, descripcion, tipo, instructor, duracion_horas, fecha_inicio, fecha_fin, costo, id_proyecto, obligatoria, activo
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'nombre', 'descripcion', 'tipo', 'instructor', 'duracion_horas', 'fecha_inicio', 'fecha_fin', 'costo', 'id_proyecto', 
            'obligatoria', 'activo'
        ],
        'update' => 'UPDATE capacitaciones SET 
            nombre=?, descripcion=?, instructor=?, duracion_horas=?, fecha_inicio=?, fecha_fin=?, costo=?, id_proyecto=?, obligatoria=?, 
            activo=? 
        WHERE id_capacitacion=?',
        'update_fields' => [
            'nombre', 'descripcion', 'instructor', 'duracion_horas', 'fecha_inicio', 'fecha_fin', 'costo', 'id_proyecto', 
            'obligatoria', 'activo'
        ],
        'delete' => 'DELETE FROM capacitaciones WHERE id_capacitacion = ?',
    ],

    'empleado_capacitacion' => [
        'select' => 'SELECT * FROM empleado_capacitacion ORDER BY id_emp_cap DESC',
        'insert' => 'INSERT INTO empleado_capacitacion (
            id_empleado, id_capacitacion, fecha_asistencia, calificacion, aprobado
        ) VALUES (?,?,?,?,?)',
        'insert_fields' => ['id_empleado', 'id_capacitacion', 'fecha_asistencia', 'calificacion', 'aprobado'],
        'update' => 'UPDATE empleado_capacitacion SET fecha_asistencia=?, calificacion=?, aprobado=? WHERE id_emp_cap=?',
        'update_fields' => ['fecha_asistencia', 'calificacion', 'aprobado'],
        'delete' => 'DELETE FROM empleado_capacitacion WHERE id_emp_cap = ?',
    ],

    'asignaciones' => [
        'select' => 'SELECT * FROM asignaciones ORDER BY id_asignacion DESC',
        'insert' => 'INSERT INTO asignaciones (
            id_empleado, id_proyecto, fecha_inicio, fecha_fin, rol_en_proyecto, horas_semanales, activo
        ) VALUES (?,?,?,?,?,?,?)',
        'insert_fields' => ['id_empleado', 'id_proyecto', 'fecha_inicio', 'fecha_fin', 'rol_en_proyecto', 'horas_semanales', 'activo'],
        'update' => 'UPDATE asignaciones SET fecha_fin=?, rol_en_proyecto=?, horas_semanales=?, activo=? WHERE id_asignacion=?',
        'update_fields' => ['fecha_fin', 'rol_en_proyecto', 'horas_semanales', 'activo'],
        'delete' => 'DELETE FROM asignaciones WHERE id_asignacion = ?',
    ],

    'accidentes_laborales' => [
        'select' => 'SELECT * FROM accidentes_laborales ORDER BY id_accidente DESC',
        'insert' => 'INSERT INTO accidentes_laborales (
            id_proyecto, id_empleado, fecha_accidente, tipo_accidente, descripcion, parte_cuerpo, dias_incapacidad,
            requirio_hospitalizacion, folio_imss, acciones_correctivas, costo_estimado, testigos, estado
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'id_proyecto', 'id_empleado', 'fecha_accidente', 'tipo_accidente', 'descripcion', 'parte_cuerpo', 'dias_incapacidad',
            'requirio_hospitalizacion', 'folio_imss', 'acciones_correctivas', 'costo_estimado', 'testigos', 'estado',
        ],
        'update' => 'UPDATE accidentes_laborales SET 
            descripcion=?, parte_cuerpo=?, dias_incapacidad=?, requirio_hospitalizacion=?, folio_imss=?, acciones_correctivas=?, 
            costo_estimado=?, testigos=?, estado=?
        WHERE id_accidente=?',
        'update_fields' => [
            'descripcion', 'parte_cuerpo', 'dias_incapacidad', 'requirio_hospitalizacion', 'folio_imss', 'acciones_correctivas',
            'costo_estimado', 'testigos', 'estado'
        ],
        'delete' => 'DELETE FROM accidentes_laborales WHERE id_accidente = ?',
    ],

    'ausencias' => [
        'select' => 'SELECT * FROM ausencias ORDER BY id_ausencia DESC',
        'insert' => 'INSERT INTO ausencias (
            id_empleado, tipo, fecha_inicio, fecha_fin, dias_habiles, folio_imss, id_proyecto, observaciones, estado, aprobada_por
        ) VALUES (?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'id_empleado', 'tipo', 'fecha_inicio', 'fecha_fin', 'dias_habiles', 'folio_imss', 'id_proyecto', 'observaciones', 'estado', 
            'aprobada_por'
        ],
        'update' => 'UPDATE ausencias SET 
            fecha_fin=?, dias_habiles=?, folio_imss=?, id_proyecto=?, observaciones=?, estado=?, aprobada_por=? 
        WHERE id_ausencia=?',
        'update_fields' => ['fecha_fin', 'dias_habiles', 'folio_imss', 'id_proyecto', 'observaciones', 'estado', 'aprobada_por'],
        'delete' => 'DELETE FROM ausencias WHERE id_ausencia = ?',
    ],

    'prestamos_empleados' => [
        'select' => 'SELECT * FROM prestamos_empleados ORDER BY id_prestamo DESC',
        'insert' => 'INSERT INTO prestamos_empleados (
            id_empleado, tipo, monto_total, monto_pendiente, numero_pagos, monto_descuento_quincenal, fecha_otorgamiento, 
            fecha_liquidacion_est, estado, autorizado_por, notas, created_by, updated_by
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'id_empleado', 'tipo', 'monto_total', 'monto_pendiente', 'numero_pagos', 'monto_descuento_quincenal', 'fecha_otorgamiento',
            'fecha_liquidacion_est', 'estado', 'autorizado_por', 'notas', 'created_by', 'updated_by'
        ],
        'update' => 'UPDATE prestamos_empleados SET 
            monto_pendiente=?, numero_pagos=?, monto_descuento_quincenal=?, fecha_liquidacion_est=?, estado=?, notas=?, updated_by=? 
        WHERE id_prestamo=?',
        'update_fields' => [
            'monto_pendiente', 'numero_pagos', 'monto_descuento_quincenal', 'fecha_liquidacion_est', 'estado', 'notas', 'updated_by'
        ],
        'delete' => 'DELETE FROM prestamos_empleados WHERE id_prestamo = ?',
    ],

    'contratos' => [
        'select' => 'SELECT * FROM contratos ORDER BY id_contrato DESC',
        'insert' => 'INSERT INTO contratos (
            numero_contrato, id_cliente, id_proyecto, id_licitacion, fecha_firma, fecha_inicio, fecha_fin_estimada, fecha_fin_real, 
            monto_total, moneda, tipo_contrato, estado, descripcion_alcance, penalizacion_dia, anticipo_pct, notas, created_by, updated_by
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'numero_contrato', 'id_cliente', 'id_proyecto', 'id_licitacion', 'fecha_firma', 'fecha_inicio', 'fecha_fin_estimada',
            'fecha_fin_real', 'monto_total', 'moneda', 'tipo_contrato', 'estado', 'descripcion_alcance', 'penalizacion_dia',
            'anticipo_pct', 'notas', 'created_by', 'updated_by'
        ],
        'update' => 'UPDATE contratos SET 
            fecha_fin_estimada=?, fecha_fin_real=?, monto_total=?, estado=?, descripcion_alcance=?, penalizacion_dia=?, anticipo_pct=?, 
            notas=?, updated_by=? 
        WHERE id_contrato=?',
        'update_fields' => [
            'fecha_fin_estimada', 'fecha_fin_real', 'monto_total', 'estado', 'descripcion_alcance', 'penalizacion_dia', 'anticipo_pct',
            'notas', 'updated_by'
        ],
        'delete' => 'DELETE FROM contratos WHERE id_contrato = ?',
    ],

    'contratos_subcontratista' => [
        'select' => 'SELECT * FROM contratos_subcontratista ORDER BY id_contrato_sub DESC',
        'insert' => 'INSERT INTO contratos_subcontratista (
            id_subcontratista, id_proyecto, numero_contrato, descripcion_trabajo, fecha_inicio, fecha_fin_est, monto_contratado, 
            estado, created_by, updated_by
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'id_subcontratista', 'id_proyecto', 'numero_contrato', 'descripcion_trabajo', 'fecha_inicio', 'fecha_fin_est', 
            'monto_contratado', 'estado', 'created_by', 'updated_by'
        ],
        'update' => 'UPDATE contratos_subcontratista SET 
            descripcion_trabajo=?, fecha_fin_est=?, monto_contratado=?, estado=?, updated_by=? 
        WHERE id_contrato_sub=?',
        'update_fields' => ['descripcion_trabajo', 'fecha_fin_est', 'monto_contratado', 'estado', 'updated_by'],
        'delete' => 'DELETE FROM contratos_subcontratista WHERE id_contrato_sub = ?',
    ],
 
    'garantias' => [
        'select' => 'SELECT * FROM garantias ORDER BY id_garantia DESC',
        'insert' => 'INSERT INTO garantias (
            id_contrato, id_proyecto, tipo, numero_poliza, aseguradora, monto, porcentaje, fecha_inicio, fecha_vencimiento, estado, 
            notas, created_by, updated_by
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'id_contrato', 'id_proyecto', 'tipo', 'numero_poliza', 'aseguradora', 'monto', 'porcentaje', 'fecha_inicio',
            'fecha_vencimiento', 'estado', 'notas', 'created_by', 'updated_by'
        ],
        'update' => 'UPDATE garantias SET 
            aseguradora=?, monto=?, porcentaje=?, fecha_vencimiento=?, estado=?, notas=?, updated_by=? 
        WHERE id_garantia=?',
        'update_fields' => ['aseguradora', 'monto', 'porcentaje', 'fecha_vencimiento', 'estado', 'notas', 'updated_by'],
        'delete' => 'DELETE FROM garantias WHERE id_garantia = ?',
    ],

    'retenciones' => [
        'select' => 'SELECT * FROM retenciones ORDER BY id_retencion DESC',
        'insert' => 'INSERT INTO retenciones (
            id_contrato, id_factura, tipo, porcentaje, monto, fecha_retencion, fecha_liberacion, monto_liberado, estado, notas, 
            created_by, updated_by
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'id_contrato', 'id_factura', 'tipo', 'porcentaje', 'monto', 'fecha_retencion', 'fecha_liberacion', 'monto_liberado',
            'estado', 'notas', 'created_by', 'updated_by'
        ],
        'update' => 'UPDATE retenciones SET fecha_liberacion=?, monto_liberado=?, estado=?, notas=?, updated_by=? WHERE id_retencion=?',
        'update_fields' => ['fecha_liberacion', 'monto_liberado', 'estado', 'notas', 'updated_by'],
        'delete' => 'DELETE FROM retenciones WHERE id_retencion = ?',
    ],

    'bitacora_obra' => [
        'select' => 'SELECT * FROM bitacora_obra ORDER BY id_bitacora DESC',
        'insert' => 'INSERT INTO bitacora_obra (
            id_proyecto, numero_nota, fecha, tipo_nota, descripcion, id_emitido_por, condiciones_clima, personal_presente, equipos_activos
        ) VALUES (?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'id_proyecto', 'numero_nota', 'fecha', 'tipo_nota', 'descripcion', 'id_emitido_por', 'condiciones_clima', 'personal_presente',
            'equipos_activos'
        ],
        'update' => 'UPDATE bitacora_obra SET 
            descripcion=?, condiciones_clima=?, personal_presente=?, equipos_activos=? 
        WHERE id_bitacora=?',
        'update_fields' => ['descripcion', 'condiciones_clima', 'personal_presente', 'equipos_activos'],
        'delete' => 'DELETE FROM bitacora_obra WHERE id_bitacora = ?',
    ],

    'riesgos_proyecto' => [
        'select' => 'SELECT * FROM riesgos_proyecto ORDER BY id_riesgo DESC',
        'insert' => 'INSERT INTO riesgos_proyecto (
            id_proyecto, descripcion, categoria, probabilidad, impacto, nivel_riesgo, plan_mitigacion, responsable, fecha_identificacion, estado
        ) VALUES (?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'id_proyecto', 'descripcion', 'categoria', 'probabilidad', 'impacto', 'nivel_riesgo', 'plan_mitigacion', 'responsable', 
            'fecha_identificacion', 'estado'
        ],
        'update' => 'UPDATE riesgos_proyecto SET 
            descripcion=?, categoria=?, probabilidad=?, impacto=?, nivel_riesgo=?, plan_mitigacion=?, responsable=?, estado=? 
        WHERE id_riesgo=?',
        'update_fields' => ['descripcion', 'categoria', 'probabilidad', 'impacto', 'nivel_riesgo', 'plan_mitigacion', 'responsable', 'estado'],
        'delete' => 'DELETE FROM riesgos_proyecto WHERE id_riesgo = ?',
    ],

    'incidentes_seguridad' => [
        'select' => 'SELECT * FROM incidentes_seguridad ORDER BY id_incidente DESC',
        'insert' => 'INSERT INTO incidentes_seguridad (
            id_proyecto, id_empleado, fecha_hora, tipo_incidente, descripcion, acciones_tomadas, costo_incidente, estado
        ) VALUES (?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'id_proyecto', 'id_empleado', 'fecha_hora', 'tipo_incidente', 'descripcion', 'acciones_tomadas', 'costo_incidente', 'estado', 
        ],
        'update' => 'UPDATE incidentes_seguridad SET descripcion=?, acciones_tomadas=?, costo_incidente=?, estado=? WHERE id_incidente=?',
        'update_fields' => ['descripcion', 'acciones_tomadas', 'costo_incidente', 'estado'],
        'delete' => 'DELETE FROM incidentes_seguridad WHERE id_incidente = ?',
    ],

    'permisos_licencias' => [
        'select' => 'SELECT * FROM permisos_licencias ORDER BY id_permiso DESC',
        'insert' => 'INSERT INTO permisos_licencias (
            id_proyecto, tipo_permiso, numero_permiso, autoridad_emisora, fecha_emision, fecha_vencimiento, estado, notas
        ) VALUES (?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'id_proyecto', 'tipo_permiso', 'numero_permiso', 'autoridad_emisora', 'fecha_emision', 'fecha_vencimiento', 'estado', 'notas'
        ],
        'update' => 'UPDATE permisos_licencias SET fecha_vencimiento=?, estado=?, notas=? WHERE id_permiso=?',
        'update_fields' => ['fecha_vencimiento', 'estado', 'notas'],
        'delete' => 'DELETE FROM permisos_licencias WHERE id_permiso = ?',
    ],

    'documentos_proyecto' => [
        'select' => 'SELECT * FROM documentos_proyecto ORDER BY id_documento DESC',
        'insert' => 'INSERT INTO documentos_proyecto (
            id_proyecto, tipo_documento, nombre, descripcion, version, subido_por, fecha_subida, activo
        ) VALUES (?,?,?,?,?,?,?,?)',
        'insert_fields' => ['id_proyecto', 'tipo_documento', 'nombre', 'descripcion', 'version', 'subido_por', 'fecha_subida', 'activo'],
        'update' => 'UPDATE documentos_proyecto SET nombre=?, descripcion=?, version=?, activo=? WHERE id_documento=?',
        'update_fields' => ['nombre', 'descripcion', 'version', 'activo'],
        'delete' => 'DELETE FROM documentos_proyecto WHERE id_documento = ?',
    ],

    'almacenes' => [
        'select' => 'SELECT * FROM almacenes ORDER BY id_almacen DESC',
        'insert' => 'INSERT INTO almacenes (nombre, ubicacion, id_proyecto, responsable, activo) VALUES (?,?,?,?,?)',
        'insert_fields' => ['nombre', 'ubicacion', 'id_proyecto', 'responsable', 'activo'],
        'update' => 'UPDATE almacenes SET nombre=?, ubicacion=?, responsable=?, activo=? WHERE id_almacen=?',
        'update_fields' => ['nombre', 'ubicacion', 'responsable', 'activo'],
        'delete' => 'DELETE FROM almacenes WHERE id_almacen = ?',
    ],

    'cotizaciones_proveedor' => [
        'select' => 'SELECT * FROM cotizaciones_proveedor ORDER BY id_cotizacion DESC',
        'insert' => 'INSERT INTO cotizaciones_proveedor (
            numero_cotizacion, id_proveedor, id_proyecto, fecha_solicitud, fecha_vencimiento, estado, total_cotizado, notas
        ) VALUES (?,?,?,?,?,?,?,?)',
        'insert_fields' => ['numero_cotizacion', 'id_proveedor', 'id_proyecto', 'fecha_solicitud', 'fecha_vencimiento', 'estado', 'total_cotizado', 'notas'],
        'update' => 'UPDATE cotizaciones_proveedor SET fecha_vencimiento=?, estado=?, total_cotizado=?, notas=? WHERE id_cotizacion=?',
        'update_fields' => ['fecha_vencimiento', 'estado', 'total_cotizado', 'notas'],
        'delete' => 'DELETE FROM cotizaciones_proveedor WHERE id_cotizacion = ?',
    ],

    'contactos_proveedor' => [
        'select' => 'SELECT * FROM contactos_proveedor ORDER BY id_contacto DESC',
        'insert' => 'INSERT INTO contactos_proveedor (
            id_proveedor, id_subcontratista, nombre, cargo, telefono, email, es_principal, activo
        ) VALUES (?,?,?,?,?,?,?,?)',
        'insert_fields' => ['id_proveedor', 'id_subcontratista', 'nombre', 'cargo', 'telefono', 'email', 'es_principal', 'activo'],
        'update' => 'UPDATE contactos_proveedor SET nombre=?, cargo=?, telefono=?, email=?, es_principal=?, activo=? WHERE id_contacto=?',
        'update_fields' => ['nombre', 'cargo', 'telefono', 'email', 'es_principal', 'activo'],
        'delete' => 'DELETE FROM contactos_proveedor WHERE id_contacto = ?',
    ],

    'facturas' => [
        'select' => 'SELECT * FROM facturas ORDER BY id_factura DESC',
        'insert' => 'INSERT INTO facturas (
            numero_factura, tipo_factura, id_proveedor, id_cliente, id_proyecto, id_orden_compra, id_contrato, id_estimacion, 
            fecha_emision, fecha_vencimiento, subtotal, iva, total, moneda, estado, uuid_sat, notas, created_by, 
            updated_by
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'numero_factura', 'tipo_factura', 'id_proveedor', 'id_cliente', 'id_proyecto', 'id_orden_compra', 'id_contrato', 
            'id_estimacion', 'fecha_emision', 'fecha_vencimiento', 'subtotal', 'iva', 'total', 'moneda', 'estado', 'uuid_sat', 'notas',
            'created_by', 'updated_by'
        ],
        'update' => 'UPDATE facturas SET fecha_vencimiento=?, estado=?, notas=?, updated_by=? WHERE id_factura=?',
        'update_fields' => ['fecha_vencimiento', 'estado', 'notas', 'updated_by'],
        'delete' => 'DELETE FROM facturas WHERE id_factura = ?',
    ],

    'pagos' => [
        'select' => 'SELECT * FROM pagos ORDER BY id_pago DESC',
        'insert' => 'INSERT INTO pagos (
            id_factura, fecha_pago, monto, metodo_pago, referencia_banco, comprobante, registrado_por, created_by, updated_by
        ) VALUES (?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'id_factura', 'fecha_pago', 'monto', 'metodo_pago', 'referencia_banco', 'comprobante', 'registrado_por', 'created_by', 
            'updated_by'
        ],
        'update' => 'UPDATE pagos SET metodo_pago=?, referencia_banco=?, comprobante=?, updated_by=? WHERE id_pago=?',
        'update_fields' => ['metodo_pago', 'referencia_banco', 'comprobante', 'updated_by'],
        'delete' => 'DELETE FROM pagos WHERE id_pago = ?',
    ],

    'equipos' => [
        'select' => 'SELECT * FROM equipos ORDER BY id_equipo DESC',
        'insert' => 'INSERT INTO equipos (
            codigo, nombre, tipo, marca, modelo, numero_serie, anio_fabricacion, id_proyecto, estado, costo_hora, propio_rentado, 
            fecha_adquisicion, valor_adquisicion, proxima_revision, activo
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'codigo', 'nombre', 'tipo', 'marca', 'modelo', 'numero_serie', 'anio_fabricacion', 'id_proyecto', 'estado', 'costo_hora',
            'propio_rentado', 'fecha_adquisicion', 'valor_adquisicion', 'proxima_revision', 'activo'
        ],
        'update' => 'UPDATE equipos SET 
            nombre=?, tipo=?, id_proyecto=?, estado=?, costo_hora=?, propio_rentado=?, proxima_revision=?, activo=? 
        WHERE id_equipo=?',
        'update_fields' => ['nombre', 'tipo', 'id_proyecto', 'estado', 'costo_hora', 'propio_rentado', 'proxima_revision', 'activo'],
        'delete' => 'DELETE FROM equipos WHERE id_equipo = ?',
    ],
 
    'mantenimiento_equipos' => [
        'select' => 'SELECT * FROM mantenimiento_equipos ORDER BY id_mantenimiento DESC',
        'insert' => 'INSERT INTO mantenimiento_equipos (
            id_equipo, tipo_mant, fecha_inicio, fecha_fin, descripcion, costo, id_tecnico, proveedor_serv, estado, observaciones
        ) VALUES (?,?,?,?,?,?,?,?,?,?)',
        'insert_fields' => [
            'id_equipo', 'tipo_mant', 'fecha_inicio', 'fecha_fin', 'descripcion', 'costo', 'id_tecnico', 'proveedor_serv', 'estado', 
            'observaciones'
        ],
        'update' => 'UPDATE mantenimiento_equipos SET 
            fecha_fin=?, descripcion=?, costo=?, id_tecnico=?, proveedor_serv=?, estado=?, observaciones=? 
        WHERE id_mantenimiento=?',
        'update_fields' => ['fecha_fin', 'descripcion', 'costo', 'id_tecnico', 'proveedor_serv', 'estado', 'observaciones'],
        'delete' => 'DELETE FROM mantenimiento_equipos WHERE id_mantenimiento = ?',
    ],

];

/**
 * Esquema de tablas para cada tenant NoSQL.
 * @var array<string, array<string, mixed>>
 */
const TENANT_COLLECTION_SCHEMA = [
    'proveedores' => [
        'collection' => 'proveedores',
        'sort' => ['_id' => -1],
        'insert_fields' => ['nombre','contacto','activo','notas'],
        'update_fields' => ['nombre','contacto','activo','notas'],
        'delete_field' => '_id',
    ],
    'herramientas' => [
        'collection'  => 'herramientas',
        'sort' => ['_id' => -1],
        'insert_fields' => ['nombre','tipo','marca','modelo','descripcion','precio','unidad_medida','proveedor_id','activo'],
        'update_fields' => ['nombre','tipo','marca','modelo','descripcion','precio','unidad_medida','proveedor_id','activo'],
        'delete_field' => '_id',
    ],
    'materiales' => [
        'collection' => 'materiales',
        'sort' => ['_id' => -1],
        'insert_fields' => ['nombre','tipo','descripcion','precio_unitario','unidad_medida','proveedor_id','activo'],
        'update_fields' => ['nombre','tipo','descripcion','precio_unitario','unidad_medida','proveedor_id','activo'],
        'delete_field' => '_id',
    ],
    'maquinaria' => [
        'collection' => 'maquinaria',
        'sort' => ['_id' => -1],
        'insert_fields' => ['nombre','tipo','marca','modelo','descripcion','precio','unidad_medida','proveedor_id','activo'],
        'update_fields' => ['nombre','tipo','marca','modelo','descripcion','precio','unidad_medida','proveedor_id','activo'],
        'delete_field' => '_id',
    ],

];
 
/**
 * Bases de Datos Globales usadas para la plataforma interna.
 * @var array<string, array<string, mixed>>
 */
function getDatabasesGlobal(): array { 
   return [ 'platform' => [
        'driver' => 'mysql',
        'host'     => INTERNAL_DB_HOST,  // ya viene del .env
        'name'     => INTERNAL_DB_NAME,  // ya viene del .env
        'user'     => INTERNAL_DB_USER,  // ya viene del .env
        'password' => INTERNAL_DB_PASS,  // ya viene del .env
        'tables' => [
            'users' => [
                'select' => 'SELECT id_usuario, nombre, apellido, email, activo, created_at FROM usuarios ORDER BY id_usuario DESC',
                'insert' => 'INSERT INTO usuarios (nombre, apellido, email, password_hash, activo) VALUES (?,?,?,?,?)',
                'insert_fields' => ['nombre', 'apellido', 'email', 'password_hash', 'activo'],
                'delete' => 'DELETE FROM usuarios WHERE id_usuario = ?'
            ],
            'companys' => [
                'select' => 'SELECT id_empresa, nombre, db_name, activo, created_at FROM empresas ORDER BY id_empresa DESC',
                'insert' => 'INSERT INTO empresas (nombre, db_name, activo) VALUES (?,?,?)', 
                'insert_fields' => ['nombre', 'db_name', 'activo'],
                'delete' => 'DELETE FROM empresas WHERE id_empresa = ?'
            ],
        ],
        'options' => DEFAULT_PDO_OPTIONS,
    ],
   ];
}


?>
