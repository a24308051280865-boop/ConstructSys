/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/*                                                                  Scripts para el Proyecto ConstrucSys                                                                     */
/*                                                                                                                                                                           */
/*****************************************************************************************************************************************************************************/
/*                                                                                                                                                                           */
/* Autor: Magallanes López Carlos Gabriel                                                                                                                                    */
/* Versión del Proyecto: 1.3                                                                                                                                                 */
/* Correo: cgmagallanes23@gmail.com                                                                                                                                          */
/* Ultima Modificación: 22/05/2026                                                                                                                                           */
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

/* Estado de la aplicación */
let PRODUCTS   = [];
let cart       = {};
let quantities = {};
let currentCat = 'Todos';

/* Inicialización del módulo al cargar el DOM */
document.addEventListener('DOMContentLoaded', () => {
    if (!DB || !cs_token) {
        renderError(new Error('Inicia sesión para ver el catálogo de productos.'));
        return;
    }
    injectOrderModal();
    syncCartUI();
    fetchAll();
});

/*****************************************************************************************************************************************************************************/

/* Helpers visuales para imágenes y placeholders por categoría */
const CAT_VISUAL = {
    'Cementantes':        { bg: '#1a1506', accent: '#c9a84c', emoji: '🏗️' },
    'Áridos y Gravas':    { bg: '#141208', accent: '#d4a844', emoji: '⛏️' },
    'Bloques y Tabiques': { bg: '#0e1218', accent: '#7a99b0', emoji: '🧱' },
    'Muros Secos':        { bg: '#0e1814', accent: '#5aad80', emoji: '📐' },
    'Acero y Varilla':    { bg: '#12121a', accent: '#8888bb', emoji: '🔩' },
    'Materiales':         { bg: '#181010', accent: '#bb7755', emoji: '📦' },
    'Herramientas':       { bg: '#0a1010', accent: '#4aaa88', emoji: '🔧' },
    'Maquinaria':         { bg: '#120e0a', accent: '#e08030', emoji: '⚙️' },
};

function imgUrl(p) {
    if (!p.tipo && !p.imagen_clave) return '';
    const key    = p.imagen_clave || toImageSlug(p.tipo || p.nombre);
    const folder = { 'Herramientas': 'herramientas', 'Maquinaria': 'maquinaria' }[p.categoria] ?? 'materiales';
    return `../assets/imgs/mongo/${folder}/${key}.jpg`;
}

function placeholderSvg(categoria, nombre) {
    const v       = CAT_VISUAL[categoria] ?? { bg: '#111', accent: '#c9a84c', emoji: '📦' };
    const inicial = (nombre || categoria || '?')[0].toUpperCase();
    const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='300' height='220'>
        <rect width='300' height='220' fill='${v.bg}'/>
        <text x='150' y='105' text-anchor='middle' dominant-baseline='middle'
              font-size='64' font-family='sans-serif' opacity='0.18' fill='${v.accent}'>${v.emoji}</text>
        <text x='150' y='158' text-anchor='middle' dominant-baseline='middle'
              font-size='28' font-weight='700' font-family='sans-serif' fill='${v.accent}' opacity='0.55'>${inicial}</text>
        <rect x='0' y='200' width='300' height='4' rx='2' fill='${v.accent}' opacity='0.3'/>
    </svg>`;
    return `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;
}

function toImageSlug(str) {
    return String(str)
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
}

function marcaUrl(marca) {
    if (!marca || marca === 'Sin marca') return '';
    const overrides = {
        'Atlas Copco':      'atlas_copco',
        'Klein Tools':      'klein_tools',
        'Lincoln Electric': 'lincoln_electric',
        'Wacker Neuson':    'wacker_neuson',
    };
    const key = overrides[marca] ?? toImageSlug(marca);
    return `../assets/imgs/mongo/marcas/${key}.jpg`;
}

/*****************************************************************************************************************************************************************************/

/* Capa de datos — fetch y normalización de productos */
async function fetchAll() {
    renderSkeletons();
    try {
        const [rawMateriales, rawHerramientas, rawMaquinaria] = await Promise.all([
            apiFetch('materiales'),
            apiFetch('herramientas'),
            apiFetch('maquinaria'),
        ]);

        PRODUCTS = [
            ...normalizeMateriales(rawMateriales),
            ...normalizeHerramientas(rawHerramientas),
            ...normalizeMaquinaria(rawMaquinaria),
        ];

        PRODUCTS.forEach(p => { if (!quantities[p.id]) quantities[p.id] = 1; });
        updateHeroStats();
        renderStockOverview();
        renderGrid();
    } catch (err) {
        renderError(err);
    }
}

