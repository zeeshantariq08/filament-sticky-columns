<?php

declare(strict_types=1);

namespace ZeeshanTariq\FilamentStickyColumns;

use Composer\InstalledVersions;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Tables\Columns\Column;
use ReflectionMethod;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentStickyColumnsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-sticky-columns';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews();
    }

    public function packageBooted(): void
    {
        $supportsMerge = false;
        if (class_exists(Column::class) && method_exists(Column::class, 'extraAttributes')) {
            $supportsMerge = (new ReflectionMethod(Column::class, 'extraAttributes'))->getNumberOfParameters() >= 2;
        }

        if (class_exists(Column::class) && !Column::hasMacro('sticky')) {
            Column::macro('sticky', function (bool $condition = true, ?int $offset = null, ?int $zIndex = null) use ($supportsMerge) {
                if (! $condition) {
                    return $this;
                }

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
        $safeVersion     = preg_replace('/[^0-9A-Za-z]/', '-', (string) $composerVersion) ?: 'dev';

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
