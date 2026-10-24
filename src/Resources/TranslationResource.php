<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Resources;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Resources\TranslationResource\Pages\CreateTranslation;
use Asignua\FilamentTranslationManager\Resources\TranslationResource\Pages\EditTranslation;
use Asignua\FilamentTranslationManager\Resources\TranslationResource\Pages\ListTranslations;
use Asignua\FilamentTranslationManager\Support\LangFiles;
use Asignua\FilamentTranslationManager\Support\Locales;
use Asignua\FilamentTranslationManager\TranslationManagerPlugin;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

/**
 * The "Translations" table. Closed unless the `manageTranslations` gate (or the plugin's authorize()) lets the user in;
 * deleting needs the stricter `deleteTranslations`.
 *
 * Built to be subclassed: a host overrides `canCreate()`, {@see self::scopeQuery()}, {@see self::extraColumns()} /
 * {@see self::extraRecordActions()} or the pages (which then point at the subclass through their `$resource`), and
 * registers the subclass through `TranslationManagerPlugin::resource()` (or leaves it to the panel's discovery and
 * passes `resource(false)`). Everything below uses `static::` for that.
 */
class TranslationResource extends Resource
{
    public static function getModel(): string
    {
        return TranslationManagerPlugin::resolve()->getModel();
    }

    public static function isPluginActive(): bool
    {
        return Filament::getCurrentPanel()?->hasPlugin(TranslationManagerPlugin::ID) === true;
    }

    public static function canViewAny(): bool
    {
        return static::isPluginActive() && TranslationManagerPlugin::resolve()->canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::isPluginActive() && TranslationManagerPlugin::resolve()->canDelete();
    }

    public static function canDeleteAny(): bool
    {
        return static::isPluginActive() && TranslationManagerPlugin::resolve()->canDelete();
    }

    public static function getNavigationLabel(): string
    {
        return TranslationManagerPlugin::resolve()->getNavigationLabel() ?? __('filament-translation-manager::filament-translation-manager.resource.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('filament-translation-manager::filament-translation-manager.resource.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-translation-manager::filament-translation-manager.resource.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return TranslationManagerPlugin::resolve()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return TranslationManagerPlugin::resolve()->getNavigationSort();
    }

    public static function getNavigationIcon(): string
    {
        return TranslationManagerPlugin::resolve()->getNavigationIcon();
    }

    public static function getNavigationBadge(): ?string
    {
        if (!TranslationManagerPlugin::resolve()->shouldShowNavigationBadge()) {
            return null;
        }

        $count = static::missingCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof Translation ? $record->group.'.'.$record->key : static::getModelLabel();
    }

    /**
     * The list query: the configured groups only (config `groups`). Override {@see self::scopeQuery()} to narrow it further.
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Translation> $query */
        $query = parent::getEloquentQuery();

        /** @phpstan-ignore return.type (Builder is invariant in its model; this is always a Translation query) */
        return static::scopeQuery($query);
    }

    /**
     * @return class-string<Translation>
     */
    protected static function translationModel(): string
    {
        return TranslationManagerPlugin::resolve()->getModel();
    }

