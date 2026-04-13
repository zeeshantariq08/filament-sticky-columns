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
    // 1. Cell's own background if already opaque
    const own = getComputedStyle(cell).backgroundColor;
    if (isOpaque(own)) return own;

    // 2. Walk up the DOM tree
    let el = cell.parentElement;
    while (el && el !== document.body) {
      const bg = getComputedStyle(el).backgroundColor;
      if (isOpaque(bg)) return bg;
      el = el.parentElement;
    }

    // 3. Filament panel CSS custom property fallback
    return 'var(--body-bg, #ffffff)';
  }

  function isOpaque(color) {
    return (
      color &&
      color !== 'rgba(0, 0, 0, 0)' &&
      color !== 'transparent' &&
      !color.startsWith('rgba(0, 0, 0, 0)')
    );
  }

  // ─── Scroll shadows ─────────────────────────────────────────────────────────

  function bindScrollShadows() {
    document
      .querySelectorAll('.fi-ta-table-wrapper, [data-sticky-wrapper]')
      .forEach((wrapper) => {
        if (wrapper._stickyBound) return;
        wrapper._stickyBound = true;

        const update = () => {
          const atLeft  = wrapper.scrollLeft <= 1;
          const atRight =
            wrapper.scrollLeft >= wrapper.scrollWidth - wrapper.clientWidth - 1;

          wrapper
            .querySelectorAll(`[${APPLIED_ATTR}="left"]`)
            .forEach((el) => el.classList.toggle('sticky-shadow-active', !atLeft));

          wrapper
            .querySelectorAll(`[${APPLIED_ATTR}="right"]`)
            .forEach((el) => el.classList.toggle('sticky-shadow-active', !atRight));
        };

        wrapper.addEventListener('scroll', update, { passive: true });
        // Run once immediately to set correct initial state
        update();
      });
  }

  // ─── Boot ───────────────────────────────────────────────────────────────────

  function boot() {
    applyStickyColumns();
    bindScrollShadows();
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

  // ── Manual re-trigger ────────────────────────────────────────────────────
  window.FilamentStickyColumns = { refresh: boot };

})();
