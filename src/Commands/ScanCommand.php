<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Commands;

use Asignua\FilamentTranslationManager\Support\Synchronizer;
use Illuminate\Console\Command;

class ScanCommand extends Command
{
    protected $signature = 'translation-manager:scan
        {--prune : Delete rows whose key no longer exists in the code or the lang files}
        {--dry-run : Only report, write nothing}';

    protected $description = 'Find __() / trans() / @lang strings in the code and lang files and add the missing rows to the translation table';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $result = Synchronizer::make()->sync((bool) $this->option('prune'), $dry);

        $this->info(($dry ? '[dry run] ' : '').__('filament-translation-manager::filament-translation-manager.scan.summary', [
            'found' => $result->found,
            'created' => $result->created,
            'filled' => $result->filled,
            'pruned' => $result->pruned,
            'skipped' => $result->skipped,
        ]));

        return self::SUCCESS;
    }
}
