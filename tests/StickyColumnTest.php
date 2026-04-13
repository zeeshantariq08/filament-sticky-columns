<?php

declare(strict_types=1);

use ZeeshanTariq\FilamentStickyColumns\Columns\StickyColumn;
use ZeeshanTariq\FilamentStickyColumns\FilamentStickyColumnsServiceProvider;

// ── StickyColumn ─────────────────────────────────────────────────────────────

it('creates a sticky left column by default', function () {
    $column = StickyColumn::make('name');

    expect($column->isSticky())->toBeTrue()
        ->and($column->getStickyPosition())->toBe('left');
});

it('can be pinned to the right via ->right()', function () {
    $column = StickyColumn::make('status')->right();

    expect($column->isSticky())->toBeTrue()
        ->and($column->getStickyPosition())->toBe('right');
});

it('can be pinned to the right via ->stickyRight()', function () {
    $column = StickyColumn::make('status')->stickyRight();

    expect($column->getStickyPosition())->toBe('right');
});

it('stores a manual offset', function () {
    $column = StickyColumn::make('name')->sticky(offset: 60);

    expect($column->getStickyOffset())->toBe(60);
});

it('returns null offset when not set', function () {
    $column = StickyColumn::make('name');

    expect($column->getStickyOffset())->toBeNull();
});

it('uses config z_index by default', function () {
    config(['filament-sticky-columns.z_index' => 15]);

    $column = StickyColumn::make('name');

    expect($column->getStickyZIndex())->toBe(15);
});

it('allows z-index override', function () {
    $column = StickyColumn::make('name')->stickyZIndex(25);

    expect($column->getStickyZIndex())->toBe(25);
});

it('can be conditionally disabled', function () {
    $column = StickyColumn::make('name')->sticky(false);

    expect($column->isSticky())->toBeFalse();
});

// ── ServiceProvider ───────────────────────────────────────────────────────────

it('detects filament major version as an integer', function () {
    $version = FilamentStickyColumnsServiceProvider::filamentMajorVersion();

    expect($version)->toBeInt()->toBeGreaterThanOrEqual(3);
});
