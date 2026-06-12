<?php

namespace App\Filament\Admin\Resources\BlockedIps;

use App\Filament\Admin\Resources\BlockedIps\Pages\CreateBlockedIp;
use App\Filament\Admin\Resources\BlockedIps\Pages\EditBlockedIp;
use App\Filament\Admin\Resources\BlockedIps\Pages\ListBlockedIps;
use App\Filament\Admin\Resources\BlockedIps\Schemas\BlockedIpForm;
use App\Filament\Admin\Resources\BlockedIps\Tables\BlockedIpsTable;
use App\Models\BlockedIp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BlockedIpResource extends Resource
{
    protected static ?string $model = BlockedIp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static ?string $navigationLabel = 'Blocked IPs';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 25;

    protected static ?string $recordTitleAttribute = 'ip';

    public static function form(Schema $schema): Schema
    {
        return BlockedIpForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlockedIpsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlockedIps::route('/'),
            'create' => CreateBlockedIp::route('/create'),
            'edit' => EditBlockedIp::route('/{record}/edit'),
        ];
    }
}
