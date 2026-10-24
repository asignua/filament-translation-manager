<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Feature;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Support\TranslationTransfer;
use Asignua\FilamentTranslationManager\Tests\Concerns\MakesProject;
use Asignua\FilamentTranslationManager\Tests\TestCase;

class TransferTest extends TestCase
{
    use MakesProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeProject();
        $this->writeFile('lang/en/messages.php', '<?php return [];');
    }

    protected function tearDown(): void
    {
        $this->destroyProject();

        parent::tearDown();
    }

    private function make(string $group, string $key, array $text): Translation
    {
        $row = new Translation;
        $row->group = $group;
        $row->key = $key;
        $row->text = $text;
        $row->save();

        return $row;
    }

    public function test_export_is_a_flat_map_of_one_locale_with_group_keys_prefixed(): void
    {
        $this->make('*', 'Hello', ['en' => 'Hello', 'uk' => 'Привіт']);
        $this->make('messages', 'checkout.title', ['uk' => 'Оформлення']);
        $this->make('*', 'Only english', ['en' => 'x']);

        $this->assertSame(
            ['Hello' => 'Привіт', 'messages.checkout.title' => 'Оформлення'],
            (new TranslationTransfer)->export('uk'),
        );
    }

    public function test_export_json_keeps_cyrillic_readable(): void
    {
        $this->make('*', 'Hello', ['uk' => 'Привіт']);

        $this->assertStringContainsString('Привіт', (new TranslationTransfer)->exportJson('uk'));
    }

    public function test_import_creates_rows_and_fills_gaps_without_overwriting(): void
    {
        $existing = $this->make('*', 'Hello', ['en' => 'Hello', 'uk' => 'Мій переклад']);
        $gap = $this->make('messages', 'checkout.title', ['en' => 'Checkout']);

        $result = (new TranslationTransfer)->import('uk', [
            'Hello' => 'Привіт',
            'messages.checkout.title' => 'Оформлення',
            'Brand new' => 'Зовсім новий',
            'pkg::file.key' => 'namespaced',
            'Blank' => '',
            'Not a string' => ['x'],
        ]);

        $this->assertSame(['created' => 1, 'updated' => 1, 'skipped' => 4], $result);
        $this->assertSame('Мій переклад', $existing->refresh()->textFor('uk'));
        $this->assertSame('Оформлення', $gap->refresh()->textFor('uk'));
        $this->assertSame(['uk' => 'Зовсім новий'], Translation::query()->where('key', 'Brand new')->firstOrFail()->text);
        $this->assertSame('Checkout', $gap->textFor('en'));
    }

    public function test_import_can_overwrite(): void
    {
        $existing = $this->make('*', 'Hello', ['uk' => 'Старе', 'en' => 'Hello']);

        $result = (new TranslationTransfer)->import('uk', ['Hello' => 'Нове'], overwrite: true);

        $this->assertSame(1, $result['updated']);
        $this->assertSame(['uk' => 'Нове', 'en' => 'Hello'], $existing->refresh()->text);
    }

    public function test_import_flushes_the_served_translation(): void
    {
        $this->make('*', 'Hello', ['uk' => 'Старе']);
        $this->assertSame('Старе', __('Hello', [], 'uk'));

        (new TranslationTransfer)->import('uk', ['Hello' => 'Нове'], overwrite: true);
        app('translator')->setLoaded([]);

        $this->assertSame('Нове', __('Hello', [], 'uk'));
    }

    public function test_export_then_import_round_trips(): void
    {
        $this->make('*', 'Hello', ['uk' => 'Привіт']);
        $this->make('messages', 'a.b', ['uk' => 'Б']);
        $json = (new TranslationTransfer)->exportJson('uk');

        Translation::query()->delete();
        (new TranslationTransfer)->import('uk', json_decode($json, true));

        $this->assertSame(json_decode($json, true), (new TranslationTransfer)->export('uk'));
    }
}
