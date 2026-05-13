<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }

    public function infolist(Schema $schema): Schema
    {
        /** @var User $user */
        $user = $this->record;
        $restaurant = $user->restaurant;

        return $schema->components([

            // ── Account ──────────────────────────────────────
            Section::make('Account')
                ->columns(3)
                ->schema([
                    TextEntry::make('name')
                        ->label('Full name'),

                    TextEntry::make('email')
                        ->label('Email')
                        ->copyable(),

                    TextEntry::make('role')
                        ->badge()
                        ->color(fn ($state) => match ($state?->value ?? $state) {
                            'admin' => 'danger',
                            'menu_owner' => 'success',
                            default => 'gray',
                        }),

                    TextEntry::make('created_at')
                        ->label('Joined')
                        ->dateTime(),

                    TextEntry::make('onboarding_completed_at')
                        ->label('Onboarding')
                        ->placeholder('Not completed')
                        ->formatStateUsing(fn ($state) => $state ? 'Completed '.$state->diffForHumans() : null),

                    TextEntry::make('onboarding_step')
                        ->label('Wizard step')
                        ->formatStateUsing(fn ($state) => "Step {$state} of 6")
                        ->visible(fn () => is_null($user->onboarding_completed_at)),
                ]),

            // ── Restaurant ────────────────────────────────────
            Section::make('Restaurant')
                ->visible(fn () => ! is_null($restaurant))
                ->columns(3)
                ->schema([
                    TextEntry::make('restaurant.name')
                        ->label('Name')
                        ->weight('bold'),

                    TextEntry::make('restaurant.slug')
                        ->label('Menu link')
                        ->copyable()
                        ->url(fn () => $restaurant ? url('/'.$restaurant->slug) : null)
                        ->openUrlInNewTab(),

                    TextEntry::make('restaurant.is_active')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($state) => $state ? 'success' : 'danger')
                        ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive'),

                    TextEntry::make('restaurant.phone')
                        ->label('Phone')
                        ->placeholder('—'),

                    TextEntry::make('restaurant.currency')
                        ->label('Currency')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('restaurant.preferred_language')
                        ->label('Language')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('restaurant.template.name')
                        ->label('Template')
                        ->placeholder('None')
                        ->badge(),

                    TextEntry::make('restaurant.created_at')
                        ->label('Restaurant created')
                        ->dateTime(),
                ]),

            // ── Traffic & Analytics ───────────────────────────
            Section::make('Traffic & Analytics')
                ->visible(fn () => ! is_null($restaurant))
                ->columns(4)
                ->schema([
                    TextEntry::make('stat_total_views')
                        ->label('Total Views')
                        ->getStateUsing(fn () => number_format($restaurant?->statistics()->count() ?? 0))
                        ->badge()
                        ->color('info'),

                    TextEntry::make('stat_unique_visitors')
                        ->label('Unique Visitors')
                        ->getStateUsing(fn () => number_format($restaurant?->statistics()->distinct('session_id')->count('session_id') ?? 0))
                        ->badge()
                        ->color('success'),

                    TextEntry::make('stat_qr_scans')
                        ->label('QR Scans')
                        ->getStateUsing(fn () => number_format($restaurant?->statistics()->where('via_qr', true)->count() ?? 0))
                        ->badge()
                        ->color('warning'),

                    TextEntry::make('stat_whatsapp_orders')
                        ->label('WhatsApp Orders')
                        ->getStateUsing(fn () => number_format((int) ($restaurant?->statistics()->sum('whatsapp_orders') ?? 0)))
                        ->badge()
                        ->color('primary'),

                    TextEntry::make('stat_views_today')
                        ->label('Views Today')
                        ->getStateUsing(fn () => number_format($restaurant?->statistics()->whereDate('viewed_at', today())->count() ?? 0))
                        ->badge()
                        ->color('info'),

                    TextEntry::make('stat_avg_time')
                        ->label('Avg. Time Spent')
                        ->getStateUsing(function () use ($restaurant): string {
                            $avg = $restaurant?->statistics()->whereNotNull('time_spent')->where('time_spent', '>', 0)->avg('time_spent') ?? 0;
                            $avg = (int) $avg;

                            return $avg > 0 ? gmdate('i\m s\s', $avg) : '—';
                        }),

                    TextEntry::make('stat_last_visit')
                        ->label('Last Visit')
                        ->getStateUsing(fn () => $restaurant?->statistics()->latest('viewed_at')->value('viewed_at'))
                        ->dateTime()
                        ->placeholder('No visits yet'),

                    TextEntry::make('stat_most_used_device')
                        ->label('Top Device')
                        ->getStateUsing(function () use ($restaurant): string {
                            if (! $restaurant) {
                                return '—';
                            }

                            $top = $restaurant->statistics()
                                ->selectRaw('device_type, COUNT(*) as cnt')
                                ->groupBy('device_type')
                                ->orderByDesc('cnt')
                                ->value('device_type');

                            return $top ?? '—';
                        })
                        ->badge()
                        ->color('gray'),
                ]),

            // ── Content ───────────────────────────────────────
            Section::make('Content')
                ->visible(fn () => ! is_null($restaurant))
                ->columns(4)
                ->schema([
                    TextEntry::make('content_dishes')
                        ->label('Dishes')
                        ->getStateUsing(fn () => ($restaurant?->dishes()->count() ?? 0).' / '.($restaurant?->dish_limit ?? '—'))
                        ->badge()
                        ->color('primary'),

                    TextEntry::make('content_categories')
                        ->label('Categories')
                        ->getStateUsing(fn () => ($restaurant?->categories()->count() ?? 0).' / '.($restaurant?->category_limit ?? '—'))
                        ->badge()
                        ->color('warning'),

                    TextEntry::make('content_available_dishes')
                        ->label('Available Dishes')
                        ->getStateUsing(fn () => number_format($restaurant?->dishes()->where('is_available', true)->count() ?? 0))
                        ->badge()
                        ->color('success'),

                    TextEntry::make('content_social_links')
                        ->label('Social Links')
                        ->getStateUsing(fn () => ($restaurant?->socialLinks()->count() ?? 0).' / '.($restaurant?->social_link_limit ?? '—'))
                        ->badge()
                        ->color('info'),
                ]),

        ]);
    }
}