async function apiFetch(module) {
    const prefixedModule = `${DB}_${module}`;
    const res = await fetch(`${API_BASE}?module=${encodeURIComponent(prefixedModule)}`, {
        headers: AUTH_HEADERS,
    });
    if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        throw new Error(body.error ?? `HTTP ${res.status} en módulo "${module}"`);
    }
    const data = await res.json();
    if (!Array.isArray(data)) {
        throw new Error(`Respuesta inesperada del módulo "${module}": ${JSON.stringify(data)}`);
    }
    return data;
}

/* Normalización de materiales, herramientas y maquinaria */
function mapCategoriaMaterial(tipo) {
    const CEMENTANTES = ['Cemento Portland gris','Cemento blanco','Cal hidratada','Yeso en polvo'];
const ARIDOS = ['Arena gruesa'];
const BLOQUES = ['Block pómex'];
const MUROS_SECOS = ['Panel W', 'Placa de fibrocemento'];
const ACERO = ['Malla electrosoldada', 'Perfil de acero en L', 'Canal de acero en U',
               'Tubo estructural redondo', 'Placa de acero'];
    if (CEMENTANTES.includes(tipo)) return 'Cementantes';
    if (ARIDOS.includes(tipo))      return 'Áridos y Gravas';
    if (BLOQUES.includes(tipo))     return 'Bloques y Tabiques';
    if (MUROS_SECOS.includes(tipo)) return 'Muros Secos';
    if (ACERO.includes(tipo))       return 'Acero y Varilla';
    return 'Materiales';
}

function normalizeMateriales(items) {
    return items.filter(p => p.activo === true).map(p => {
        const cat = mapCategoriaMaterial(p.tipo ?? '');
        return {
            id: p._id, sku: '', nombre: p.nombre ?? 'Sin nombre',
            tipo: p.tipo ?? '', categoria: cat, marca: '',
            unidad: p.unidad_medida ?? 'Pieza',
            precio: toFloat(p.precio_unitario),
            stock: 1, imagen_clave: p.imagen_clave ?? '',
            get imagen()      { return imgUrl(this); },
            get placeholder() { return placeholderSvg(this.categoria, this.nombre); },
        };
    });
}

function normalizeHerramientas(items) {
    return items.filter(p => p.activo === true).map(p => ({
        id: p._id, sku: p.modelo ?? '', nombre: p.nombre ?? 'Sin nombre',
        tipo: p.tipo ?? '', categoria: 'Herramientas', marca: p.marca ?? '',
        unidad: p.unidad_medida ?? 'Pieza', precio: toFloat(p.precio),
        stock: 1, imagen_clave: p.imagen_clave ?? '',
        get imagen()      { return imgUrl(this); },
        get placeholder() { return placeholderSvg(this.categoria, this.nombre); },
    }));
}

function normalizeMaquinaria(items) {
    return items.filter(p => p.activo === true).map(p => ({
        id: p._id, sku: p.modelo ?? '', nombre: p.nombre ?? 'Sin nombre',
        tipo: p.tipo ?? '', categoria: 'Maquinaria', marca: p.marca ?? '',
        unidad: p.unidad_medida ?? 'Pieza', precio: toFloat(p.precio),
        stock: 1, imagen_clave: p.imagen_clave ?? '',
        get imagen()      { return imgUrl(this); },
        get placeholder() { return placeholderSvg(this.categoria, this.nombre); },
    }));
}

/* Actualización de estadísticas en el hero */
function updateHeroStats() {
    const total = PRODUCTS.length;
    const cats  = new Set(PRODUCTS.map(p => p.categoria)).size;
    document.getElementById('stat-total').textContent  = total;
    document.getElementById('stat-cats').textContent   = cats;
    document.getElementById('badge-total').textContent = total;
}

/*****************************************************************************************************************************************************************************/

