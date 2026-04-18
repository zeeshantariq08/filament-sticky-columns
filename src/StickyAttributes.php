<?php

declare(strict_types=1);

namespace ZeeshanTariq\FilamentStickyColumns;

use Filament\Tables\Columns\Column;

/**
 * Filament v4+ renders <th>/<td> via {@see Column::extraHeaderAttributes()} /
 * {@see Column::extraCellAttributes()}, while {@see Column::extraAttributes()} is for
 * inner column markup (e.g. TextColumn's fi-ta-text wrapper). Sticky must target cells.
 */
final class StickyAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function applyToColumn(Column $column, array $attributes, bool $merge): void
    {
        if (method_exists($column, 'extraCellAttributes')) {
            $column->extraCellAttributes($attributes, $merge);
        }

        if (method_exists($column, 'extraHeaderAttributes')) {
            $column->extraHeaderAttributes($attributes, $merge);
        }

        if ($merge) {
            $column->extraAttributes($attributes, true);
        } else {
            $column->extraAttributes($attributes);
        }
    }
}
