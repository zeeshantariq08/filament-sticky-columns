# Changelog

All notable changes to `zeeshantariq/filament-sticky-columns` will be documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

## [1.0.0] — 2025-04-11

### Added
- `HasStickyColumn` trait with `->sticky()`, `->stickyRight()`, `->stickyZIndex()` fluent methods
- `StickyColumn` drop-in TextColumn subclass, sticky LEFT by default
- `->right()` shorthand alias on `StickyColumn`
- Auto-computed column offsets via JS `offsetWidth` measurement
- Manual offset override via `->sticky(offset: 60)`
- Scroll-triggered directional shadows (`sticky-shadow-active` CSS class)
- Dark mode support via Filament CSS custom properties
- Filament v3, v4, v5 compatibility
- Livewire v3 support (`Livewire.hook('commit', ...)`)
- Livewire v4 support (`livewire:navigated`, `livewire:update` events)
- Alpine.js re-initialisation support
- `window.FilamentStickyColumns.refresh()` for manual re-trigger
- Config file: `z_index`, `background`, `shadow`, `shadow_color`
- Pest test suite
