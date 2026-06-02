// ═══════════════════════════════════════════════════════════════════════════════
//  ConstructStore — store.js
//  Autor: Magallanes López Carlos Gabriel · v1.1 · 2026
// ═══════════════════════════════════════════════════════════════════════════════

const cs_user  = JSON.parse(localStorage.getItem('cs_user') || '{}');
const cs_token = localStorage.getItem('cs_token') || '';
const DB       = cs_user.db_name || null;

const API_BASE = '../api/index.php';

const AUTH_HEADERS = {
    'Authorization': `Bearer ${cs_token}`,
    'X-Internal-Key': 'ConstructSys_Internal_2026_!xK9',
};

// ─── STATE ────────────────────────────────────────────────────────────────────
let PRODUCTS   = [];
let cart       = {};          // { [id]: { product, qty } }
let quantities = {};          // cantidades del selector en el grid
let currentCat = 'Todos';

// ─── BOOT ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (!DB || !cs_token) {
        renderError(new Error('Inicia sesión para ver el catálogo de productos.'));
        return;
    }
    syncCartUI();   // dibuja carrito vacío correctamente desde el inicio
    fetchAll();
});

// ═══════════════════════════════════════════════════════════════════════════════
//  IMAGE HELPERS
//  Las imágenes se sirven desde assets/imgs/mongo/<colección>/<imagen_clave>.png
//  imagen_clave en la BD coincide con el nombre snake_case del archivo.
// ═══════════════════════════════════════════════════════════════════════════════
// ─── IMAGE RESOLUTION ─────────────────────────────────────────────────────────

/**
 * Convierte cualquier string al snake_case sin tildes que usan los archivos:
 *   "Martillo de Uña"    → "martillo_de_una"
 *   "Pistola de Silicón" → "pistola_de_silicon"
 *   "Varilla 3/8\""      → "varilla_3_8"
 *   "Klein Tools"        → "klein_tools"
 */
function toImageSlug(str) {
    return String(str)
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')  // quita tildes/acentos
        .replace(/[^a-z0-9]+/g, '_')                       // todo lo demás → guión bajo
        .replace(/^_+|_+$/g, '');                          // limpia extremos
}

/**
 * Resuelve la clave de imagen de un producto.
 * Prioridad:
 *   1. imagen_clave de la BD  (si ya viene correcto desde MongoDB)
 *   2. tipo  (más específico: "Martillo de bola", "Cemento Portland gris"…)
 *   3. nombre (fallback)
 */
// ✅ DESPUÉS — tipo primero, siempre genera el slug correcto
function resolveImageKey(p) {
    if (p.tipo)         return toImageSlug(p.tipo);   // → "mezcladora_de_concreto" ✅
    if (p.imagen_clave) return p.imagen_clave;
    return toImageSlug(p.nombre);
}
/** Ruta completa del producto. */
function imgUrl(p) {
    const key = resolveImageKey(p);
    if (!key) return '';
    const folder = { 'Herramientas':'herramientas', 'Maquinaria':'maquinaria' }[p.categoria] ?? 'materiales';
    return `../assets/imgs/mongo/${folder}/${key}.jpg`;
}

/** Logo de marca — mismo slug. "Sin marca" devuelve ''. */
function marcaUrl(marca) {
    if (!marca || marca === 'Sin marca') return '';
    return `../assets/imgs/mongo/logos/${toImageSlug(marca)}.jpg`;
}
// ═══════════════════════════════════════════════════════════════════════════════
//  DATA LAYER
// ═══════════════════════════════════════════════════════════════════════════════
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
        renderStockOverview();   // ← nuevo: sección de inventario
        renderGrid();
    } catch (err) {
        renderError(err);
    }
}

