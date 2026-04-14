/**
 * filament-sticky-columns.js
 * zeeshantariq/filament-sticky-columns
 *
 * Reads [data-sticky] attributes placed by the PHP HasStickyColumn trait
 * and applies position:sticky + correct offsets to every matching <th>/<td>.
 *
 * Compatible with:
 *   - Filament v3 + Livewire v3
 *   - Filament v4 + Livewire v3
 *   - Filament v5 + Livewire v4
 */

(function () {
  'use strict';

  // ─── Constants ──────────────────────────────────────────────────────────────

  const ATTR         = 'data-sticky';           // 'left' | 'right'
  const ATTR_OFFSET  = 'data-sticky-offset';    // manual px offset
  const ATTR_Z       = 'data-sticky-z-index';   // z-index override
  const APPLIED_ATTR = 'data-sticky-applied';   // set by JS after processing

  const SHADOW_LEFT  = '4px 0 8px -2px var(--sticky-shadow-color, rgba(0,0,0,.12))';
  const SHADOW_RIGHT = '-4px 0 8px -2px var(--sticky-shadow-color, rgba(0,0,0,.12))';

  // ─── Core ───────────────────────────────────────────────────────────────────

  function getStickyDecl(cell) {
    if (!cell) return null;
    if (cell.hasAttribute && cell.hasAttribute(ATTR)) return cell;
    if (cell.querySelector) return cell.querySelector(`[${ATTR}]`);
    return null;
  }

  function getIsDark() {
    const root = document.documentElement;
    const body = document.body;
    return (
      (root && root.classList.contains('dark')) ||
      (body && body.classList.contains('dark'))
    );
  }

  /**
   * Clear presentation we set on a previous run so getComputedStyle / theme
   * resolution is not poisoned by stale inline colours after light/dark toggle.
   */
  function resetStickyPresentation() {
    document.querySelectorAll(`[${APPLIED_ATTR}]`).forEach((el) => {
      el.removeAttribute(APPLIED_ATTR);
      el.style.position = '';
      el.style.left = '';
      el.style.right = '';
      el.style.zIndex = '';
      el.style.backgroundColor = '';
      el.style.boxShadow = '';
    });
  }

  function applyStickyColumns() {
    document.querySelectorAll('table').forEach((table) => {
      // Filament may apply column attributes to <td> but not <th>,
      // so we activate if *any* cell declares sticky.
      if (!table.querySelector(`[${ATTR}]`)) return;

      // Separate border model is required for sticky to work correctly
      table.style.borderCollapse = 'separate';
      table.style.borderSpacing  = '0';

      processTable(table);
    });
  }

  function processTable(table) {
    const referenceCells = getReferenceCells(table);
    const stickyMap = buildStickyMap(referenceCells);

    if (!Object.keys(stickyMap).length) return;

    ['thead', 'tbody', 'tfoot'].forEach((section) => {
      table.querySelectorAll(`${section} tr`).forEach((row) => {
        applyStickyToRow(Array.from(row.children), stickyMap, section === 'thead');
      });
    });
  }

  /**
   * Prefer header cells for width measurement; fall back to first body row.
   */
  function getReferenceCells(table) {
    const headerCells = Array.from(table.querySelectorAll('thead th'));
    if (headerCells.some((cell) => getStickyDecl(cell))) {
      return headerCells;
    }

    const firstBodyRow = table.querySelector('tbody tr');
    if (firstBodyRow) {
      return Array.from(firstBodyRow.children);
    }

    // Last resort: use any th/td we can find.
    return Array.from(table.querySelectorAll('th, td'));
  }

  /**
   * Walk reference cells and compute each sticky column's offset.
   * Returns { colIndex: { position, offset, zIndex } }
   */
  function buildStickyMap(cells) {
    const map    = {};
    let leftAcc  = 0;
    let rightAcc = 0;

    // Left pass — left to right
    cells.forEach((cell, idx) => {
      const decl = getStickyDecl(cell);
      if (decl?.getAttribute(ATTR) === 'left') {
        const manual = decl.getAttribute(ATTR_OFFSET);
        map[idx] = {
          position : 'left',
          offset   : manual !== null ? parseInt(manual, 10) : leftAcc,
          zIndex   : parseInt(decl.getAttribute(ATTR_Z) || '10', 10),
        };
        leftAcc += cell.offsetWidth || 0;
      }
    });

    // Right pass — right to left
    ;[...cells].reverse().forEach((cell, revIdx) => {
      const idx = cells.length - 1 - revIdx;
      const decl = getStickyDecl(cell);
      if (decl?.getAttribute(ATTR) === 'right') {
        const manual = decl.getAttribute(ATTR_OFFSET);
        map[idx] = {
          position : 'right',
          offset   : manual !== null ? parseInt(manual, 10) : rightAcc,
          zIndex   : parseInt(decl.getAttribute(ATTR_Z) || '10', 10),
        };
        rightAcc += cell.offsetWidth || 0;
      }
    });

    return map;
  }

  function applyStickyToRow(cells, stickyMap, isHeader) {
    cells.forEach((cell, idx) => {
      const config = stickyMap[idx];
      if (!config) return;

      cell.style.position         = 'sticky';
      cell.style[config.position] = `${config.offset}px`;
      // Header cells sit above body cells
      cell.style.zIndex           = isHeader ? config.zIndex + 10 : config.zIndex;
      cell.style.backgroundColor  = resolveBackground(cell);

      // Shadow — only when config.shadow is not explicitly disabled
      const showShadow = cell.closest('table')?.dataset.stickyShadow !== 'false';
      if (showShadow) {
        // Shadow is managed by CSS class; inline is just a fallback
        cell.style.boxShadow = '';
      }

      cell.setAttribute(APPLIED_ATTR, config.position);
    });
  }

  /**
   * Resolve an opaque background colour for a sticky cell so content
   * scrolling beneath does not bleed through.
   */
  function resolveBackground(cell) {
    const isDark = getIsDark();

    // 1. Cell's own background if already opaque (but avoid near-white in dark mode)
    const own = getComputedStyle(cell).backgroundColor;
    if (isOpaque(own) && !isNearWhite(own, isDark)) return own;

    // 1b. Table / wrapper background
    const table = cell.closest('table');
    if (table) {
      const tbg = getComputedStyle(table).backgroundColor;
      if (isOpaque(tbg) && !isNearWhite(tbg, isDark)) return tbg;
    }

    const wrapper = cell.closest('.fi-ta-table-wrapper') || cell.closest('[data-sticky-wrapper]');
    if (wrapper) {
      const wbg = getComputedStyle(wrapper).backgroundColor;
      if (isOpaque(wbg) && !isNearWhite(wbg, isDark)) return wbg;
    }

    // 2. Walk up the DOM tree
    let el = cell.parentElement;
    while (el && el !== document.body) {
      const bg = getComputedStyle(el).backgroundColor;
      if (isOpaque(bg) && !isNearWhite(bg, isDark)) return bg;
      el = el.parentElement;
    }

    // 3. CSS variable fallbacks (prefer Filament vars, then body vars)
    return 'var(--fi-body-bg, var(--fi-color-bg, var(--body-bg, rgb(17 24 39))))';
  }

  function isNearWhite(color, isDark) {
    if (!isDark) return false;
    const rgb = parseRgb(color);
    if (!rgb) return false;
    return rgb.r >= 235 && rgb.g >= 235 && rgb.b >= 235;
  }

  function parseRgb(color) {
    if (!color) return null;
    const c = color.trim().toLowerCase();
    let m = c.match(/rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/);
    if (m) return { r: Number(m[1]), g: Number(m[2]), b: Number(m[3]) };
    m = c.match(/rgba\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*([0-9.]+)\s*\)/);
    if (m) {
      const a = Number(m[4]);
      if (!Number.isFinite(a) || a <= 0) return null;
      return { r: Number(m[1]), g: Number(m[2]), b: Number(m[3]) };
    }
    if (c.startsWith('#')) {
      if (c.length === 4) {
        return {
          r: parseInt(c[1] + c[1], 16),
          g: parseInt(c[2] + c[2], 16),
          b: parseInt(c[3] + c[3], 16),
        };
      }
      if (c.length === 7 || c.length === 9) {
        return {
          r: parseInt(c.slice(1, 3), 16),
          g: parseInt(c.slice(3, 5), 16),
          b: parseInt(c.slice(5, 7), 16),
        };
      }
    }
    return null;
  }

  function isOpaque(color) {
    if (!color) return false;

    const c = color.trim().toLowerCase();
    if (c === 'transparent') return false;

    // rgb(...) is always opaque
    if (c.startsWith('rgb(')) return true;

    // rgba(r,g,b,a)
    if (c.startsWith('rgba(')) {
      const m = c.match(/rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*([0-9.]+)\s*\)/);
      if (!m) return false;
      const a = Number(m[1]);
      return Number.isFinite(a) && a >= 1;
    }

    // hsl/hsla
    if (c.startsWith('hsla(')) {
      const m = c.match(/hsla\(.+?,\s*([0-9.]+)\s*\)/);
      if (!m) return false;
      const a = Number(m[1]);
      return Number.isFinite(a) && a >= 1;
    }
    if (c.startsWith('hsl(')) return true;

    // hex colors are opaque
    if (c.startsWith('#')) {
      // #rgb, #rrggbb are opaque; #rgba / #rrggbbaa depend on alpha
      if (c.length === 4 || c.length === 7) return true;
      if (c.length === 5) return c[4] === 'f';
      if (c.length === 9) return c.slice(7, 9) === 'ff';
    }

    // Unknown formats: assume not opaque so we keep searching.
    return false;
  }

  // ─── Scroll shadows ─────────────────────────────────────────────────────────

  function updateWrapperScrollShadows(wrapper) {
    const atLeft  = wrapper.scrollLeft <= 1;
    const atRight =
      wrapper.scrollLeft >= wrapper.scrollWidth - wrapper.clientWidth - 1;

    wrapper
      .querySelectorAll(`[${APPLIED_ATTR}="left"]`)
      .forEach((el) => el.classList.toggle('sticky-shadow-active', !atLeft));

    wrapper
      .querySelectorAll(`[${APPLIED_ATTR}="right"]`)
      .forEach((el) => el.classList.toggle('sticky-shadow-active', !atRight));
  }

  function bindScrollShadows() {
    document
      .querySelectorAll('.fi-ta-table-wrapper, [data-sticky-wrapper]')
      .forEach((wrapper) => {
        if (!wrapper._stickyBound) {
          wrapper._stickyBound = true;
          const update = () => updateWrapperScrollShadows(wrapper);
          wrapper.addEventListener('scroll', update, { passive: true });
        }
        // Re-sync after DOM / sticky attrs change (e.g. theme toggle, Livewire morph)
        updateWrapperScrollShadows(wrapper);
      });
  }

  // ─── Boot ───────────────────────────────────────────────────────────────────

  function boot() {
    resetStickyPresentation();
    applyStickyColumns();
    bindScrollShadows();
  }

  // Re-apply when theme toggles (Filament toggles `dark` on <html> or <body>)
  function bindThemeObserver() {
    if (document.documentElement._stickyThemeObserverBound) return;
    document.documentElement._stickyThemeObserverBound = true;

    let lastIsDark = getIsDark();
    const schedule = () => {
      const isDark = getIsDark();
      if (isDark === lastIsDark) return;
      lastIsDark = isDark;
      requestAnimationFrame(boot);
    };

    const observer = new MutationObserver(schedule);
    observer.observe(document.documentElement, {
      attributes     : true,
      attributeFilter: ['class'],
    });
    if (document.body) {
      observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    } else {
      document.addEventListener('DOMContentLoaded', () => {
        observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
      });
    }

    window.addEventListener('storage', (e) => {
      if (!e.key) return;
      if (!/theme/i.test(e.key)) return;
      requestAnimationFrame(() => {
        boot();
        lastIsDark = getIsDark();
      });
    });
  }

  // ── Initial load ──────────────────────────────────────────────────────────
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  // ── Livewire v3 (Filament v3 / v4) ───────────────────────────────────────
  if (window.Livewire) {
    // Fires after every network round-trip
    window.Livewire.hook('commit', ({ succeed }) => {
      succeed(() => requestAnimationFrame(boot));
    });

    // Fires after Livewire has morphed the DOM (v3.x)
    if (typeof window.Livewire.hook === 'function') {
      try {
        window.Livewire.hook('morph.updated', () => requestAnimationFrame(boot));
      } catch (_) {
        // Hook may not exist in all versions — silently ignore
      }
    }
  }

  // ── Livewire v4 (Filament v5) ─────────────────────────────────────────────
  document.addEventListener('livewire:navigated', () => requestAnimationFrame(boot));
  document.addEventListener('livewire:update',    () => requestAnimationFrame(boot));

  // ── Alpine.js ─────────────────────────────────────────────────────────────
  document.addEventListener('alpine:initialized', boot);
  document.addEventListener('alpine:init',        boot);

  // Theme toggles (dark/light)
  bindThemeObserver();

  // ── Manual re-trigger ────────────────────────────────────────────────────
  window.FilamentStickyColumns = { refresh: boot };

})();
