<?php

declare(strict_types=1);

namespace ZeeshanTariq\FilamentStickyColumns;

use Composer\InstalledVersions;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Filament\Tables\View\TablesRenderHook;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use ZeeshanTariq\FilamentStickyColumns\Support\StickyableManager;
use ZeeshanTariq\FilamentStickyColumns\Support\StickyableRegistry;

class FilamentStickyColumnsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-sticky-columns';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile();
    }

    public function packageBooted(): void
    {
        $supportsMerge = false;
        if (class_exists(Column::class) && method_exists(Column::class, 'extraAttributes')) {
            $supportsMerge = (new ReflectionMethod(Column::class, 'extraAttributes'))->getNumberOfParameters() >= 2;
        }

        if (class_exists(Column::class) && ! Column::hasMacro('sticky')) {
            Column::macro('sticky', function (bool $condition = true, ?int $offset = null, ?int $zIndex = null) use ($supportsMerge) {
                if (! $condition) {
                    return $this;
                }

                StickyableRegistry::markForced($this, 'left');

                $attrs = [
                    'data-sticky'         => 'left',
                    'data-sticky-z-index' => $zIndex ?? config('filament-sticky-columns.z_index', 10),
                ];

                if ($offset !== null) {
                    $attrs['data-sticky-offset'] = $offset;
                }

                StickyAttributes::applyToColumn($this, $attrs, $supportsMerge);

                return $this;
            });

            Column::macro('stickyRight', function (bool $condition = true, ?int $offset = null, ?int $zIndex = null) use ($supportsMerge) {
                if (! $condition) {
                    return $this;
                }

                StickyableRegistry::markForced($this, 'right');

                $attrs = [
                    'data-sticky'         => 'right',
                    'data-sticky-z-index' => $zIndex ?? config('filament-sticky-columns.z_index', 10),
                ];

                if ($offset !== null) {
                    $attrs['data-sticky-offset'] = $offset;
                }

                StickyAttributes::applyToColumn($this, $attrs, $supportsMerge);

                return $this;
            });
        }

        if (class_exists(Column::class) && ! Column::hasMacro('stickyable')) {
            Column::macro('stickyable', function (bool $condition = true, string $side = 'left') use ($supportsMerge) {
                if (! $condition) {
                    return $this;
                }

                // Filament v4+ feature — no-op on v3.
                if (FilamentStickyColumnsServiceProvider::filamentMajorVersion() < 4) {
                    return $this;
                }

                $side = $side === 'right' ? 'right' : 'left';
                StickyableRegistry::markStickyable($this, $side);

                /** @var Column $column */
                $column = $this;

                $attrs = function () use ($side, $column): array {
                    try {
                        $livewire = $column->getLivewire();
                    } catch (\Throwable) {
                        return [];
                    }

                    if (! method_exists($livewire, 'isTableColumnStickyable')) {
                        return [];
                    }

                    if (! $livewire->isTableColumnStickyable($column->getName())) {
                        return [];
                    }

                    return [
                        'data-sticky'         => $side,
                        'data-sticky-z-index' => config('filament-sticky-columns.z_index', 10),
                    ];
                };

                StickyAttributes::applyToColumn($this, $attrs, $supportsMerge);

                return $this;
            });
        }

        if (class_exists(Table::class) && ! Table::hasMacro('stickyableColumns')) {
            Table::macro('stickyableColumns', function (bool $condition = true) {
                if (! $condition) {
                    return $this;
                }

                if (FilamentStickyColumnsServiceProvider::filamentMajorVersion() < 4) {
                    return $this;
                }

                StickyableRegistry::enableTable($this);

                return $this;
            });
        }

        if (self::filamentMajorVersion() >= 4 && class_exists(FilamentView::class)) {
            FilamentView::registerRenderHook(
                TablesRenderHook::TOOLBAR_SEARCH_AFTER,
                function (): string {
                    $livewire = Livewire::current();

                    if (! is_object($livewire) || ! method_exists($livewire, 'getTable')) {
                        return '';
                    }

                    if (! method_exists($livewire, 'isTableColumnStickyable')) {
                        return '';
                    }

                    try {
                        $table = $livewire->getTable();
                    } catch (\Throwable) {
                        return '';
                    }

                    if (! $table instanceof Table || ! StickyableRegistry::isTableEnabled($table)) {
                        return '';
                    }

                    return view('filament-sticky-columns::components.stickyable-trigger', [
                        'columns' => StickyableManager::optionsForLivewire($livewire),
                    ])->render();
                },
            );
        }

        $assetId = self::filamentAssetId();

        FilamentAsset::register(
            assets: [
                Css::make(
                    id: $assetId,
                    path: __DIR__ . '/../resources/dist/filament-sticky-columns.css',
                ),
                Js::make(
                    id: $assetId,
                    path: __DIR__ . '/../resources/dist/filament-sticky-columns.js',
                ),
            ],
            package: 'zeeshantariq/filament-sticky-columns',
        );
    }

    /**
     * Stable Filament asset id used for public filenames. Includes the installed
     * Composer version plus dist file mtimes so browsers fetch fresh CSS/JS after
     * `composer update` / reinstall without relying on a hard refresh.
     */
    public static function filamentAssetId(): string
    {
        $distDir = dirname(__DIR__) . '/resources/dist';
        $cssFile  = $distDir . '/filament-sticky-columns.css';
        $jsFile   = $distDir . '/filament-sticky-columns.js';

        $mtime = max(
            is_file($cssFile) ? (int) filemtime($cssFile) : 0,
            is_file($jsFile) ? (int) filemtime($jsFile) : 0,
        );

        $composerVersion = InstalledVersions::getVersion('zeeshantariq/filament-sticky-columns') ?? 'dev';
        $safeVersion     = preg_replace('/[^0-9A-Za-z.\-]/', '-', (string) $composerVersion) ?: 'dev';

        return 'filament-sticky-columns-' . $safeVersion . '-' . (string) $mtime;
    }

    /**
     * Detect the installed Filament major version.
     */
    public static function filamentMajorVersion(): int
    {
        if (class_exists(\Composer\InstalledVersions::class)) {
            $version = \Composer\InstalledVersions::getVersion('filament/filament');
            if ($version !== null && preg_match('/^v?(\d+)/', $version, $matches)) {
                return (int) $matches[1];
            }
        }

        // Fallback: check class existence for known v4/v5 additions
        if (class_exists(\Filament\Tables\Columns\ImageColumn::class)) {
            return 3; // safe default
        }

        return 3;
    }
}
