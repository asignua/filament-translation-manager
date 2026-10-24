<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Resources\TranslationResource\Pages;

use Asignua\FilamentTranslationManager\Pages\QuickTranslate;
use Asignua\FilamentTranslationManager\Resources\TranslationResource;
use Asignua\FilamentTranslationManager\Support\Locales;
use Asignua\FilamentTranslationManager\Support\Synchronizer;
use Asignua\FilamentTranslationManager\Support\TranslationTransfer;
use Asignua\FilamentTranslationManager\TranslationManagerPlugin;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListTranslations extends ListRecords
{
    protected static string $resource = TranslationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->quickTranslateAction(),
            $this->scanAction(),
            $this->exportAction(),
            $this->importAction(),
            CreateAction::make(),
        ];
    }

    protected function guard(): void
    {
        abort_unless(TranslationManagerPlugin::resolve()->canAccess(), 403);
    }

    protected function quickTranslateAction(): Action
    {
        return Action::make('quickTranslate')
            ->label(__('filament-translation-manager::filament-translation-manager.actions.quick_translate'))
            ->icon(Heroicon::OutlinedBolt)
            ->color('gray')
            ->url(fn (): string => QuickTranslate::getUrl())
            ->visible(fn (): bool => TranslationManagerPlugin::resolve()->isQuickTranslateEnabled());
    }

    protected function scanAction(): Action
    {
        return Action::make('scan')
            ->label(__('filament-translation-manager::filament-translation-manager.actions.scan'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription(__('filament-translation-manager::filament-translation-manager.actions.scan_description'))
            ->schema([
                Toggle::make('prune')
                    ->label(__('filament-translation-manager::filament-translation-manager.actions.prune'))
                    ->helperText(__('filament-translation-manager::filament-translation-manager.actions.prune_help'))
                    ->visible(fn (): bool => TranslationManagerPlugin::resolve()->canDelete()),
            ])
            ->action(function (array $data): void {
                $this->guard();

                $prune = (bool) ($data['prune'] ?? false) && TranslationManagerPlugin::resolve()->canDelete();
                $result = Synchronizer::make()->sync($prune);

                Notification::make()
                    ->success()
                    ->title(__('filament-translation-manager::filament-translation-manager.scan.done'))
                    ->body(__('filament-translation-manager::filament-translation-manager.scan.summary', [
                        'found' => $result->found,
                        'created' => $result->created,
                        'filled' => $result->filled,
                        'pruned' => $result->pruned,
                        'skipped' => $result->skipped,
                    ]))
                    ->send();
            });
    }

    protected function exportAction(): Action
    {
        return Action::make('export')
            ->label(__('filament-translation-manager::filament-translation-manager.actions.export'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->modalWidth('md')
            ->schema([
                Select::make('locale')
                    ->label(__('filament-translation-manager::filament-translation-manager.fields.locale'))
                    ->options(Locales::options())
                    ->default(Locales::default())
                    ->required(),
            ])
            ->action(function (array $data): StreamedResponse {
                $this->guard();

                $locale = (string) $data['locale'];
                abort_unless(in_array($locale, Locales::codes(), true), 422);
                $json = (new TranslationTransfer)->exportJson($locale);

                return response()->streamDownload(static function () use ($json): void {
                    echo $json;
                }, $locale.'.json', ['Content-Type' => 'application/json']);
            });
    }

    protected function importAction(): Action
    {
        return Action::make('import')
            ->label(__('filament-translation-manager::filament-translation-manager.actions.import'))
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->modalWidth('md')
            ->schema([
                Select::make('locale')
                    ->label(__('filament-translation-manager::filament-translation-manager.fields.locale'))
                    ->options(Locales::options())
                    ->default(Locales::default())
                    ->required(),
                FileUpload::make('file')
                    ->label(__('filament-translation-manager::filament-translation-manager.fields.file'))
                    ->helperText(__('filament-translation-manager::filament-translation-manager.actions.import_help'))
                    ->acceptedFileTypes(['application/json', 'text/plain', '.json'])
                    ->storeFiles(false)
                    ->required(),
                Toggle::make('overwrite')
                    ->label(__('filament-translation-manager::filament-translation-manager.actions.overwrite')),
            ])
            ->action(function (array $data): void {
                $this->guard();

                $locale = (string) $data['locale'];
                abort_unless(in_array($locale, Locales::codes(), true), 422);

                $file = $data['file'] ?? null;
                $contents = $file instanceof TemporaryUploadedFile ? (string) file_get_contents($file->getRealPath()) : '';
                $map = json_decode($contents, true);

                if (!is_array($map)) {
                    Notification::make()
                        ->danger()
                        ->title(__('filament-translation-manager::filament-translation-manager.import.invalid'))
                        ->send();

                    return;
                }

                $result = (new TranslationTransfer)->import($locale, $map, (bool) ($data['overwrite'] ?? false));

                Notification::make()
                    ->success()
                    ->title(__('filament-translation-manager::filament-translation-manager.import.done'))
                    ->body(__('filament-translation-manager::filament-translation-manager.import.summary', $result))
                    ->send();
            });
    }
}
