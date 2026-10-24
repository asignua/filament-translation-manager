<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Support;

use Symfony\Component\Finder\Finder;

/**
 * Finds translation literals in the configured source folders (see {@see TranslationLiteral}).
 */
final class CodeScanner
{
    /**
     * @param list<string> $paths      folders; relative ones resolve against base_path()
     * @param list<string> $extensions
     * @param list<string> $exclude    folder names skipped at any depth (`vendor`, `tests`, …)
     */
    public function __construct(
        private readonly array $paths,
        private readonly array $extensions = ['php'],
        private readonly array $exclude = ['vendor', 'node_modules', 'tests', 'storage', 'cache'],
        private readonly ?string $basePath = null,
    ) {}

    public static function fromConfig(): self
    {
        /** @var list<string> $paths */
        $paths = array_values((array) config('filament-translation-manager.scan.paths', ['app', 'resources/views', 'routes']));
        /** @var list<string> $extensions */
        $extensions = array_values((array) config('filament-translation-manager.scan.extensions', ['php']));
        /** @var list<string> $exclude */
        $exclude = array_values((array) config('filament-translation-manager.scan.exclude', ['vendor', 'node_modules', 'tests']));

        return new self($paths, $extensions, $exclude);
    }

    /**
     * @return list<string> the unique decoded literals
     */
    public function literals(): array
    {
        $keys = [];

        foreach ($this->paths as $path) {
            $directory = str_starts_with($path, '/') ? $path : ($this->basePath ?? base_path()).'/'.ltrim($path, './');

            if (!is_dir($directory)) {
                continue;
            }

            $finder = (new Finder)->files()->in($directory);

            foreach ($this->extensions as $extension) {
                $finder->name('*.'.ltrim($extension, '.'));
            }

            if ($this->exclude !== []) {
                $finder->notPath('#(^|/)('.implode('|', array_map(static fn (string $dir): string => preg_quote($dir, '#'), $this->exclude)).')(/|$)#');
            }

            foreach ($finder as $file) {
                foreach (TranslationLiteral::extract($file->getContents()) as $key) {
                    $keys[$key] = true;
                }
            }
        }

        return array_map('strval', array_keys($keys));
    }
}