/* Stock Overview — tarjetas de resumen por grupo de categoría */
const STOCK_GROUPS = [
    {
        key: 'Herramientas', label: 'Herramientas', filterKey: 'Herramientas',
        filterCats: ['Herramientas'], accentColor: '#c9a84c',
        svgIcon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
          <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3-3a1 1 0 000-1.4l-1.6-1.6a1 1 0 00-1.4 0l-3 3z"/>
          <path d="M6 18l6.3-6.3M3 21l3-3"/></svg>`,
    },
    {
        key: 'Maquinaria', label: 'Maquinaria', filterKey: 'Maquinaria',
        filterCats: ['Maquinaria'], accentColor: '#e67e22',
        svgIcon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
          <circle cx="12" cy="12" r="3"/>
          <path d="M19.07 4.93A10 10 0 014.93 19.07M12 2v2M12 20v2M2 12h2M20 12h2"/></svg>`,
    },
    {
        key: 'Materiales', label: 'Materiales', filterKey: 'Materiales',
        filterCats: ['Cementantes','Bloques y Tabiques','Áridos y Gravas','Acero y Varilla','Muros Secos','Materiales'],
        accentColor: '#27ae60',
        svgIcon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
          <rect x="2" y="7" width="20" height="14" rx="2"/>
          <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
          <line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>`,
        subs: [
            { label: 'Cementantes', cat: 'Cementantes' },
            { label: 'Bloques',     cat: 'Bloques y Tabiques' },
            { label: 'Áridos',      cat: 'Áridos y Gravas' },
            { label: 'Acero',       cat: 'Acero y Varilla' },
            { label: 'Muros Secos', cat: 'Muros Secos' },
            { label: 'Otros',       cat: 'Materiales' },
        ],
    },
];

function renderStockOverview() {
    const grid = document.getElementById('stock-cats-grid');
    if (!grid) return;
    const chipNum = document.getElementById('stock-chip-num');
    if (chipNum) chipNum.textContent = PRODUCTS.length;

    grid.innerHTML = STOCK_GROUPS.map(group => {
        const groupProducts = PRODUCTS.filter(p => group.filterCats.includes(p.categoria));
        const count         = groupProducts.length;
        const visual        = CAT_VISUAL[group.filterCats[0]] ?? { bg: '#111', accent: '#c9a84c', emoji: '📦' };

        const previewsHtml = count > 0
            ? `<div class="scc-thumbs">
                ${groupProducts.slice(0, 4).map(() => `
                    <div class="scc-thumb" style="background:${visual.bg};border:1px solid ${visual.accent}22;
                         display:flex;align-items:center;justify-content:center;font-size:20px;">
                        ${visual.emoji}
                    </div>`).join('')}
               </div>`
            : '<div class="scc-no-img">Sin productos registrados</div>';

        const pillsHtml = group.subs
            ? `<div class="scc-subs">
                ${group.subs.map(s => {
                    const n = PRODUCTS.filter(p => p.categoria === s.cat).length;
                    if (!n) return '';
                    return `<span class="scc-pill" onclick="event.stopPropagation();setCategoria('${s.cat}')" title="${s.label}">
                        ${escHtml(s.label)} <em>${n}</em></span>`;
                }).join('')}
               </div>`
            : '';

        return `
        <div class="stock-cat-card" onclick="setCategoria('${escHtml(group.filterKey)}')"
             style="--scc-accent:${escHtml(group.accentColor)}">
          <div class="scc-header">
            <div class="scc-icon" style="color:${escHtml(group.accentColor)}">${group.svgIcon}</div>
            <div class="scc-meta">
              <div class="scc-label">${escHtml(group.label)}</div>
              <div class="scc-count"><span>${count}</span> productos</div>
            </div>
            <div class="scc-arrow">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M9 18l6-6-6-6"/></svg>
            </div>
          </div>
          ${previewsHtml}
          ${pillsHtml}
        </div>`;
    }).join('');
}

/*****************************************************************************************************************************************************************************/

/* Renderizado — Skeletons y estados de error */
function renderSkeletons(count = 8) {
    document.getElementById('grid').innerHTML = Array.from({ length: count }, () => `
        <div class="skel-card">
          <div class="skel-img skeleton"></div>
          <div class="skel-body">
            <div class="skel-line w30 skeleton"></div>
            <div class="skel-line w80 h24 skeleton"></div>
            <div class="skel-line w60 skeleton"></div>
            <div class="skel-line w45 h24 skeleton" style="margin-top:8px"></div>
          </div>
          <div class="skel-foot">
            <div class="skel-btn w40 skeleton"></div>
            <div class="skel-btn flex1 skeleton"></div>
          </div>
        </div>`).join('');
}

function renderError(err) {
    document.getElementById('grid').innerHTML = `
        <div class="error-banner">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <div>
            <h3>No se pudo cargar el catálogo</h3>
            <p>${escHtml(err.message)}</p>
            <button class="btn-retry" onclick="fetchAll()">Reintentar</button>
          </div>
        </div>`;
}

/*****************************************************************************************************************************************************************************/

/* Renderizado — Grid de productos */
function getFiltered() {
    let list = currentCat === 'Todos' ? [...PRODUCTS]
        : PRODUCTS.filter(p => p.categoria === currentCat);
    const sort = document.getElementById('sort-sel').value;
    if (sort === 'price-asc')  list.sort((a, b) => a.precio - b.precio);
    if (sort === 'price-desc') list.sort((a, b) => b.precio - a.precio);
    if (sort === 'name-asc')   list.sort((a, b) => a.nombre.localeCompare(b.nombre));
    return list;
}

function renderGrid() {
    const list = getFiltered();
    const grid = document.getElementById('grid');
    if (!list.length) {
        grid.innerHTML = `
            <div class="empty-grid">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"
                   style="width:40px;height:40px;opacity:.2">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
              <p>No hay productos en esta categoría.</p>
            </div>`;
        return;
    }

    grid.innerHTML = list.map(p => {
        const inCart     = !!cart[p.id];
        const qty        = quantities[p.id] ?? 1;
        const sid        = escHtml(p.id);
        const outOfStock = p.stock <= 0;
        const subtitulo  = [p.tipo, p.marca].filter(Boolean).join(' · ');
        const marcaLogoUrl  = marcaUrl(p.marca);
        const marcaLogoHtml = marcaLogoUrl
            ? `<img class="pcard-marca-logo" src="${escHtml(marcaLogoUrl)}" alt="${escHtml(p.marca)}"
                    onerror="this.style.display='none'">`
            : (p.marca && p.marca !== 'Sin marca'
                ? `<span class="pcard-marca-txt">${escHtml(p.marca)}</span>` : '');

        const realImgSrc = escHtml(p.imagen);
        const phSrc      = escHtml(p.placeholder);
        const imgHtml    = realImgSrc
            ? `<img src="${realImgSrc}" alt="${escHtml(p.nombre)}"
                    onerror="this.src='${phSrc}'"
                    style="width:100%;height:100%;object-fit:cover;"/>`
            : `<img src="${phSrc}" alt="${escHtml(p.nombre)}"
                    style="width:100%;height:100%;object-fit:cover;"/>`;

        return `
        <div class="pcard" id="pcard-${sid}">
          <div class="pcard-img">
            ${marcaLogoHtml}
            ${imgHtml}
          </div>
          <div class="pcard-body">
            <div class="pcard-category">${escHtml(p.categoria)}</div>
            <div class="pcard-name">${escHtml(p.nombre)}</div>
            ${subtitulo ? `<div class="pcard-sku">${escHtml(subtitulo)}</div>` : ''}
            <div class="price-main">${fmtMXN(p.precio)}</div>
            <div class="price-unit-label">
              ${escHtml(p.unidad)}
              ${outOfStock ? ' · <span style="color:var(--red)">Sin stock</span>' : ''}
            </div>
          </div>
          <div class="pcard-footer">
            <div class="qty-ctrl">
              <button class="qty-btn" onclick="changeQty('${sid}',-1)" ${outOfStock ? 'disabled' : ''}>−</button>
              <div class="qty-num" id="qty-${sid}">${qty}</div>
              <button class="qty-btn" onclick="changeQty('${sid}',1)"  ${outOfStock ? 'disabled' : ''}>+</button>
            </div>
            <button class="add-btn ${inCart ? 'in-cart' : ''}" id="add-btn-${sid}"
                    onclick="addToCart('${sid}')"
                    ${outOfStock ? 'disabled style="opacity:.4;cursor:not-allowed"' : ''}>
              ${outOfStock
                  ? `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Sin stock`
                  : inCart
                      ? `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px"><polyline points="20 6 9 17 4 12"/></svg> En carrito`
                      : `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Agregar`
              }
            </button>
          </div>
        </div>`;
    }).join('');
}

function setCategoria(cat, scroll = true) {
    currentCat = cat;
    document.querySelectorAll('.ftab').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.cat === cat);
    });
    renderGrid();
    if (scroll) {
        const section = document.querySelector('.toolbar');
        if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

/*****************************************************************************************************************************************************************************/

/* Selector de cantidad por producto */
function changeQty(id, delta) {
    quantities[id] = Math.max(1, (quantities[id] ?? 1) + delta);
    const el = document.getElementById('qty-' + id);
    if (el) el.textContent = quantities[id];
}

/*****************************************************************************************************************************************************************************/

/* Carrito — agregar, eliminar, modificar y sincronizar UI */
function addToCart(id) {
    const p = PRODUCTS.find(x => x.id === id);
    if (!p || p.stock <= 0) return;
    const qty = quantities[id] ?? 1;
    if (cart[id]) cart[id].qty += qty;
    else          cart[id] = { product: p, qty };
    syncCartUI();
    renderGrid();
    toast(`${p.nombre} agregado al carrito`);
}

function removeFromCart(id) {
    if (!cart[id]) return;
    const nombre = cart[id].product.nombre;
    delete cart[id];
    syncCartUI();
    renderGrid();
    toast(`${nombre} eliminado`);
}

function changeCartQty(id, delta) {
    if (!cart[id]) return;
    const newQty = cart[id].qty + delta;
    if (newQty < 1) {
        if (confirm(`¿Quitar "${cart[id].product.nombre}" del carrito?`)) {
            removeFromCart(id);
        }
        return;
    }
    cart[id].qty = newQty;
    syncCartUI();
}

function clearCart() {
    if (!Object.keys(cart).length) return;
    if (!confirm('¿Vaciar el carrito?')) return;
    cart = {};
    syncCartUI();
    renderGrid();
}

function syncCartUI() {
    const items = Object.values(cart);
    const count = items.reduce((s, i) => s + i.qty, 0);

    const badge = document.getElementById('cart-badge');
    const label = document.getElementById('cart-label');
    if (badge) {
        badge.textContent   = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    }
    if (label) label.textContent = count > 0 ? `${count} item${count !== 1 ? 's' : ''}` : 'Carrito';

    const container = document.getElementById('cart-items');
    const footer    = document.getElementById('cart-footer');
    if (!container || !footer) return;

    if (!items.length) {
        container.innerHTML = `
            <div class="cart-empty">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                <path d="M6 2 3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 01-8 0"/>
              </svg>
              <p>Tu carrito está vacío.<br>Agrega productos para comenzar.</p>
            </div>`;
        footer.style.display = 'none';
        return;
    }

    footer.style.display = 'block';
    let total = 0;

    container.innerHTML = items.map(({ product: p, qty }) => {
        const subtotal = p.precio * qty;
        total += subtotal;
        const sid    = escHtml(p.id);
        const phSrc  = escHtml(p.placeholder);
        const imgSrc = p.imagen
            ? `${escHtml(p.imagen)}" onerror="this.src='${phSrc}'`
            : phSrc;

        return `
        <div class="citem" id="citem-${sid}">
          <div class="citem-img">
            <img src="${imgSrc}" alt="${escHtml(p.nombre)}"
                 style="width:100%;height:100%;object-fit:cover;border-radius:4px;"/>
          </div>
          <div class="citem-info">
            <div class="citem-name">${escHtml(p.nombre)}</div>
            <div class="citem-unit">${escHtml(p.unidad)} · ${fmtMXN(p.precio)}/u</div>
            <div class="citem-row">
              <div class="citem-qty">
                <button class="cqb" onclick="changeCartQty('${sid}',-1)">−</button>
                <div class="cqn">${qty}</div>
                <button class="cqb" onclick="changeCartQty('${sid}',1)">+</button>
              </div>
              <div class="citem-price">${fmtMXN(subtotal)}</div>
            </div>
          </div>
          <button class="citem-del" onclick="removeFromCart('${sid}')">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6l-1 14H6L5 6"/>
              <path d="M10 11v6M14 11v6"/>
              <path d="M9 6V4h6v2"/>
            </svg>
          </button>
        </div>`;
    }).join('');

    const totalEl = document.getElementById('cart-total');
    if (totalEl) totalEl.textContent = fmtMXN(total);
}

function openCart() {
    document.getElementById('cart-sidebar').classList.add('open');
    document.getElementById('cart-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeCart() {
    document.getElementById('cart-sidebar').classList.remove('open');
    document.getElementById('cart-overlay').classList.remove('open');
    document.body.style.overflow = '';
}

function cotizar() {
    if (!Object.keys(cart).length) { toast('Agrega productos primero', 'err'); return; }
    const lineas = Object.values(cart)
        .map(({ product: p, qty }) => `• ${qty}× ${p.nombre} — ${fmtMXN(p.precio * qty)}`)
        .join('\n');
    alert('Cotización:\n\n' + lineas + '\n\nEsta función se conecta con tu sistema de cotizaciones.');
}

/*****************************************************************************************************************************************************************************/

/* Modal de confirmación de pedido */
function injectOrderModal() {
    /* ── Estilos ── */
    const style = document.createElement('style');
    style.textContent = `
    /* Overlay */
    #om-overlay {
        position: fixed; inset: 0;
        background: rgba(0,0,0,.78);
        backdrop-filter: blur(5px);
        z-index: 9000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    #om-overlay.open { display: flex; }

    /* Modal */
    #om-modal {
        background: #0f0f0f;
        border: 1px solid #222;
        border-radius: 10px;
        width: 100%;
        max-width: 480px;
        max-height: 90vh;
        overflow-y: auto;
        padding: 32px 28px 28px;
        position: relative;
        animation: omIn .24s cubic-bezier(.22,.68,0,1.18);
    }
    @keyframes omIn {
        from { opacity: 0; transform: translateY(18px) scale(.98); }
        to   { opacity: 1; transform: none; }
    }

    /* Cabecera del modal */
    .om-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 22px;
        padding-bottom: 16px;
        border-bottom: 1px solid #1e1e1e;
    }
    .om-header-icon {
        width: 36px; height: 36px;
        background: #1a1506;
        border: 1px solid #c9a84c28;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #c9a84c;
        flex-shrink: 0;
    }
    .om-header h2 {
        font-size: 15px;
        font-weight: 700;
        color: #f0ede8;
        margin: 0;
        letter-spacing: .02em;
    }
    .om-header p {
        font-size: 12px;
        color: #5a5650;
        margin: 2px 0 0;
    }

    /* Botón cerrar */
    .om-close {
        position: absolute; top: 14px; right: 14px;
        background: none; border: none;
        color: #3a3630; cursor: pointer;
        padding: 6px; line-height: 0;
        border-radius: 6px;
        transition: color .18s, background .18s;
    }
    .om-close:hover { color: #f0ede8; background: #1e1e1e; }

    /* Tabla de líneas */
    .om-lines {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 4px;
        font-size: 13px;
    }
    .om-lines thead th {
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #4a4640;
        padding: 0 0 10px;
        border-bottom: 1px solid #1e1e1e;
        text-align: left;
    }
    .om-lines thead th:last-child { text-align: right; }
    .om-lines tbody td {
        padding: 10px 0;
        border-bottom: 1px solid #161616;
        color: #9e9890;
        vertical-align: middle;
    }
    .om-lines tbody tr:last-child td { border-bottom: none; }
    .om-lines td.om-td-name  { color: #d0cdc8; font-weight: 500; }
    .om-lines td.om-td-qty   { color: #6a6660; font-size: 12px; padding-right: 12px; white-space: nowrap; }
    .om-lines td.om-td-unit  { color: #5a5650; font-size: 11px; padding-right: 12px; white-space: nowrap; }
    .om-lines td.om-td-price { text-align: right; color: #c9a84c; font-weight: 600;
                                font-family: 'DM Mono', monospace; white-space: nowrap; }

    /* Total */
    .om-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 0 0;
        margin-top: 4px;
        border-top: 1px solid #222;
    }
    .om-total-label {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #5a5650;
    }
    .om-total-amount {
        font-size: 18px;
        font-weight: 700;
        color: #c9a84c;
        font-family: 'DM Mono', monospace;
    }
    .om-total-note {
        font-size: 10px;
        color: #3a3630;
        text-align: right;
        margin-top: 4px;
    }

    /* Separador */
    .om-divider {
        height: 1px;
        background: #1a1a1a;
        margin: 20px 0;
    }

    /* Número de orden */
    .om-order-block {
        display: none;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        padding: 20px 0 4px;
        text-align: center;
        animation: omIn .3s ease;
    }
    .om-order-block.show { display: flex; }
    .om-order-icon {
        width: 52px; height: 52px;
        background: #0b1e10;
        border: 1.5px solid #27ae6044;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        animation: omPop .35s cubic-bezier(.22,.68,0,1.5);
    }
    @keyframes omPop { from { transform: scale(0); } to { transform: scale(1); } }
    .om-order-title {
        font-size: 15px;
        font-weight: 700;
        color: #27ae60;
        letter-spacing: .02em;
    }
    .om-order-sub {
        font-size: 12px;
        color: #4a4640;
        line-height: 1.6;
    }
    .om-order-num {
        background: #0d0d0d;
        border: 1px solid #222;
        border-radius: 6px;
        padding: 8px 20px;
        font-family: 'DM Mono', monospace;
        font-size: 15px;
        color: #c9a84c;
        letter-spacing: .12em;
        margin: 4px 0;
    }
    .om-order-hint {
        font-size: 10px;
        color: #3a3630;
    }

    /* Acciones */
    .om-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 20px;
    }
    .om-btn-confirm {
        width: 100%;
        padding: 12px;
        background: #c9a84c;
        color: #080808;
        border: none;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: .07em;
        text-transform: uppercase;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        transition: background .18s;
    }
    .om-btn-confirm:hover { background: #e8c96a; }
    .om-btn-secondary {
        width: 100%;
        padding: 10px;
        background: none;
        border: 1px solid #222;
        border-radius: 7px;
        color: #5a5650;
        font-size: 13px;
        cursor: pointer;
        transition: border-color .18s, color .18s;
    }
    .om-btn-secondary:hover { border-color: #3a3630; color: #9e9890; }
    .om-btn-done {
        width: 100%;
        padding: 12px;
        background: #27ae60;
        color: #fff;
        border: none;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: background .18s;
    }
    .om-btn-done:hover { background: #2ecc71; }
    `;
    document.head.appendChild(style);

    /* ── Markup ── */
    const el = document.createElement('div');
    el.id = 'om-overlay';
    el.innerHTML = `
    <div id="om-modal" role="dialog" aria-modal="true" aria-labelledby="om-title">

        <button class="om-close" onclick="closeOrderModal()" aria-label="Cerrar">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                 style="width:18px;height:18px">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>

        <!-- Cabecera -->
        <div class="om-header">
            <div class="om-header-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                     style="width:18px;height:18px">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                             M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <h2 id="om-title">Resumen del pedido</h2>
                <p>Verifica las partidas antes de confirmar</p>
            </div>
        </div>

        <!-- Vista: resumen -->
        <div id="om-view-summary">
            <table class="om-lines" id="om-lines-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Unidad</th>
                        <th>Importe</th>
                    </tr>
                </thead>
                <tbody id="om-lines-body"></tbody>
            </table>
            <div class="om-total">
                <span class="om-total-label">Total estimado</span>
                <span class="om-total-amount" id="om-total-amount">—</span>
            </div>
            <div class="om-total-note">Precios en MXN · IVA no incluido</div>
            <div class="om-actions">
                <button class="om-btn-confirm" onclick="confirmOrder()">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                         style="width:15px;height:15px">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Confirmar pedido
                </button>
                <button class="om-btn-secondary" onclick="closeOrderModal()">
                    Cancelar y volver al carrito
                </button>
            </div>
        </div>

        <!-- Vista: confirmación -->
        <div class="om-order-block" id="om-view-confirmed">
            <div class="om-order-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="#27ae60" stroke-width="2.5"
                     style="width:26px;height:26px">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <div class="om-order-title">Pedido registrado</div>
            <div class="om-order-sub">Tu pedido ha sido capturado en el sistema.<br>Conserva el número de referencia.</div>
            <div class="om-order-num" id="om-order-num">—</div>
            <div class="om-order-hint">Número de orden interno · ConstructStore</div>
            <div class="om-actions" style="width:100%">
                <button class="om-btn-done" onclick="finishOrder()">Listo</button>
            </div>
        </div>

    </div>`;
    document.body.appendChild(el);

    /* Cerrar con Escape */
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && document.getElementById('om-overlay').classList.contains('open')) {
            closeOrderModal();
        }
    });
}

/* Abrir modal y poblar resumen */
function checkout() {
    if (!Object.keys(cart).length) { toast('Tu carrito está vacío', 'err'); return; }

    /* Poblar tabla */
    const items  = Object.values(cart);
    let total    = 0;
    const tbody  = document.getElementById('om-lines-body');
    tbody.innerHTML = items.map(({ product: p, qty }) => {
        const importe = p.precio * qty;
        total += importe;
        return `
        <tr>
            <td class="om-td-name">${escHtml(p.nombre)}</td>
            <td class="om-td-qty">${qty}</td>
            <td class="om-td-unit">${escHtml(p.unidad)}</td>
            <td class="om-td-price">${fmtMXN(importe)}</td>
        </tr>`;
    }).join('');

    document.getElementById('om-total-amount').textContent = fmtMXN(total);

    /* Mostrar vista de resumen, ocultar confirmación */
    document.getElementById('om-view-summary').style.display   = '';
    document.getElementById('om-view-confirmed').classList.remove('show');

    /* Abrir */
    document.getElementById('om-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    closeCart();
}

function closeOrderModal() {
    document.getElementById('om-overlay').classList.remove('open');
    document.body.style.overflow = '';
}

/* Confirmar: genera número de orden y muestra pantalla de éxito */
function confirmOrder() {
    const orderNum = 'CS-' + new Date().getFullYear()
        + '-' + Date.now().toString(36).toUpperCase().slice(-5)
        + '-' + Math.random().toString(36).slice(2, 5).toUpperCase();

    document.getElementById('om-order-num').textContent    = orderNum;
    document.getElementById('om-view-summary').style.display = 'none';
    document.getElementById('om-view-confirmed').classList.add('show');
}

/* Finalizar: vacía carrito, cierra modal */
function finishOrder() {
    cart = {};
    syncCartUI();
    renderGrid();
    closeOrderModal();
    toast('Pedido registrado correctamente', 'ok');
}

/*****************************************************************************************************************************************************************************/

/* Utilidades generales */
function fmtMXN(n) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency', currency: 'MXN', minimumFractionDigits: 2,
    }).format(n);
}

function toast(msg, type) {
    const c = document.getElementById('toasts');
    if (!c) return;
    const t = document.createElement('div');
    t.className   = 'toast' + (type === 'err' ? ' err' : '');
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => {
        t.style.animation = 'tOut .3s ease forwards';
        setTimeout(() => t.remove(), 300);
    }, 2800);
}

function toFloat(v) { const n = parseFloat(v);  return isFinite(n) ? n : 0; }
function toInt(v)   { const n = parseInt(v, 10); return isFinite(n) ? n : 0; }

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
        .replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/'/g, '&#39;');
}

/*****************************************************************************************************************************************************************************/

/* Panel de administración */
function openAdmin() {
    window.location.href = 'store_admin.html';
}
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
    toast('Formulario limpiado');
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

    // Limpiar campos vacíos opcionales
    ['descripcion', 'imagen_clave', 'modelo', 'contacto', 'notas'].forEach(k => {
        if (data[k] === '' || data[k] === undefined) delete data[k];
    });

    const originalText = btn?.textContent ?? '';
    if (btn) { btn.disabled = true; btn.textContent = 'Registrando…'; }

    try {
        // ← Ahora apunta al endpoint dedicado que resuelve proveedor_id
        const res = await fetch('../api/store/admin-insert.php', {
            method:  'POST',
            headers: {
                'Content-Type':  'application/json',
                'Authorization': `Bearer ${cs_token}`,
                'X-Internal-Key': 'ConstructSys_Internal_2026_!xK9',
            },
            body: JSON.stringify({ coleccion, data }),
        });

        const result = await res.json();

        if (result.success) {
            toast('Producto registrado correctamente', 'ok');
            adminReset(form);
            // Recargar catálogo si aplica
            if (['materiales', 'herramientas', 'maquinaria'].includes(coleccion)) fetchAll();
        } else {
            toast(`Error: ${result.error ?? 'Error desconocido'}`, 'err');
        }

    } catch (err) {
        toast(`Error de conexión: ${err.message}`, 'err');
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = originalText; }
    }

    return false;
}
function adminCopyJSON(form) {
    const data = Object.fromEntries(new FormData(form).entries());
    if (data.precio_unitario !== undefined) data.precio_unitario = parseFloat(data.precio_unitario);
    if (data.precio          !== undefined) data.precio          = parseFloat(data.precio);
    data.activo = data.activo === 'true';
    navigator.clipboard.writeText(JSON.stringify(data, null, 2))
        .then(() => toast('JSON copiado'))
        .catch(() => toast('No se pudo copiar', 'err'));
}

document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    closeCart();
    closeAdmin();
});

/*****************************************************************************************************************************************************************************/
