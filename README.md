
# 🏗️ ConstructSys — Sistema de Gestión para Empresas Constructoras

![PHP](https://img.shields.io/badge/PHP-8.2.12-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-BDR-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![MongoDB](https://img.shields.io/badge/MongoDB-BDNR-47A248?style=for-the-badge&logo=mongodb&logoColor=white)
![OAuth](https://img.shields.io/badge/OAuth-Google-4285F4?style=for-the-badge&logo=google&logoColor=white)
![Version](https://img.shields.io/badge/Versión-1.0-orange?style=for-the-badge)
![License](https://img.shields.io/badge/Licencia-MIT-green?style=for-the-badge)
![Status](https://img.shields.io/badge/Estado-En_Desarrollo-yellow?style=for-the-badge)

Plataforma multi-tenant de gestión integral para empresas de la industria de la construcción. Centraliza en un único sistema la administración de proyectos, licitaciones, contratos, recursos humanos, nómina, proveedores, inventario y una tienda en línea para la comercialización de materiales, herramientas y maquinaria.

---

## 👨‍💻 Información del Desarrollador

| Campo | Detalle |
|-------|---------|
| **Desarrollador** | Magallanes López Carlos Gabriel |
| **Correo** | cgmagallanes23@gmail.com |
| **Versión** | 1.0 |
| **Inicio del proyecto** | Mayo 2026 |

---

## 🎯 Descripción

**ConstructSys** es una plataforma web diseñada para resolver la fragmentación operativa de las empresas constructoras, que habitualmente gestionan sus procesos en herramientas desconectadas entre sí. El sistema unifica en una sola interfaz todos los flujos críticos del negocio: desde el seguimiento de obras y la firma de contratos hasta el pago de nómina y la venta de materiales.

La arquitectura **multi-tenant** garantiza que cada empresa registrada opere con total aislamiento de datos: sus bases de datos MySQL y MongoDB son exclusivas, sin compartir información con otras organizaciones en la plataforma.

---

## 🗂️ Estructura del Proyecto

```
ConstructIndustry/
├── api/
│   ├── auth/
│   │   ├── google/
│   │   │   ├── redirect.php        # Inicia el flujo OAuth con Google
│   │   │   └── callback.php        # Maneja el retorno de Google
│   │   ├── jwt.php                 # Generación y validación de tokens JWT
│   │   ├── login.php               # Endpoint POST de autenticación
│   │   ├── register.php            # Endpoint POST de registro de empresa
│   │   └── service.php             # AuthService: lógica de auth
│   ├── controllers/
│   │   ├── database.php            # Clase Database (PDO/MySQL)
│   │   ├── mongodb.php             # Clase MongoDatabase
│   │   ├── router.php              # Router HTTP → controlador correcto
│   │   └── dbrecord.php            # Registro dinámico de tenants
│   ├── settings/
│   │   ├── constants.php           # Constantes, schemas y configuración global
│   │   ├── env.php                 # Carga del archivo .env
│   │   ├── headers.php             # Headers CORS y Content-Type
│   │   ├── middleware.php          # Rate limiting y validación de clave interna
│   │   └── internal_controllers.php# Instancias globales reutilizables
│   ├── utils/
│   │   ├── helpers.php             # curlPost, curlGet, getEnvValue
│   │   └── validators.php          # Validadores de schema y PDO options
│   └── index.php                   # Punto de entrada principal del API
├── scripts/
│   ├── index.js                    # Lógica de login y OAuth
│   ├── dashboard.js                # Lógica del panel de administración
│   └── store.js                    # Lógica de la tienda en línea
├── styles/
│   ├── index.css                   # Estilos del login y registro
│   ├── dashboard.css               # Estilos del panel
│   └── store.css                   # Estilos de la tienda
├── views/
│   ├── dashboard.html              # Panel de administración
│   ├── store.html                  # Tienda en línea (ConstructStore)
│   └── register.html              # Formulario de registro de empresa
├── vendor/                         # Dependencias Composer (MongoDB driver)
├── .env                            # Variables de entorno (no en repositorio)
├── .env.example                    # Plantilla de variables de entorno
├── .gitignore
├── composer.json
└── index.html                      # Vista de login
```

---

## ✨ Módulos del Sistema

### 🏗️ Gestión de Obras y Proyectos
| Módulo | Descripción |
|--------|-------------|
| **Proyectos** | Registro completo con código, tipo, estado, presupuesto y avance porcentual |
| **Hitos** | Entregables medibles por proyecto con fechas y montos de cobro |
| **Bitácora de Obra** | Notas diarias con condiciones climáticas, personal y equipos |
| **Documentos** | Versionado de planos, contratos y reportes por proyecto |
| **Riesgos** | Matriz de riesgos con probabilidad, impacto y plan de mitigación |
| **Permisos y Licencias** | Seguimiento de autorizaciones gubernamentales con fechas de vencimiento |

### 📑 Licitaciones y Contratos
| Módulo | Descripción |
|--------|-------------|
| **Licitaciones** | Ciclo completo desde publicación hasta fallo con montos estimados y ofertados |
| **Contratos** | Vinculación cliente-proyecto con alcance, penalizaciones y anticipo |
| **Contratos con Subcontratistas** | Gestión de trabajos especializados tercerizados |
| **Garantías** | Fianzas y seguros con número de póliza y fechas de vencimiento |
| **Retenciones** | Control de retenciones económicas por contrato y factura |
| **Estimaciones** | Cobro progresivo por avance de obra |

### 👥 Recursos Humanos y Nómina
| Módulo | Descripción |
|--------|-------------|
| **Empleados** | Expediente completo con datos fiscales, bancarios y laborales |
| **Departamentos** | Estructura organizacional con responsable por área |
| **Asignaciones** | Asignación de empleados a proyectos con rol y horas semanales |
| **Nómina** | Cálculo de percepciones (salario, horas extra, bonos) y deducciones (ISR, IMSS, INFONAVIT) |
| **Ausencias y Vacaciones** | Flujo de solicitud y aprobación con folio IMSS |
| **Capacitaciones** | Registro de cursos con asistencia, calificación y certificado |
| **Accidentes Laborales** | Reporte con folio IMSS, días de incapacidad y acciones correctivas |
| **Préstamos** | Tabla de amortización con descuento quincenal automático |

### 💼 Clientes, Proveedores y Finanzas
| Módulo | Descripción |
|--------|-------------|
| **Clientes** | Personas físicas y morales con datos fiscales y límite de crédito |
| **Proveedores** | Datos fiscales, bancarios, calificación y cotizaciones |
| **Subcontratistas** | Empresas especializadas con historial de contratos |
| **Contactos** | Directorio de contactos por proveedor y subcontratista |
| **Facturas** | Facturas de entrada y salida con UUID SAT y estado de pago |
| **Pagos** | Registro de pagos con método, referencia bancaria y comprobante |
| **Cotizaciones** | Solicitudes de cotización por proveedor y proyecto |

### 🏭 Equipos e Infraestructura
| Módulo | Descripción |
|--------|-------------|
| **Equipos** | Maquinaria propia y rentada con próxima revisión y valor de adquisición |
| **Mantenimiento** | Historial de servicios con costo y proveedor externo |
| **Almacenes** | Ubicaciones de almacenamiento vinculadas a proyectos |

### 🛒 ConstructStore — Tienda en Línea
Catálogo de e-commerce para la venta de materiales, herramientas y maquinaria del inventario MongoDB. Incluye filtros por categoría, ordenamiento por precio, carrito con cálculo en tiempo real y solicitud de cotización formal.

---

## 🏛️ Arquitectura

### Modelo Multi-Tenant

```
intern_platform (MySQL)
├── usuarios        → autenticación y roles
└── empresas        → registro de tenants

Por cada empresa registrada:
├── tenant_{slug}           → MySQL con todas las tablas operativas
└── tenant_{slug}_inv       → MongoDB con colecciones de inventario
```

### Flujo de una Solicitud API

```
Frontend
    ↓  fetch("api/index.php?module=tenant_alpha_proyectos")
    ↓  headers: { Authorization: Bearer JWT, X-Internal-Key: ... }

index.php
    ↓  Valida JWT + clave interna
    ↓  DBRecord lee empresas activas de intern_platform
    ↓  Construye mapa dinámico de módulos por tenant

Router
    ↓  Resuelve "tenant_alpha_proyectos" → tenant_alpha (MySQL)
    ↓  Instancia Database o MongoDatabase según driver

Database / MongoDatabase
    ↓  handleGet / handlePost / handleUpdate / handleDelete
    ↓  Ejecuta SQL o operación MongoDB

JSON Response ← Frontend
```

### Sistema de Autenticación

```
Login tradicional:
  email + password → bcrypt verify → JWT HS256 (8h)

Google OAuth 2.0:
  redirect.php → Google → callback.php → JWT HS256 (8h)

JWT Payload:
  { sub, email, rol, name, db, iat, exp }
         ↑
         db_name del tenant → el frontend construye los módulos
```

---

## 🔐 Seguridad

| Mecanismo | Implementación |
|-----------|----------------|
| **Contraseñas** | bcrypt con cost factor 12 |
| **Sesiones** | JWT HS256 firmado con secreto de al menos 32 caracteres |
| **OAuth** | Google OAuth 2.0 server-side con protección CSRF (state token) |
| **Rate Limiting** | Máximo 5 intentos por IP por minuto en login |
| **Clave Interna** | Header `X-Internal-Key` validado con `hash_equals` |
| **Aislamiento de Datos** | Base de datos dedicada por empresa (multi-tenant) |
| **Variables de Entorno** | Secretos en `.env`, nunca hardcodeados ni en repositorio |

---

## 🛠️ Stack Tecnológico

| Capa | Tecnología | Versión |
|------|-----------|---------|
| **Backend** | PHP (sin framework) | 8.2 |
| **Base de datos relacional** | MySQL vía PDO | 8.0 |
| **Base de datos documental** | MongoDB vía librería oficial PHP | 7.0 |
| **Servidor local** | XAMPP (Apache) | 2.4 |
| **Frontend** | HTML5 + CSS3 + JavaScript vanilla | ES2024 |
| **Autenticación** | JWT HS256 + bcrypt + OAuth 2.0 | — |
| **Driver MongoDB** | mongodb/mongodb (Composer) | — |
| **Fuentes** | Google Fonts (Barlow, Playfair Display, Bebas Neue) | — |

---

## ⚙️ Instalación y Configuración

### Requisitos Previos

| Componente | Versión Mínima |
|------------|---------------|
| XAMPP | 8.2+ |
| PHP | 8.2 |
| MySQL | 8.0 |
| MongoDB | 7.0 |
| Composer | 2.x |
| Extensión PHP MongoDB | `ext-mongodb` |

### 1. Clonar el Repositorio

```bash
git clone https://github.com/tu-usuario/ConstructIndustry.git
cd C:/xampp/htdocs/ConstructIndustry
```

### 2. Instalar Dependencias

```bash
composer install
```

### 3. Configurar Variables de Entorno

```bash
cp .env.example .env
```

Editar `.env` con tus valores:

```env
# Base de datos central
DB_HOST=localhost
DB_NAME=intern_platform
DB_USER=root
DB_PASS=

# JWT
JWT_SECRET=TuSecretoDeAlMenos32Caracteres!

# Google OAuth
GOOGLE_CLIENT_ID=TU_CLIENT_ID.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=TU_CLIENT_SECRET
GOOGLE_REDIRECT_URI=http://localhost/ConstructIndustry/api/auth/google/callback.php

# MongoDB
MONGO_URI=mongodb://localhost:27017
INTERNAL_MONGO_DB=inventario_db

# Seguridad
INTERNAL_API_KEY=TuClaveInternaSegura2026
```

### 4. Crear la Base de Datos Central

Ejecutar en phpMyAdmin o MySQL CLI:

```sql
CREATE DATABASE IF NOT EXISTS `intern_platform`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `intern_platform`;

CREATE TABLE `empresas` (
    `id_empresa`    INT          NOT NULL AUTO_INCREMENT,
    `nombre`        VARCHAR(100) NOT NULL,
    `db_name`       VARCHAR(64)  NOT NULL UNIQUE,
    `mongo_db_name` VARCHAR(64)  NOT NULL UNIQUE,
    `activo`        TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_empresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `usuarios` (
    `id_usuario`    INT          NOT NULL AUTO_INCREMENT,
    `nombre`        VARCHAR(80)  NOT NULL,
    `apellido`      VARCHAR(80)  NOT NULL,
    `email`         VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL DEFAULT '',
    `rol`           ENUM('admin','empleado','cliente') NOT NULL DEFAULT 'empleado',
    `activo`        TINYINT(1)   NOT NULL DEFAULT 1,
    `id_empresa`    INT          NULL DEFAULT NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_usuario`),
    CONSTRAINT `fk_usuario_empresa`
        FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 5. Configurar Google OAuth (Opcional)

1. Ir a [console.cloud.google.com](https://console.cloud.google.com)
2. Crear proyecto → **APIs & Services → Credentials → OAuth Client ID**
3. Tipo: **Web application**
4. Authorized redirect URI:
   ```
   http://localhost/ConstructIndustry/api/auth/google/callback.php
   ```
5. Copiar Client ID y Secret al `.env`

### 6. Acceder al Sistema

```
http://localhost/ConstructIndustry/
```

---

## 🔄 Flujo de Registro de Nueva Empresa

```
Usuario completa formulario
    ↓  nombre_empresa + nombre + apellido + email + password

register.php
    ↓  DBRecord.createCompany("Constructora Alpha")
    ├─→ CREATE DATABASE tenant_constructora_alpha (MySQL)
    ├─→ Copia estructura de tablas desde schema base
    ├─→ Crea base de datos tenant_constructora_alpha_inv (MongoDB)
    ├─→ Inicializa colecciones: materiales, herramientas, maquinaria, proveedores
    └─→ INSERT INTO empresas

    ↓  AuthService.register(nombre, apellido, email, password, 'admin')
    ↓  UPDATE usuarios SET id_empresa = ?
    ↓  JWT generado

Frontend recibe token → localStorage → dashboard
```

---

## 🌐 API Reference

Todas las rutas pasan por `api/index.php` con el parámetro `?module=`.

### Autenticación

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `api/auth/login.php` | Login con email y contraseña |
| `POST` | `api/auth/register.php` | Registro de nueva empresa |
| `GET`  | `api/auth/google/redirect.php` | Inicia flujo OAuth con Google |
| `GET`  | `api/auth/google/callback.php` | Callback de Google OAuth |

### Datos del Tenant

| Método | Parámetro | Descripción |
|--------|-----------|-------------|
| `GET`  | `?module={db}_{tabla}` | Obtener todos los registros |
| `POST` | `?module={db}_{tabla}` | Insertar nuevo registro |
| `PUT`  | `?module={db}_{tabla}&id={id}` | Actualizar registro existente |
| `DELETE` | `?module={db}_{tabla}&id={id}` | Eliminar registro |

**Headers requeridos:**
```
Authorization: Bearer {jwt_token}
X-Internal-Key: {internal_api_key}
Content-Type: application/json
```

---

## 📊 Bases de Datos

### `intern_platform` — Central de Identidad

```
intern_platform
├── usuarios     (id_usuario, nombre, apellido, email, password_hash, rol, activo, id_empresa)
└── empresas     (id_empresa, nombre, db_name, mongo_db_name, activo)
```

### Por Tenant — MySQL

32 tablas operativas incluyendo: `proyectos`, `contratos`, `licitaciones`, `hitos_proyecto`, `bitacora_obra`, `empleados`, `departamentos`, `nomina`, `detalle_nomina`, `facturas`, `pagos`, `clientes`, `proveedores`, `equipos`, y más.

### Por Tenant — MongoDB

```
tenant_{slug}_inv
├── materiales       (cementantes, áridos, bloques, acero, madera, etc.)
├── herramientas     (eléctricas, manuales, de medición)
├── maquinaria       (excavadoras, grúas, compactadoras)
└── proveedores      (proveedores de inventario)
```

---

## 🔮 Funcionalidades en Desarrollo

- [ ] Panel de estadísticas con gráficas en tiempo real
- [ ] Módulo de reportes PDF exportables
- [ ] Sistema de notificaciones por vencimiento de contratos y permisos
- [ ] Módulo de estimaciones de obra para cobro progresivo
- [ ] Vista de kanban para seguimiento de proyectos
- [ ] App móvil para captura desde obra
- [ ] Integración con SAT para timbrado de facturas

---

## 📄 Licencia

Este proyecto está bajo la [Licencia MIT](LICENSE).

---

## 📧 Contacto

**Magallanes López Carlos Gabriel** — cgmagallanes23@gmail.com
