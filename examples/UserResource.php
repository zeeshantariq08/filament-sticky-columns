<?php

declare(strict_types=1);

/**
 * EXAMPLE — UserResource.php
 *
 * Demonstrates all usage patterns for zeeshantariq/filament-sticky-columns.
 * Copy the relevant parts into your own Filament resource.
 *
 * Compatible with Filament v3, v4, and v5.
 */

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ZeeshanTariq\FilamentStickyColumns\Columns\StickyColumn;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ── Pattern 1: StickyColumn drop-in (sticky LEFT by default) ──

                StickyColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('60px'),

                // Auto-computed offset — JS measures the previous column's width
                StickyColumn::make('name')
                    ->searchable()
                    ->sortable(),

                // ── Pattern 2: Manual offset (pixel-perfect control) ──────────

                // Useful when auto-compute isn't accurate (e.g. checkbox columns)
                // StickyColumn::make('id')->sticky(offset: 0),
                // StickyColumn::make('name')->sticky(offset: 60),

                // ── Regular scrollable columns ────────────────────────────────

                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('phone')
                    ->placeholder('—'),

                TextColumn::make('department')
                    ->label('Department'),

                TextColumn::make('role')
                    ->badge(),

                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── Pattern 3: StickyColumn pinned to the RIGHT ───────────────

                StickyColumn::make('status')
                    ->right()                    // shorthand for ->stickyRight()
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger'  => 'inactive',
                        'warning' => 'pending',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'   => 'Active',
                        'inactive' => 'Inactive',
                        'pending'  => 'Pending',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

/*
|--------------------------------------------------------------------------
| Pattern 4: Macro — apply ->sticky() to ANY Filament column type
|--------------------------------------------------------------------------
|
| Register this in App\Providers\AppServiceProvider::boot()
|
|   use Filament\Tables\Columns\Column;
|
|   Column::macro('sticky', function (bool $condition = true, ?int $offset = null) {
|       $this->extraAttributes(fn () => $condition ? [
|           'data-sticky'         => 'left',
|           'data-sticky-offset'  => $offset,
|           'data-sticky-z-index' => 10,
|       ] : [], merge: true);
|       return $this;
|   });
|
|   Column::macro('stickyRight', function (bool $condition = true, ?int $offset = null) {
|       $this->extraAttributes(fn () => $condition ? [
|           'data-sticky'         => 'right',
|           'data-sticky-offset'  => $offset,
|           'data-sticky-z-index' => 10,
|       ] : [], merge: true);
|       return $this;
|   });
|
| Then use on any column type:
|
|   TextColumn::make('name')->sticky(),
|   IconColumn::make('verified')->sticky(),
|   ImageColumn::make('avatar')->sticky(),
|   TextColumn::make('actions')->stickyRight(),
|
*/
