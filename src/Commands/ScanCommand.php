<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Commands;

use Asignua\FilamentTranslationManager\Support\Synchronizer;
use Illuminate\Console\Command;

class ScanCommand extends Command
{
    protected $signature = 'translation-manager:scan
        {--prune : Mark (config prune.mode = mark) or delete (delete) rows whose key no longer exists in the code or the lang files}
        {--dry-run : Only report, write nothing}';

    protected $description = 'Find __() / trans() / @lang strings in the code and lang files and add the missing rows to the translation table';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $result = Synchronizer::make()->sync((bool) $this->option('prune'), $dry);

        $this->info(($dry ? '[dry run] ' : '').$result->summary());

        return self::SUCCESS;
    }
}
