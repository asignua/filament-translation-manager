<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Unit;

use Asignua\FilamentTranslationManager\Support\TranslationLiteral;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TranslationLiteralTest extends TestCase
{
    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function sources(): array
    {
        return [
            'single quotes' => ["__('Hello world')", ['Hello world']],
            'double quotes' => ['__("Hello world")', ['Hello world']],
            'escaped single quote' => ["__('Don\\'t change')", ["Don't change"]],
            'apostrophe in double quotes' => ['__("Don\'t change")', ["Don't change"]],
            'double quote in single quotes' => ["__('Say \"hi\"')", ['Say "hi"']],
            'escaped double quote' => ['__("Say \\"hi\\"")', ['Say "hi"']],
            'escaped backslash' => ["__('a\\\\b')", ['a\\b']],
            'newline escape in double quotes' => ['__("one\\ntwo")', ["one\ntwo"]],
            'newline escape stays literal in single quotes' => ["__('one\\ntwo')", ['one\\ntwo']],
            'unicode escape' => ['__("caf\\u{E9}")', ['café']],
            'cyrillic' => ["__('Додати в кошик')", ['Додати в кошик']],
            'multiline call' => ["__(\n    'Spread over lines',\n    ['a' => 1]\n)", ['Spread over lines']],
            'multiline string' => ["__('first\nsecond')", ["first\nsecond"]],
            'with replacements' => ["__('Hello :name', ['name' => \$n])", ['Hello :name']],
            'trans' => ["trans('Hello')", ['Hello']],
            'trans_choice' => ["trans_choice('apples', 3)", ['apples']],
            'blade lang directive' => ["@lang('Hello')", ['Hello']],
            'blade echo' => ["{{ __('Hello') }}", ['Hello']],
            'Lang::get' => ["Lang::get('Hello')", ['Hello']],
            'fqcn Lang::get' => ["\\Lang::get('Hello')", ['Hello']],
            'Lang::choice' => ["Illuminate\\Support\\Facades\\Lang::choice('apples', 2)", ['apples']],
            'namespaced key' => ["__('pkg::group.key')", ['pkg::group.key']],
            'dotted json-style key' => ["__('Mr. Smith.')", ['Mr. Smith.']],
            'group key' => ["__('auth.failed')", ['auth.failed']],
            'two on a line' => ["__('A') . __('B')", ['A', 'B']],
            'whitespace before the quote' => ["__(  'Spaced'  )", ['Spaced']],
            'method named trans is not a helper' => ["\$translator->trans('No')", []],
            'static method named trans is not a helper' => ["Foo::trans('No')", []],
            'a function that merely ends in trans' => ["mytrans('No')", []],
            'concatenation is dynamic' => ["__('a' . \$b)", []],
            'variable' => ['__($key)', []],
            'interpolation is dynamic' => ['__("Hi $name")', []],
            'brace interpolation is dynamic' => ['__("Hi {$user->name}")', []],
            'escaped dollar is static' => ['__("Costs \\$5")', ['Costs $5']],
            'empty string' => ["__('')", []],
        ];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('sources')]
    public function test_it_extracts_static_literals(string $source, array $expected): void
    {
        $this->assertSame($expected, TranslationLiteral::extract($source));
    }

    public function test_the_apostrophe_regression_does_not_truncate_the_key(): void
    {
        // The eEgnith bug: a looser regex stopped at the escaped quote and produced the key `Don\`.
        $keys = TranslationLiteral::extract("__('Don\\'t change'), __('Plain')");

        $this->assertSame(["Don't change", 'Plain'], $keys);
    }

    public function test_decode_single_quotes_only_knows_two_escapes(): void
    {
        $this->assertSame("it's \\n", TranslationLiteral::decode("it\\'s \\n", "'"));
    }

    public function test_decode_double_quotes_keeps_unknown_escapes(): void
    {
        $this->assertSame("it\\'s\t", TranslationLiteral::decode("it\\'s\\t", '"'));
    }
}
