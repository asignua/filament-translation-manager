<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Support;

/**
 * The ONE parser of translation literals in source code: `__('…')`, `trans('…')`, `trans_choice('…')`,
 * `@lang('…')`, `Lang::get('…')`, `Lang::choice('…')`.
 *
 * Every scanner uses it. (eEgnith once had two regexes that disagreed on escaped quotes, and the second
 * one wrote the truncated key `Don\` into the lang files on every scan.)
 *
 * Only a literal that is the WHOLE first argument counts: `__('a'.$b)` and `__("Hi $name")` are dynamic and
 * are skipped, because their text is not a key.
 */
final class TranslationLiteral
{
    /**
     * Group 1 is the quote, group 2 the RAW content (escapes still in it - pass it through {@see self::decode()}).
     * `(?:\\.|(?!\1).)+?` takes an escaped pair or any char that is not the opening quote, so `\'` and an
     * apostrophe inside a double-quoted string never end the literal. The trailing look-ahead demands `,` or `)`.
     */
    public static function pattern(): string
    {
        return '/(?:(?<![\w$>:])(?:__|trans_choice|trans)|@lang|\\\\?\bLang::(?:get|choice))\(\s*([\'"])((?:\\\\.|(?!\1).)+?)\1\s*[,)]/s';
    }

    /**
     * The decoded static keys of a source string, in order of appearance (duplicates kept).
     *
     * @return list<string>
     */
    public static function extract(string $source): array
    {
        if (preg_match_all(self::pattern(), $source, $matches, PREG_SET_ORDER) === 0) {
            return [];
        }

        $keys = [];

        foreach ($matches as $match) {
            [, $quote, $raw] = $match;

            if ($quote === '"' && self::interpolates($raw)) {
                continue;
            }

            $key = self::decode($raw, $quote);

            if ($key !== '') {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Single quotes: only `\'` and `\\` (exactly like PHP). Double quotes: the full set (`\n`, `\t`, `\"`, `\$`, `\x41`, `\u{1F600}`),
     * while an unknown escape such as `\'` keeps its backslash, as PHP does.
     */
    public static function decode(string $raw, string $quote): string
    {
        if ($quote === "'") {
            return str_replace(["\\'", '\\\\'], ["'", '\\'], $raw);
        }

        return (string) preg_replace_callback(
            '/\\\\(u\{[0-9A-Fa-f]+\}|x[0-9A-Fa-f]{1,2}|[0-7]{1,3}|.)/s',
            static function (array $m): string {
                $escape = $m[1];

                return match (true) {
                    $escape === 'n' => "\n",
                    $escape === 't' => "\t",
                    $escape === 'r' => "\r",
                    $escape === 'v' => "\v",
                    $escape === 'e' => "\e",
                    $escape === 'f' => "\f",
                    $escape === '\\', $escape === '$', $escape === '"' => $escape,
                    $escape[0] === 'u' && strlen($escape) > 1 => mb_chr((int) hexdec(substr($escape, 2, -1)), 'UTF-8'),
                    $escape[0] === 'x' && strlen($escape) > 1 => chr((int) hexdec(substr($escape, 1))),
                    ctype_digit($escape[0]) => chr(((int) octdec($escape)) & 0xFF),
                    default => $m[0],
                };
            },
            $raw,
        );
    }

    /**
     * A double-quoted literal with `$var` / `{$var}` is not a constant string.
     */
    private static function interpolates(string $raw): bool
    {
        return preg_match('/(?<!\\\\)(?:\\\\\\\\)*(?:\$[A-Za-z_{]|\{\$)/', $raw) === 1;
    }
}
