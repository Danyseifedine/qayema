<?php

namespace App\Filament\Admin\Resources\ContactMessages;

use App\Filament\Admin\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Admin\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Models\ContactMessage;
use App\Support\PerRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

/**
 * The inbox: the public contact form and the owners' package requests, open
 * until an admin marks one handled (or applies the package asked for). The
 * menu's badge counts the open ones.
 */
class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $navigationLabel = 'Inbox';

    protected static ?string $modelLabel = 'message';

    protected static ?string $pluralModelLabel = 'inbox';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $open = self::openCount();

        return $open === 0 ? null : (string) $open;
    }

    /** Messages still to answer: the menu's badge and the Open tab's, counted once a request. */
    public static function openCount(): int
    {
        return PerRequest::remember('inbox-open', fn (): int => ContactMessage::query()->open()->count());
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return 'Open messages';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }
}
