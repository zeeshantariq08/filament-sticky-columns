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

### Publish config (optional)

```bash
php artisan vendor:publish --tag="filament-sticky-columns-config"
```

### Publish assets

This package registers JS/CSS via Filament assets. In most apps, Filament will publish them automatically during installs/upgrades.

If you need to publish manually (or you updated the package locally), run:

```bash
php artisan filament:assets
```

### Local development (path repository)

If you develop this package inside a Laravel app repo, add a Composer path repository in your app `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "packages/zeeshantariq/filament-sticky-columns",
      "options": { "symlink": true }
    }
  ]
}
```

Then require the package and install:

```bash
composer require zeeshantariq/filament-sticky-columns:"dev-main"
php artisan filament:assets
```

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

- Hard refresh the browser (especially after asset changes).
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
