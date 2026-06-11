
/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/*                                                          Scripts — store_admin.html                                                                                       */
/*                                                                                                                                                                           */
/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/* Autor: Magallanes López Carlos Gabriel                                                                                                                                    */
/* Versión del Proyecto: 1.0                                                                                                                                                 */
/* Correo: cgmagallanes23@gmail.com                                                                                                                                          */
/* Ultima Modificación: 10/06/2026                                                                                                                                           */
/*                                                                                                                                                                           */
/*****************************************************************************************************************************************************************************/

const cs_user  = JSON.parse(localStorage.getItem('cs_user') || '{}');
const cs_token = localStorage.getItem('cs_token') || '';
const DB       = cs_user.db_name || null;

const API_BASE = '../api/index.php';

const AUTH_HEADERS = {
    'Authorization': `Bearer ${cs_token}`,
    'X-Internal-Key': 'ConstructSys_Internal_2026_!xK9',
};

/* Estado de registros */
let allRecords    = [];
let filteredRecs  = [];
let sortCol       = 'nombre';
let sortDir       = 'asc';
let currentPage   = 1;
const PAGE_SIZE   = 20;

/*****************************************************************************************************************************************************************************/

async function doLogin() {
    const input = document.getElementById('auth-password');
    const error = document.getElementById('auth-error');
    const btn   = document.getElementById('auth-btn');
    const pass  = input.value.trim();

    if (!pass) {
        input.classList.add('error');
        error.textContent = 'Ingresa tu contraseña de cuenta.';
        error.classList.add('show');
        input.focus();
        return;
    }

    // Necesita el email del usuario guardado en localStorage
    const email = cs_user.email ?? '';
    if (!email) {
        error.textContent = 'No se encontró sesión activa. Inicia sesión desde el dashboard.';
        error.classList.add('show');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = `
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
             style="width:15px;height:15px;animation:spin .7s linear infinite">
            <path d="M21 12a9 9 0 11-6.219-8.56"/>
        </svg>
        Verificando…`;

    try {
        const res  = await fetch('../api/auth/login.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ email, password: pass }),
        });
        const data = await res.json();

        if (data.success) {
            input.classList.remove('error');
            error.classList.remove('show');
            document.getElementById('auth-screen').style.display = 'none';
            document.getElementById('admin-app').classList.add('visible');
            if (cs_user.nombre) {
                document.getElementById('topbar-username').textContent = cs_user.nombre;
            }
        } else {
            input.classList.add('error');
            error.textContent = 'Contraseña incorrecta.';
            error.classList.add('show');
            input.value = '';
            input.focus();
        }

    } catch {
        error.textContent = 'Error de conexión. Intenta de nuevo.';
        error.classList.add('show');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                 style="width:15px;height:15px">
                <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/>
                <polyline points="10 17 15 12 10 7"/>
                <line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            Entrar al Panel`;
    }
}

function doLogout() {
    document.getElementById('admin-app').classList.remove('visible');
    document.getElementById('auth-screen').style.display = 'flex';
    document.getElementById('auth-password').value = '';
    document.getElementById('auth-error').classList.remove('show');
    allRecords = [];
    filteredRecs = [];
    renderRecordsTable();
}

function onAuthKey(e) {
    if (e.key === 'Enter') doLogin();
}

function toggleAuthVis() {
    const input  = document.getElementById('auth-password');
    const isPass = input.type === 'password';
    input.type   = isPass ? 'text' : 'password';
    document.getElementById('vis-icon-off').style.display = isPass ? 'none'  : '';
    document.getElementById('vis-icon-on').style.display  = isPass ? ''      : 'none';
}

/* Inyección de keyframe para el spinner del botón */
(function () {
    const s = document.createElement('style');
    s.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
    document.head.appendChild(s);
})();

/*****************************************************************************************************************************************************************************/

/* ─── NAVEGACIÓN DE VISTAS ────────────────────────────────────────────────── */
function switchView(btn) {
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.admin-view').forEach(v => v.classList.remove('active'));
    btn.classList.add('active');
    const view = document.getElementById('view-' + btn.dataset.view);
    if (view) {
        view.classList.add('active');
        if (btn.dataset.view === 'registros') fetchRecords();
    }
}

/* ─── NAVEGACIÓN DE FORMULARIOS ───────────────────────────────────────────── */
function switchForm(btn) {
    document.querySelectorAll('.ftab-inner').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.aform').forEach(f => f.classList.remove('active'));
    btn.classList.add('active');
    const form = document.getElementById('aform-' + btn.dataset.form);
    if (form) form.classList.add('active');
}

/*****************************************************************************************************************************************************************************/

/* ─── FORMULARIOS ─────────────────────────────────────────────────────────── */
function adminToggle(el) {
    const isOn = el.classList.toggle('active');
    el.querySelector('span').textContent = isOn ? 'Activo' : 'Inactivo';
    el.querySelector('input[type=hidden]').value = isOn ? 'true' : 'false';
}

function adminReset(form) {
    form.reset();
    form.querySelectorAll('.atoggle').forEach(tog => {
        tog.classList.add('active');
        tog.querySelector('span').textContent = 'Activo';
        tog.querySelector('input[type=hidden]').value = 'true';
    });
    toast('Formulario limpiado', null, 'Limpiar');
}

async function adminSubmit(e, coleccion) {
    e.preventDefault();
    const form = e.target;
    const btn  = form.querySelector('.abtn-primary');
    const data = Object.fromEntries(new FormData(form).entries());

    // Convertir tipos
    if (data.precio_unitario !== undefined) data.precio_unitario = parseFloat(data.precio_unitario);
    if (data.precio          !== undefined) data.precio          = parseFloat(data.precio);
    data.activo = data.activo === 'true';

    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = 'Registrando…';

    try {
        const res = await fetch('../api/store/admin_insert.php', {
            method:  'POST',
            headers: {
                'Content-Type':  'application/json',
                'Authorization': `Bearer ${cs_token}`,
            },
            body: JSON.stringify({ coleccion, data }),
        });

        const result = await res.json();

        if (result.success) {
            toast('ok', `${escHtml(data.nombre ?? coleccion)} registrado correctamente.`, 'Registro exitoso');
            adminReset(form);
        } else {
            toast('err', escHtml(result.error ?? 'Error desconocido.'), 'Error al registrar');
        }

    } catch (err) {
        toast('err', escHtml(err.message), 'Error de conexión');
    } finally {
        btn.disabled = false;
        btn.textContent = originalText;
    }
    return false;
}

/*****************************************************************************************************************************************************************************/

async function fetchRecords() {
    const tbody  = document.getElementById('records-tbody');
    const empty  = document.getElementById('records-empty');
    const pg     = document.getElementById('records-pagination');

    // Skeleton loader
    tbody.innerHTML = Array.from({ length: 5 }, () =>
        `<tr>${Array.from({ length: 6 }, () =>
            `<td><div style="height:12px;border-radius:4px;background:var(--surface3);
             animation:shimmer 1.5s infinite;width:${60 + Math.random() * 30 | 0}%"></div></td>`
        ).join('')}</tr>`
    ).join('');
    empty.style.display = 'none';
    pg.style.display    = 'none';

    try {
        const catFilter = document.getElementById('records-cat-filter').value;
        const modulos   = catFilter
            ? [catFilter]
            : ['materiales', 'herramientas', 'maquinaria', 'proveedores'];

        const results = await Promise.all(modulos.map(async mod => {
            const res = await fetch(
                `../api/index.php?module=${encodeURIComponent(DB + '_' + mod)}`,
                { headers: { ...AUTH_HEADERS } }
            );
            if (!res.ok) return [];
            const data = await res.json().catch(() => []);
            return Array.isArray(data)
                ? data.map(r => ({ ...r, _coleccion: mod }))
                : [];
        }));

        allRecords  = results.flat();
        currentPage = 1;
        filterRecordsTable();

    } catch (err) {
        tbody.innerHTML     = '';
        empty.style.display = 'flex';
        document.getElementById('records-empty-msg').textContent =
            `No se pudieron cargar los registros: ${err.message}`;
    }
}

function filterRecordsTable() {
    const q   = (document.getElementById('records-search-input').value || '').toLowerCase().trim();
    const cat = document.getElementById('records-cat-filter').value;

    filteredRecs = allRecords.filter(r => {
        const matchCat = !cat || r._coleccion === cat;
        const haystack = [r.nombre, r.tipo, r.marca, r._coleccion]
            .filter(Boolean).join(' ').toLowerCase();
        return matchCat && (!q || haystack.includes(q));
    });

    sortRecordsData();
}

function sortRecords(col) {
    if (sortCol === col) {
        sortDir = sortDir === 'asc' ? 'desc' : 'asc';
    } else {
        sortCol = col;
        sortDir = 'asc';
    }

    document.querySelectorAll('.records-table thead th').forEach(th => {
        const isSorted = th.dataset.col === col;
        th.classList.toggle('sorted', isSorted);
        const icon = th.querySelector('.sort-icon');
        if (icon) icon.textContent = isSorted ? (sortDir === 'asc' ? '↑' : '↓') : '↕';
    });

    sortRecordsData();
}

function sortRecordsData() {
    filteredRecs.sort((a, b) => {
        let va = a[sortCol] ?? '';
        let vb = b[sortCol] ?? '';
        if (sortCol === 'precio' || sortCol === 'precio_unitario') {
            va = parseFloat(va) || 0;
            vb = parseFloat(vb) || 0;
            return sortDir === 'asc' ? va - vb : vb - va;
        }
        va = String(va).toLowerCase();
        vb = String(vb).toLowerCase();
        return sortDir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
    });

    currentPage = 1;
    renderRecordsTable();
}

function renderRecordsTable() {
    const tbody  = document.getElementById('records-tbody');
    const empty  = document.getElementById('records-empty');
    const pg     = document.getElementById('records-pagination');
    const pgInfo = document.getElementById('pg-info');
    const pgBtns = document.getElementById('pg-btns');

    if (!filteredRecs.length) {
        tbody.innerHTML     = '';
        empty.style.display = 'flex';
        pg.style.display    = 'none';
        document.getElementById('records-empty-msg').textContent =
            allRecords.length ? 'Sin coincidencias para el filtro aplicado.' : 'No hay registros. Haz clic en "Actualizar" para cargar los datos.';
        return;
    }

    empty.style.display = 'none';

    const totalPages = Math.ceil(filteredRecs.length / PAGE_SIZE);
    currentPage      = Math.min(currentPage, totalPages);
    const start      = (currentPage - 1) * PAGE_SIZE;
    const slice      = filteredRecs.slice(start, start + PAGE_SIZE);

    tbody.innerHTML = slice.map(r => {
        const precio    = r.precio_unitario ?? r.precio ?? null;
        const precioStr = precio !== null
            ? new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(precio)
            : '—';
        const activo    = r.activo !== false;
        const marca     = r.marca ?? '—';

        return `
        <tr>
            <td class="td-name">${escHtml(r.nombre ?? '—')}</td>
            <td class="td-cat">${escHtml(r._coleccion ?? '—')}${r.tipo ? ` · ${escHtml(r.tipo)}` : ''}</td>
            <td>${escHtml(marca)}</td>
            <td class="td-price">${precioStr}</td>
            <td>
                <span class="status-chip ${activo ? 'activo' : 'inactivo'}">
                    <span class="dot"></span>
                    ${activo ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td class="td-id">${escHtml(String(r._id ?? '—').slice(-8))}</td>
        </tr>`;
    }).join('');

    /* Paginación */
    pgInfo.textContent = `${start + 1}–${Math.min(start + PAGE_SIZE, filteredRecs.length)} de ${filteredRecs.length}`;

    pgBtns.innerHTML = '';
    const prevBtn = document.createElement('button');
    prevBtn.className = 'pg-btn';
    prevBtn.textContent = '←';
    prevBtn.disabled    = currentPage === 1;
    prevBtn.onclick     = () => { currentPage--; renderRecordsTable(); };
    pgBtns.appendChild(prevBtn);

    const maxVisible = 5;
    let pgStart = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let pgEnd   = Math.min(totalPages, pgStart + maxVisible - 1);
    if (pgEnd - pgStart < maxVisible - 1) pgStart = Math.max(1, pgEnd - maxVisible + 1);

    for (let i = pgStart; i <= pgEnd; i++) {
        const b = document.createElement('button');
        b.className   = 'pg-btn' + (i === currentPage ? ' active' : '');
        b.textContent = i;
        const page    = i;
        b.onclick     = () => { currentPage = page; renderRecordsTable(); };
        pgBtns.appendChild(b);
    }

    const nextBtn = document.createElement('button');
    nextBtn.className = 'pg-btn';
    nextBtn.textContent = '→';
    nextBtn.disabled    = currentPage === totalPages;
    nextBtn.onclick     = () => { currentPage++; renderRecordsTable(); };
    pgBtns.appendChild(nextBtn);

    pg.style.display = totalPages > 1 ? 'flex' : 'none';
}

/*****************************************************************************************************************************************************************************/

/* ─── TOAST ÚNICO DE NOTIFICACIÓN ────────────────────────────────────────── */

/* Solo puede existir un toast a la vez. El siguiente reemplaza al anterior.  */
let activeToast = null;

function toast(type, msg, title) {
    /* type: 'ok' | 'err' | null (neutral) */
    const c = document.getElementById('toasts');
    if (!c) return;

    if (activeToast) {
        activeToast.remove();
        activeToast = null;
    }

    const iconOk = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <polyline points="20 6 9 17 4 12"/></svg>`;
    const iconErr = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;
    const iconNeutral = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

    const t = document.createElement('div');
    t.className = 'toast' + (type === 'ok' ? ' ok' : type === 'err' ? ' err' : '');
    t.innerHTML = `
        ${type === 'ok' ? iconOk : type === 'err' ? iconErr : iconNeutral}
        <div class="toast-body">
            ${title ? `<div class="toast-title">${escHtml(title)}</div>` : ''}
            ${msg   ? `<div class="toast-msg">${escHtml(msg)}</div>`     : ''}
        </div>`;
    c.appendChild(t);
    activeToast = t;

    const duration = type === 'err' ? 4200 : 3000;
    setTimeout(() => {
        if (!t.isConnected) return;
        t.style.animation = 'tOut .3s ease forwards';
        setTimeout(() => { t.remove(); if (activeToast === t) activeToast = null; }, 300);
    }, duration);
}

/*****************************************************************************************************************************************************************************/

/* ─── UTILIDADES ──────────────────────────────────────────────────────────── */
function escHtml(str) {
    return String(str ?? '')        .replace(/&/g, '&amp;').replace(/"/g, '&quot;')

        .replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/'/g, '&#39;');
}

/*****************************************************************************************************************************************************************************/
