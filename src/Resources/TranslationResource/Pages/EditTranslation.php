<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Resources\TranslationResource\Pages;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Resources\TranslationResource;
use Asignua\FilamentTranslationManager\Support\TextFilter;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTranslation extends EditRecord
{
    protected static string $resource = TranslationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->visible(static fn (): bool => TranslationResource::canDeleteAny())];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Translation $record */
        $record = $this->getRecord();

        return TextFilter::clean($data, $record->text);
    }
}