    /**
     * Hook for subclasses: every query of the resource (list, counts, badge) goes through it.
     *
     * @param Builder<Translation> $query
     *
     * @return Builder<Translation>
     */
    protected static function scopeQuery(Builder $query): Builder
    {
        return $query->inManagedGroups();
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    /**
     * Rows missing a text in at least one configured locale.
     */
    public static function missingCount(): int
    {
        /** @var Builder<Translation> $query */
        $query = static::getEloquentQuery();

        return static::applyMissing($query, Locales::codes())->count();
    }

    /**
     * Whether the group column and filter are useful: not when the plugin is limited to a single group.
     */
    public static function showsGroup(): bool
    {
        return count(static::translationModel()::managedGroups() ?? [null, null]) !== 1;
    }

    /**
     * Columns a subclass adds after the locale columns.
     *
     * @return list<\Filament\Tables\Columns\Column>
     */
    protected static function extraColumns(): array
    {
        return [];
    }

    /**
     * Row actions a subclass adds to the "three dots" group, after Edit and before Delete.
     *
     * @return list<Action>
     */
    protected static function extraRecordActions(): array
    {
        return [];
    }

    /**
     * @param Builder<Translation> $query
     * @param list<string>         $locales rows missing in ANY of these
     *
     * @return Builder<Translation>
     */
    public static function applyMissing(Builder $query, array $locales): Builder
    {
        return $query->missingInAny($locales);
    }

    public static function form(Schema $schema): Schema
    {
        $files = new LangFiles;
        $fields = [];

        foreach (Locales::all() as $code => $locale) {
            $fields[] = Textarea::make('text.'.$code)
                ->label(Locales::label($code))
                ->rows(2)
                ->autosize()
                ->helperText(function (?Translation $record) use ($files, $code): ?string {
                    $default = $record === null ? null : $files->value($record->group, $record->key, $code);

                    return $default === null
                        ? null
                        : (string) __('filament-translation-manager::filament-translation-manager.form.file_default', ['text' => $default]);
                });
        }

        return $schema->components([
            Section::make(__('filament-translation-manager::filament-translation-manager.form.identity'))
                ->columns(2)
                ->schema([
                    TextInput::make('group')
                        ->label(__('filament-translation-manager::filament-translation-manager.table.group'))
                        ->helperText(__('filament-translation-manager::filament-translation-manager.form.group_help'))
                        ->required()
                        ->maxLength(255)
                        ->default(static fn (): string => static::translationModel()::managedGroups()[0] ?? '*')
                        ->disabled(static fn (string $operation): bool => $operation === 'edit' || count(static::translationModel()::managedGroups() ?? []) === 1),
                    TextInput::make('key')
                        ->label(__('filament-translation-manager::filament-translation-manager.table.key'))
                        ->required()
                        ->maxLength(255)
                        ->disabledOn('edit')
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: static fn (Unique $rule, \Filament\Schemas\Components\Utilities\Get $get): Unique => $rule->where('group', $get('group')),
                        ),
                ]),
            Section::make(__('filament-translation-manager::filament-translation-manager.form.texts'))
                ->schema($fields),
        ]);
    }

    /**
     * LIKE over the key and the JSON text. `!` escapes the wildcards: SQLite has no default escape character,
     * and MySQL's `\` would need doubling. The SQL is a fixed template with grammar-quoted columns; the user's
     * text only travels as a binding.
     *
     * @param Builder<Translation> $query
     *
     * @return Builder<Translation>
     */
    public static function searchKeyAndText(Builder $query, string $search): Builder
    {
        $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
        $grammar = $query->getQuery()->getGrammar();
        $key = $grammar->wrap('key')." like ? escape '!'";
        $text = $grammar->wrap('text')." like ? escape '!'";

        /** @phpstan-ignore argument.type, argument.type */
        return $query->where(fn (Builder $q) => $q->whereRaw($key, [$term])->orWhereRaw($text, [$term]));
    }

