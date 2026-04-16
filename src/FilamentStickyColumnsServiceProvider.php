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
use ZeeshanTariq\FilamentStickyColumns\Commands\PublishStickyColumnsAssets;

class FilamentStickyColumnsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-sticky-columns';
    private static string $version = 'dev';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(PublishStickyColumnsAssets::class);
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

                if ($supportsMerge) {
                    $this->extraAttributes($attrs, true);
                } else {
                    $this->extraAttributes($attrs);
                }

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

                if ($supportsMerge) {
                    $this->extraAttributes($attrs, true);
                } else {
                    $this->extraAttributes($attrs);
                }

                return $this;
            });
        }

        static::$version = InstalledVersions::getVersion('zeeshantariq/filament-sticky-columns') ?? 'dev';
        $assetId = 'filament-sticky-columns' . static::$version;

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
     * Detect the installed Filament major version.
     */
    public static function filamentMajorVersion(): int
    {
        if (class_exists(\Composer\InstalledVersions::class)) {
            $version = \Composer\InstalledVersions::getVersion('filament/filament');
            if ($version !== null) {
                return (int) $version[0];
            }
        }

        // Fallback: check class existence for known v4/v5 additions
        if (class_exists(\Filament\Tables\Columns\ImageColumn::class)) {
            return 3; // safe default
        }

        return 3;
    }
}
