# Filament Sticky Columns

[![Latest Version on Packagist](https://img.shields.io/packagist/v/zeeshantariq/filament-sticky-columns.svg)](https://packagist.org/packages/zeeshantariq/filament-sticky-columns)
[![Total Downloads](https://img.shields.io/packagist/dt/zeeshantariq/filament-sticky-columns.svg)](https://packagist.org/packages/zeeshantariq/filament-sticky-columns)
[![License](https://img.shields.io/packagist/l/zeeshantariq/filament-sticky-columns.svg)](LICENSE.md)

Sticky (frozen) table columns for **Filament v3, v4, and v5**.

Pin one or more columns to the left or right edge while the rest of the table scrolls horizontally — just like Excel or Google Sheets.

---

## Features

- 📌 Stick columns to the **left** or **right**
- 🌗 **Dark-mode** aware — inherits Filament panel surface colours automatically
- 🔢 Multiple sticky columns with **auto-computed offsets**
- 🌊 **Scroll shadow** indicator so users know content is scrolled beneath
- ⚡️ Works with **Livewire v3 and v4** (Filament v3–v5)
- 🧩 Use **`StickyColumn`** drop-in, or add stickiness to **any** column using **`->sticky()`**
- 🔧 Zero config required; publish only when you need to override defaults

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ≥ 8.1 |
| Laravel | ≥ 10 |
| Filament | ^3.0 \|\| ^4.0 \|\| ^5.0 |

---

## Installation

### Install from Packagist

```bash
composer require zeeshantariq/filament-sticky-columns
```

### Recommended (Vite) — no `filament:assets`, no hard refresh

If your Filament panel uses a Vite theme (`->viteTheme(...)`), import the package CSS/JS into your theme build.
This gives you automatic cache-busting (hashed filenames) and avoids running `php artisan filament:assets`.

Add to your Filament theme CSS (example: `resources/css/filament/admin/theme.css`):

```css
@import "../../../../vendor/zeeshantariq/filament-sticky-columns/resources/css/filament-sticky-columns.css";
```

Import the JS in a file that is loaded by your panel (example: a Filament theme JS entry, or your panel JS bundle):

```js
import '../../../../vendor/zeeshantariq/filament-sticky-columns/resources/js/filament-sticky-columns.js'
```

Then rebuild your assets:

```bash
npm run dev
# or
npm run build
```

### Publish config

```bash
php artisan vendor:publish --tag="filament-sticky-columns-config"
```

### Publish assets (Filament assets pipeline)

This package also registers JS/CSS via Filament assets.

Filament v3/v4 publish all registered assets with this command:

```bash
php artisan filament:assets
```

**Cache busting (no hard refresh):** asset filenames include your installed package version and the built files’ last-modified time, so after `composer update` or a fresh install, run `php artisan filament:assets` (or rely on `filament:upgrade` in your app’s Composer scripts) and do a **normal** page reload — the browser should request new CSS/JS URLs automatically.

---

## Usage

### Option A — `StickyColumn` drop-in

`StickyColumn` is a drop-in replacement for Filament’s `TextColumn`. It is sticky **left** by default.

```php
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use ZeeshanTariq\FilamentStickyColumns\Columns\StickyColumn;

public function table(Table $table): Table
{
    return $table->columns([

        // Sticky LEFT (default)
        StickyColumn::make('id')->sortable(),
        StickyColumn::make('name')->searchable(),

        // Regular scrollable columns
        TextColumn::make('email'),
        TextColumn::make('phone'),
        TextColumn::make('department'),

        // Sticky RIGHT
        StickyColumn::make('status')->right()->badge(),

    ]);
}
```

---

### Option B — Sticky any column with `->sticky()` / `->stickyRight()`

This package registers macros on `Filament\Tables\Columns\Column`, so you can pin **any** column type without switching to `StickyColumn`.

```php
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;

public function table(Table $table): Table
{
    return $table->columns([
        TextColumn::make('id')->label('ID')->sticky(),
        ImageColumn::make('avatar')->circular()->sticky(),
        TextColumn::make('name')->searchable()->sticky(),

        TextColumn::make('email'),
        TextColumn::make('phone'),

        IconColumn::make('active')->boolean()->stickyRight(),
    ]);
}
```

---

### Option C — Manual offsets

When auto-compute isn't accurate (e.g. beside a checkbox column):

```php
StickyColumn::make('id')->sticky(offset: 0),    // starts at 0
StickyColumn::make('name')->sticky(offset: 60), // after a 60 px ID column
```

---

## API reference

### `StickyColumn`

```php
StickyColumn::make('name')                 // sticky left
StickyColumn::make('name')->right()        // sticky right
StickyColumn::make('name')->sticky()       // sticky left (explicit)
StickyColumn::make('name')->stickyRight()  // sticky right
StickyColumn::make('name')->sticky(offset: 120)
```

### Column macros

```php
TextColumn::make('name')->sticky(condition: true, offset: null, zIndex: null)
TextColumn::make('actions')->stickyRight(condition: true, offset: null, zIndex: null)
```

---

## Configuration

```php
// config/filament-sticky-columns.php
return [
    'z_index'      => 10,                        // z-index for sticky cells
    'background'   => 'auto',                    // 'auto' = Filament surface colour
    'shadow'       => true,                      // directional scroll shadow
    'shadow_color' => 'rgba(0, 0, 0, 0.12)',     // shadow CSS colour
];
```

---

## Manual refresh

If you dynamically add columns or swap table content outside of normal Livewire updates:

```js
window.FilamentStickyColumns.refresh();
```

---

## Troubleshooting

### Sticky columns not applying

Checklist:

- Make sure the assets are published:

```bash
php artisan filament:assets
```

- **Filament v4 note (important)**:
  - Filament v4 renders table **cells** using `extraHeaderAttributes()` (`<th>`) and `extraCellAttributes()` (`<td>`).
  - `extraAttributes()` is applied to **inner** column markup (e.g. `TextColumn`’s wrapper), which is not always enough for sticky positioning.
  - When debugging, ensure you can see `data-sticky="left"` / `data-sticky="right"` on the actual `<th>` / `<td>` elements in your table (not only on a nested `<div>`).

- If you use the Filament assets pipeline, refresh once after publishing.
- If you use Vite (recommended), you should not need hard refresh because Vite cache-busts.
- Inspect the table DOM and confirm your sticky columns have `data-sticky="left"` / `data-sticky="right"` somewhere inside the cell.

### Offsets look wrong

If you use selection/checkbox columns or very dynamic columns, auto offsets can be off. Use manual offsets:

```php
TextColumn::make('id')->sticky(offset: 0)
TextColumn::make('name')->sticky(offset: 80)
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT — see [LICENSE.md](LICENSE.md).