    public static function table(Table $table): Table
    {
        $columns = [
            TextColumn::make('group')
                ->label(__('filament-translation-manager::filament-translation-manager.table.group'))
                ->badge()
                ->color('gray')
                ->sortable()
                ->visible(static fn (): bool => static::showsGroup()),
            TextColumn::make('key')
                ->label(__('filament-translation-manager::filament-translation-manager.table.key'))
                ->wrap()
                ->limit(70)
                ->sortable()
                // One search box across the key AND every text.
                ->searchable(query: static function (Builder $query, string $search): Builder {
                    return static::searchKeyAndText($query, $search);
                }),
        ];

        foreach (Locales::all() as $code => $locale) {
            $columns[] = TextColumn::make('text.'.$code)
                ->label(Locales::label($code))
                ->limit(50)
                ->wrap()
                ->placeholder(__('filament-translation-manager::filament-translation-manager.table.missing'))
                ->color(static fn (?string $state): string => $state === null || $state === '' ? 'danger' : 'gray')
                ->tooltip(static fn (?string $state): ?string => $state !== null && mb_strlen($state) > 50 ? $state : null);
        }

        if (Translation::statusEnabled()) {
            array_splice($columns, 2, 0, [
                TextColumn::make('status')
                    ->label(__('filament-translation-manager::filament-translation-manager.status.label'))
                    ->badge()
                    ->getStateUsing(static fn (Translation $record): string => $record->status())
                    ->formatStateUsing(static fn (string $state): string => __('filament-translation-manager::filament-translation-manager.status.'.$state))
                    ->color(static fn (string $state): string => match ($state) {
                        Translation::STATUS_ORPHANED => 'danger',
                        Translation::STATUS_MISSING => 'warning',
                        default => 'success',
                    }),
            ]);
        }

        array_push($columns, ...static::extraColumns());

        $filters = [
            SelectFilter::make('group')
                ->label(__('filament-translation-manager::filament-translation-manager.table.group'))
                ->options(static fn (): array => static::getEloquentQuery()->reorder()->distinct()->orderBy('group')->pluck('group', 'group')->all())
                ->visible(static fn (): bool => static::showsGroup()),
            SelectFilter::make('missing_in')
                ->label(__('filament-translation-manager::filament-translation-manager.filters.missing_in'))
                ->options(Locales::options())
                ->multiple()
                ->query(static function (Builder $query, array $data): Builder {
                    /** @var list<string> $selected */
                    $selected = array_values(array_filter((array) ($data['values'] ?? [])));

                    /** @var Builder<Translation> $query */
                    return $selected === [] ? $query : static::applyMissing($query, $selected);
                }),
            Filter::make('missing')
                ->label(__('filament-translation-manager::filament-translation-manager.filters.missing_any'))
                ->query(static function (Builder $query): Builder {
                    /** @var Builder<Translation> $query */
                    return static::applyMissing($query, Locales::codes());
                }),
        ];

        if (Translation::statusEnabled()) {
            $filters[] = Filter::make('orphaned')
                ->label(__('filament-translation-manager::filament-translation-manager.filters.orphaned'))
                ->query(static function (Builder $query): Builder {
                    /** @var Builder<Translation> $query */
                    return $query->orphaned();
                });
            $filters[] = Filter::make('confirmed')
                ->label(__('filament-translation-manager::filament-translation-manager.filters.confirmed'))
                ->query(static function (Builder $query): Builder {
                    /** @var Builder<Translation> $query */
                    return $query->confirmed();
                });
        }

        return $table
            ->columns($columns)
            ->defaultSort('key')
            ->paginated([25, 50, 100])
            ->filters($filters)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ...static::statusActions(),
                    ...static::extraRecordActions(),
                    DeleteAction::make()->visible(static fn (): bool => static::canDeleteAny()),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(static fn (): bool => static::canDeleteAny()),
                ]),
            ]);
    }

    /**
     * "Confirm" / "Remove confirmation": an editor vouches that the row is right as it is, which takes it out of "missing"
     * (the case: a text that equals the key on purpose).
     *
     * @return list<Action>
     */
    protected static function statusActions(): array
    {
        if (!Translation::statusEnabled()) {
            return [];
        }

        return [
            Action::make('confirm')
                ->label(static fn (Translation $record): string => __('filament-translation-manager::filament-translation-manager.actions.'.($record->isConfirmed() ? 'unconfirm' : 'confirm')))
                ->icon(static fn (Translation $record): Heroicon => $record->isConfirmed() ? Heroicon::OutlinedXCircle : Heroicon::OutlinedCheckCircle)
                ->color(static fn (Translation $record): string => $record->isConfirmed() ? 'gray' : 'success')
                ->visible(static fn (Translation $record): bool => static::canEdit($record))
                ->action(static function (Translation $record): void {
                    // Through the model: spatie's cache is flushed on save.
                    $record->confirmed_at = $record->isConfirmed() ? null : now();
                    $record->save();
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTranslations::route('/'),
            'create' => CreateTranslation::route('/create'),
            'edit' => EditTranslation::route('/{record}/edit'),
        ];
    }
}