async function apiFetch(module) {
    const prefixedModule = `${DB}_${module}`;
    const res = await fetch(`${API_BASE}?module=${encodeURIComponent(prefixedModule)}`, {
        headers: AUTH_HEADERS
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

// ─── NORMALIZERS ──────────────────────────────────────────────────────────────

/** Mapea tipo de material → categoría de navegación. */
function mapCategoriaMaterial(tipo) {
    const CEMENTANTES  = ['Cemento Portland gris','Cemento blanco','Cal hidratada','Yeso en polvo'];
    const ARIDOS       = ['Arena fina','Arena gruesa','Grava','Tepetate'];
    const BLOQUES      = ['Tabique rojo recocido','Block de concreto','Block pómex','Ladrillo de barro'];
    const MUROS_SECOS  = ['Tablaroca','Panel W','Placa de fibrocemento'];
    const ACERO        = [
        'Varilla 3/8"','Varilla 1/2"','Varilla 3/4"','Alambre recocido',
        'Malla electrosoldada','Perfil de acero en L','Canal de acero en U',
        'Tubo estructural cuadrado','Tubo estructural redondo','Placa de acero',
    ];
    if (CEMENTANTES.includes(tipo))  return 'Cementantes';
    if (ARIDOS.includes(tipo))       return 'Áridos y Gravas';
    if (BLOQUES.includes(tipo))      return 'Bloques y Tabiques';
    if (MUROS_SECOS.includes(tipo))  return 'Muros Secos';
    if (ACERO.includes(tipo))        return 'Acero y Varilla';
    return 'Materiales';
}

function normalizeMateriales(items) {
    return items
        .filter(p => p.activo === true)
        .map(p => {
            const cat = mapCategoriaMaterial(p.tipo ?? '');
            return {
                id:        p._id,
                sku:       '',
                nombre:    p.nombre        ?? 'Sin nombre',
                tipo:      p.tipo          ?? '',
                categoria: cat,
                marca:     '',
                unidad:    p.unidad_medida ?? 'Pieza',
                precio:    toFloat(p.precio_unitario),
                stock:     1,
                imagen_clave: p.imagen_clave ?? '',
                get imagen() { return imgUrl(this); },
            };
        });
}

function normalizeHerramientas(items) {
    return items
        .filter(p => p.activo === true)
        .map(p => ({
            id:        p._id,
            sku:       p.modelo        ?? '',
            nombre:    p.nombre        ?? 'Sin nombre',
            tipo:      p.tipo          ?? '',
            categoria: 'Herramientas',
            marca:     p.marca         ?? '',
            unidad:    p.unidad_medida ?? 'Pieza',
            precio:    toFloat(p.precio),
            stock:     1,
            imagen_clave: p.imagen_clave ?? '',
            get imagen() { return imgUrl(this); },
        }));
}

function normalizeMaquinaria(items) {
    return items
        .filter(p => p.activo === true)
        .map(p => ({
            id:        p._id,
            sku:       p.modelo        ?? '',
            nombre:    p.nombre        ?? 'Sin nombre',
            tipo:      p.tipo          ?? '',
            categoria: 'Maquinaria',
            marca:     p.marca         ?? '',
            unidad:    p.unidad_medida ?? 'Pieza',
            precio:    toFloat(p.precio),
            stock:     1,
            imagen_clave: p.imagen_clave ?? '',
            get imagen() { return imgUrl(this); },
        }));
}

// ─── HERO STATS ───────────────────────────────────────────────────────────────
function updateHeroStats() {
    const total = PRODUCTS.length;
    const cats  = new Set(PRODUCTS.map(p => p.categoria)).size;
    document.getElementById('stat-total').textContent  = total;
    document.getElementById('stat-cats').textContent   = cats;
    document.getElementById('badge-total').textContent = total;
}

// ═══════════════════════════════════════════════════════════════════════════════
//  STOCK OVERVIEW
//  Sección visual de resumen de inventario por colección / subcategoría.
//  Solo lectura — permite filtrar el grid al hacer clic.
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Definición de grupos para la sección de stock.
 * - filterCats: categorías que abarca (para conteo y filtrado)
 * - filterKey:  valor que se pasa a setCategoria() al hacer clic
 * - subs:       subcategorías a mostrar como pills (opcional)
 * - svgIcon:    icono SVG inline del grupo
 * - accentColor: color del borde/acento de la tarjeta
 */
const STOCK_GROUPS = [
    {
        key:   'Herramientas',
        label: 'Herramientas',
        filterKey:  'Herramientas',
        filterCats: ['Herramientas'],
        accentColor: '#c9a84c',
        svgIcon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
          <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3-3a1 1 0 000-1.4l-1.6-1.6a1 1 0 00-1.4 0l-3 3z"/>
          <path d="M6 18l6.3-6.3M3 21l3-3"/>
          <path d="M9.4 13.6L14 9m-5 5l-1.5 1.5"/>
        </svg>`,
    },
    {
        key:   'Maquinaria',
        label: 'Maquinaria',
        filterKey:  'Maquinaria',
        filterCats: ['Maquinaria'],
        accentColor: '#e67e22',
        svgIcon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
          <circle cx="12" cy="12" r="3"/>
          <path d="M19.07 4.93A10 10 0 014.93 19.07M12 2v2M12 20v2M2 12h2M20 12h2
                   M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41
                   M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
        </svg>`,
    },
    {
        key:   'Materiales',
        label: 'Materiales',
        filterKey:  'Materiales',
        filterCats: ['Cementantes','Bloques y Tabiques','Áridos y Gravas','Acero y Varilla','Muros Secos','Materiales'],
        accentColor: '#27ae60',
        svgIcon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
          <rect x="2" y="7" width="20" height="14" rx="2"/>
          <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
          <line x1="12" y1="12" x2="12" y2="16"/>
          <line x1="10" y1="14" x2="14" y2="14"/>
        </svg>`,
        subs: [
            { label:'Cementantes',    cat:'Cementantes'     },
            { label:'Bloques',        cat:'Bloques y Tabiques' },
            { label:'Áridos',         cat:'Áridos y Gravas' },
            { label:'Acero',          cat:'Acero y Varilla' },
            { label:'Muros Secos',    cat:'Muros Secos'     },
            { label:'Otros',          cat:'Materiales'      },
        ],
    },
];

function renderStockOverview() {
    const grid = document.getElementById('stock-cats-grid');
    if (!grid) return;

    // Chip total
    const chipNum = document.getElementById('stock-chip-num');
    if (chipNum) chipNum.textContent = PRODUCTS.length;

    grid.innerHTML = STOCK_GROUPS.map(group => {
        const groupProducts = PRODUCTS.filter(p => group.filterCats.includes(p.categoria));
        const count         = groupProducts.length;

        // Hasta 6 thumbnails de muestra (distintos)
        const previews = groupProducts
            .filter(p => p.imagen_clave)
            .slice(0, 6)
            .map(p => `
                <div class="scc-thumb">
                  <img src="${escHtml(p.imagen)}"
                       alt="${escHtml(p.nombre)}"
                       loading="lazy"
                       onerror="this.parentElement.style.display='none'"/>
                </div>`)
            .join('');

        // Pills de subcategorías (solo para Materiales)
        const pillsHtml = group.subs
            ? `<div class="scc-subs">
                ${group.subs.map(s => {
                    const n = PRODUCTS.filter(p => p.categoria === s.cat).length;
                    if (!n) return '';
                    return `<span class="scc-pill" onclick="event.stopPropagation();setCategoria('${s.cat}')" title="${s.label}">
                        ${escHtml(s.label)} <em>${n}</em>
                    </span>`;
                }).join('')}
               </div>`
            : '';

        return `
        <div class="stock-cat-card" data-accent="${escHtml(group.accentColor)}"
             onclick="setCategoria('${escHtml(group.filterKey)}')"
             style="--scc-accent:${escHtml(group.accentColor)}">
          <div class="scc-header">
            <div class="scc-icon" style="color:${escHtml(group.accentColor)}">${group.svgIcon}</div>
            <div class="scc-meta">
              <div class="scc-label">${escHtml(group.label)}</div>
              <div class="scc-count"><span>${count}</span> productos</div>
            </div>
            <div class="scc-arrow">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M9 18l6-6-6-6"/>
              </svg>
            </div>
          </div>
          ${previews ? `<div class="scc-thumbs">${previews}</div>` : '<div class="scc-no-img">Sin imágenes disponibles</div>'}
          ${pillsHtml}
        </div>`;
    }).join('');
}

// ═══════════════════════════════════════════════════════════════════════════════
//  RENDER — SKELETONS / ERROR
// ═══════════════════════════════════════════════════════════════════════════════
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
            <h3>No se pudo conectar con la base de datos</h3>
            <p>Verifica que el servidor PHP esté corriendo y que <code>API_BASE</code>
               apunte a la ruta correcta de <code>index.php</code>.</p>
            <p style="margin-top:6px;font-size:12px;color:var(--red)">${escHtml(err.message)}</p>
            <button class="btn-retry" onclick="fetchAll()">Reintentar</button>
          </div>
        </div>`;
}

// ═══════════════════════════════════════════════════════════════════════════════
//  RENDER — GRID
// ═══════════════════════════════════════════════════════════════════════════════
function getFiltered() {
    let list = currentCat === 'Todos'
        ? [...PRODUCTS]
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
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
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

        // Subtítulo: tipo · marca (ambos opcionales)
        const subtitulo  = [p.tipo, p.marca].filter(Boolean).join(' · ');

        // Logo de marca (si existe imagen en /marcas/)
        const marcaLogoUrl = marcaUrl(p.marca);
        const marcaLogoHtml = marcaLogoUrl
            ? `<img class="pcard-marca-logo" src="${escHtml(marcaLogoUrl)}"
                    alt="${escHtml(p.marca)}"
                    onerror="this.style.display='none'">`
            : (p.marca && p.marca !== 'Sin marca'
                ? `<span class="pcard-marca-txt">${escHtml(p.marca)}</span>`
                : '');

        return `
        <div class="pcard" id="pcard-${sid}">
          <div class="pcard-img">
            ${marcaLogoHtml}
            <img src="${escHtml(p.imagen)}" alt="${escHtml(p.nombre)}"
              onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
            <div class="img-placeholder" style="display:none">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
              <span>${escHtml(p.categoria)}</span>
            </div>
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
              <button class="qty-btn" onclick="changeQty('${sid}',-1)" ${outOfStock?'disabled':''}>−</button>
              <div class="qty-num" id="qty-${sid}">${qty}</div>
              <button class="qty-btn" onclick="changeQty('${sid}',1)"  ${outOfStock?'disabled':''}>+</button>
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

/**
 * Cambia la categoría activa, actualiza los tabs y re-renderiza el grid.
 * No necesita recibir `this` — usa data-cat para el highlight.
 * @param {string} cat  Categoría a activar
 * @param {boolean} [scroll=true]  Hacer scroll al grid
 */
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

// ═══════════════════════════════════════════════════════════════════════════════
//  QUANTITY SELECTOR (en el grid)
// ═══════════════════════════════════════════════════════════════════════════════
function changeQty(id, delta) {
    quantities[id] = Math.max(1, (quantities[id] ?? 1) + delta);
    const el = document.getElementById('qty-' + id);
    if (el) el.textContent = quantities[id];
}

// ═══════════════════════════════════════════════════════════════════════════════
//  CART
// ═══════════════════════════════════════════════════════════════════════════════
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
        // Si baja de 1, pregunta si quiere eliminar
        if (confirm(`¿Quitar "${cart[id].product.nombre}" del carrito?`)) {
            removeFromCart(id);
        }
        return;
    }
    cart[id].qty = newQty;
    syncCartUI();
    // No llama renderGrid porque agregar/quitar del carrito no cambia
    // si el producto está "En carrito" (sigue estando) — solo cambia la cantidad
}

function clearCart() {
    if (!Object.keys(cart).length) return;
    if (!confirm('¿Vaciar el carrito?')) return;
    cart = {};
    syncCartUI();
    renderGrid();
}

/**
 * Sincroniza TODA la UI del carrito: badge, label, sidebar items, footer, total.
 * Genera siempre el HTML desde cero para evitar referencias DOM obsoletas.
 */
function syncCartUI() {
    const items = Object.values(cart);
    const count = items.reduce((s, i) => s + i.qty, 0);

    // ─ Badge y label en topbar
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

    // ─ Carrito vacío
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

    // ─ Ítems
    footer.style.display = 'block';
    let total = 0;

    container.innerHTML = items.map(({ product: p, qty }) => {
        const subtotal = p.precio * qty;
        total += subtotal;
        const sid = escHtml(p.id);

        // Thumbnail: usa imagen del producto si existe
        const thumbHtml = p.imagen
            ? `<img src="${escHtml(p.imagen)}" alt="${escHtml(p.nombre)}"
                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
            : '';

        return `
        <div class="citem" id="citem-${sid}">
          <div class="citem-img">
            ${thumbHtml}
            <span style="display:none;align-items:center;justify-content:center;
                         width:100%;height:100%;font-size:10px;color:var(--text3)">
              ${escHtml(p.categoria.slice(0,4))}
            </span>
          </div>
          <div class="citem-info">
            <div class="citem-name">${escHtml(p.nombre)}</div>
            <div class="citem-unit">${escHtml(p.unidad)} · ${fmtMXN(p.precio)}/u</div>
            <div class="citem-row">
              <div class="citem-qty">
                <button class="cqb" onclick="changeCartQty('${sid}',-1)" title="Reducir">−</button>
                <div class="cqn">${qty}</div>
                <button class="cqb" onclick="changeCartQty('${sid}',1)"  title="Aumentar">+</button>
              </div>
              <div class="citem-price">${fmtMXN(subtotal)}</div>
            </div>
          </div>
          <button class="citem-del" onclick="removeFromCart('${sid}')" title="Quitar del carrito">
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

function checkout() {
    if (!Object.keys(cart).length) { toast('Tu carrito está vacío', 'err'); return; }
    toast('Redirigiendo al pago…');
}

function cotizar() {
    if (!Object.keys(cart).length) { toast('Agrega productos primero', 'err'); return; }
    const lineas = Object.values(cart)
        .map(({ product: p, qty }) => `• ${qty}× ${p.nombre} — ${fmtMXN(p.precio * qty)}`)
        .join('\n');
    alert('Cotización:\n\n' + lineas + '\n\nEsta función se conecta con tu sistema de cotizaciones.');
}

// ═══════════════════════════════════════════════════════════════════════════════
//  HELPERS
// ═══════════════════════════════════════════════════════════════════════════════
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
        .replace(/&/g,  '&amp;')
        .replace(/"/g,  '&quot;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/'/g,  '&#39;');
}

// Cerrar carrito con Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCart(); });