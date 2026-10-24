<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Support;

/**
 * Turns the string of `__('…')` into the (group, key) pair of a `language_lines` row, the way Laravel's
 * translator reads it:
 *
 *  - `pkg::group.key` is namespaced: not served by the database loader, so not resolvable (null);
 *  - `group.key` where `group` is a real lang file group (or a group that already has rows) is a group row;
 *  - everything else - a sentence, `Hello.`, a dotted JSON key - is a JSON row in group `*` with the full string as the key.
 *    Dots in a key are fine; only `::` is special.
 */
final class KeyResolver
{
    /**
     * @param list<string> $groups the known non-JSON groups
     */
    public function __construct(private readonly array $groups) {}

    /**
     * @return array{0: string, 1: string}|null [group, key]
     */
    public function resolve(string $literal): ?array
    {
        if ($literal === '' || str_contains($literal, '::')) {
            return null;
        }

        $dot = strpos($literal, '.');

        if ($dot !== false && $dot > 0 && $dot < strlen($literal) - 1) {
            $group = substr($literal, 0, $dot);

            if (in_array($group, $this->groups, true)) {
                return [$group, substr($literal, $dot + 1)];
            }
        }

        return ['*', $literal];
    }

    /**
     * A JSON-style key that is really a `group.item` identifier of a lang file that does not exist (`auth.failed`,
     * `ee.Save`, `ee.Publish in this language`): a dotted identifier without spaces, or a lowercase identifier +
     * dot in front of a sentence. Abbreviations (`e.g. this`, `U.S.A.`) and versions (`v1.2.3`) are not.
     * Used by the scan when `scan.skip_group_like_keys` is on.
     */
    public static function looksLikeGroupKey(string $key): bool
    {
        if (str_contains($key, ' ') && preg_match('/^[a-z][a-z0-9_-]+\./', $key) === 1) {
            return true;
        }

        return preg_match('/^[A-Za-z][A-Za-z0-9_-]*(\.[A-Za-z][A-Za-z0-9_-]*)+$/', $key) === 1;
    }

    /**
     * The reverse: the string a row is addressed by in code and in an exported file.
     */
    public static function address(string $group, string $key): string
    {
        return $group === '*' ? $key : $group.'.'.$key;
    }
}
