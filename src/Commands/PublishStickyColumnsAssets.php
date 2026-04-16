<?php

declare(strict_types=1);

namespace ZeeshanTariq\FilamentStickyColumns\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class PublishStickyColumnsAssets extends Command
{
    protected $signature = 'filament-sticky-columns:assets {--force : Overwrite existing files if they exist}';

    protected $description = 'Publish filament-sticky-columns assets (JS/CSS) only.';

    public function handle(Filesystem $files): int
    {
        $version = InstalledVersions::getVersion('zeeshantariq/filament-sticky-columns') ?? 'dev';

        $sourceJs = dirname(__DIR__, 2) . '/resources/dist/filament-sticky-columns.js';
        $sourceCss = dirname(__DIR__, 2) . '/resources/dist/filament-sticky-columns.css';

        if (! $files->exists($sourceJs)) {
            $this->error("Missing source JS: {$sourceJs}");
            return self::FAILURE;
        }

        if (! $files->exists($sourceCss)) {
            $this->error("Missing source CSS: {$sourceCss}");
            return self::FAILURE;
        }

        $publicJsDir = public_path('js/zeeshantariq/filament-sticky-columns');
        $publicCssDir = public_path('css/zeeshantariq/filament-sticky-columns');

        $files->ensureDirectoryExists($publicJsDir);
        $files->ensureDirectoryExists($publicCssDir);

        $destJs = $publicJsDir . "/filament-sticky-columns{$version}.js";
        $destCss = $publicCssDir . "/filament-sticky-columns{$version}.css";

        $overwrite = (bool) $this->option('force');

        foreach ([[$sourceJs, $destJs], [$sourceCss, $destCss]] as [$src, $dest]) {
            if ($files->exists($dest) && ! $overwrite) {
                $this->line("Skipping existing: {$dest} (use --force to overwrite)");
                continue;
            }

            $files->copy($src, $dest);
            $this->info("Published: {$dest}");
        }

        $this->comment('Done. If your browser cached old assets, reload the page.');

        return self::SUCCESS;
    }
}

