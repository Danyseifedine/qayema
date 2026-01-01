<?php

namespace App\Filament\Admin\Resources\MenuSettings\Schemas;

use App\Models\Setting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MenuSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('menu_id')
                    ->label('Menu')
                    ->relationship('menu', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
                Select::make('setting_id')
                    ->label('Setting')
                    ->relationship('setting', 'title')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                        return $rule->where('menu_id', $get('menu_id'));
                    })
                    ->helperText('Select the setting to configure for this menu')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Reset value when setting changes
                        $set('value', null);
                    })
                    ->columnSpanFull(),
                TextInput::make('value')
                    ->label('Value')
                    ->helperText('Enter the setting value')
                    ->visible(function ($get, $record) {
                        $settingId = $get('setting_id');
                        if (! $settingId) {
                            return false;
                        }

                        $setting = $record?->setting ?? Setting::find($settingId);
                        if (! $setting) {
                            return false;
                        }

                        return in_array($setting->type, ['string', 'integer', 'float']);
                    })
                    ->numeric(function ($get, $record) {
                        $settingId = $get('setting_id');
                        if (! $settingId) {
                            return false;
                        }

                        $setting = $record?->setting ?? Setting::find($settingId);

                        return $setting && in_array($setting->type, ['integer', 'float']);
                    })
                    ->required(function ($get, $record) {
                        $settingId = $get('setting_id');
                        if (! $settingId) {
                            return false;
                        }

                        $setting = $record?->setting ?? Setting::find($settingId);

                        return $setting && $setting->type !== 'boolean';
                    }),
                Textarea::make('value')
                    ->label('Value')
                    ->helperText('Enter JSON value (e.g., {"key": "value"})')
                    ->rows(4)
                    ->visible(function ($get, $record) {
                        $settingId = $get('setting_id');
                        if (! $settingId) {
                            return false;
                        }

                        $setting = $record?->setting ?? Setting::find($settingId);

                        return $setting && $setting->type === 'json';
                    })
                    ->required(),
                Toggle::make('value')
                    ->label('Value')
                    ->helperText('Toggle the setting on or off')
                    ->visible(function ($get, $record) {
                        $settingId = $get('setting_id');
                        if (! $settingId) {
                            return false;
                        }

                        $setting = $record?->setting ?? Setting::find($settingId);

                        return $setting && $setting->type === 'boolean';
                    })
                    ->default(false),
            ]);
    }
}
