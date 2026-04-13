<?php

declare(strict_types=1);

namespace ZeeshanTariq\FilamentStickyColumns\Columns;

use Filament\Tables\Columns\TextColumn;
use ZeeshanTariq\FilamentStickyColumns\Concerns\HasStickyColumn;

/**
 * Drop-in sticky column — works exactly like Filament's TextColumn
 * but pinned to the LEFT edge by default.
 *
 * Compatible with Filament v3, v4, and v5.
 *
 * Usage:
 *   StickyColumn::make('name')              // sticky left
 *   StickyColumn::make('name')->right()     // sticky right
 *   StickyColumn::make('name')->sticky(offset: 60)
 */
class StickyColumn extends TextColumn
{
    use HasStickyColumn;

    protected function setUp(): void
    {
        parent::setUp();

        // Sticky LEFT by default when using this class.
        $this->sticky();
    }

    /**
     * Short-hand: pin to the RIGHT edge.
     */
    public function right(bool $condition = true): static
    {
        return $this->stickyRight($condition);
    }
}
