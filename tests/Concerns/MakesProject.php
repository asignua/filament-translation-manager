<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Concerns;

use Illuminate\Filesystem\Filesystem;

/**
 * A throw-away "project" on disk: code files and lang files the scanner can read.
 */
trait MakesProject
{
    protected string $project;

    protected function makeProject(): void
    {
        $this->project = sys_get_temp_dir().'/tm-'.bin2hex(random_bytes(6));
        mkdir($this->project.'/lang/en', 0777, true);

        app()->useLangPath($this->project.'/lang');
    }

    protected function destroyProject(): void
    {
        (new Filesystem)->deleteDirectory($this->project);
    }

    protected function writeFile(string $relative, string $contents): void
    {
        $path = $this->project.'/'.$relative;

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $contents);
    }

    /**
     * Point the scan at the throw-away project.
     */
    protected function scanProject(): void
    {
        config([
            'filament-translation-manager.scan.paths' => [$this->project.'/app', $this->project.'/views'],
            'filament-translation-manager.scan.exclude' => ['vendor', 'tests'],
        ]);
    }
}
