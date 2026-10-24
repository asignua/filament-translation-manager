<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Support;

use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;

/**
 * Reads the project's own lang files (`lang/<locale>/<group>.php` and `lang/<locale>.json`) - the "default" a
 * database row overrides. Package files under `lang/vendor` are not read: spatie's DB loader serves only
 * non-namespaced groups.
 */
final class LangFiles
{
    /** @var array<string, array<string, string>> */
    private array $cache = [];

    public function __construct(private readonly ?string $path = null) {}

    public function path(): string
    {
        return $this->path ?? lang_path();
    }

    /**
     * Lang file groups (`auth`, `validation`, …), the names of the `.php` files in any locale directory.
     *
     * @return list<string>
     */
    public function groups(): array
    {
        $groups = [];

        foreach ($this->localeDirectories() as $directory) {
            foreach ((new Finder)->files()->in($directory)->depth(0)->name('*.php') as $file) {
                $groups[$file->getFilenameWithoutExtension()] = true;
            }
        }

        return array_keys($groups);
    }

    /**
     * Every string of a locale, flattened: `['*' => ['Hello' => '…'], 'auth' => ['failed' => '…']]`.
     *
     * @return array<string, array<string, string>>
     */
    public function lines(string $locale): array
    {
        $lines = [];
        $json = $this->path().'/'.$locale.'.json';

        if (is_file($json)) {
            $decoded = json_decode((string) file_get_contents($json), true);

            foreach (is_array($decoded) ? $decoded : [] as $key => $value) {
                if (is_string($value)) {
                    $lines['*'][(string) $key] = $value;
                }
            }
        }

        $directory = $this->path().'/'.$locale;

        if (is_dir($directory)) {
            foreach ((new Finder)->files()->in($directory)->depth(0)->name('*.php') as $file) {
                $group = $file->getFilenameWithoutExtension();
                $data = (static fn (string $path): mixed => require $path)($file->getRealPath());

                foreach (is_array($data) ? Arr::dot($data) : [] as $key => $value) {
                    if (is_string($value)) {
                        $lines[$group][(string) $key] = $value;
                    }
                }
            }
        }

        return $lines;
    }

    /**
     * The file text of one line (used as the hint in the edit form), or null.
     */
    public function value(string $group, string $key, string $locale): ?string
    {
        return ($this->cache[$locale] ??= $this->flat($locale))[$group."\0".$key] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function flat(string $locale): array
    {
        $flat = [];

        foreach ($this->lines($locale) as $group => $lines) {
            foreach ($lines as $key => $value) {
                $flat[$group."\0".$key] = $value;
            }
        }

        return $flat;
    }

    /**
     * @return list<string>
     */
    private function localeDirectories(): array
    {
        if (!is_dir($this->path())) {
            return [];
        }

        $directories = [];

        foreach ((new Finder)->directories()->in($this->path())->depth(0)->notName('vendor') as $directory) {
            $directories[] = $directory->getRealPath();
        }

        return $directories;
    }
}
