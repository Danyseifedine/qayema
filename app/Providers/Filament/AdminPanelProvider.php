<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Resources\BlockedIps\BlockedIpResource;
use App\Filament\Admin\Resources\Categories\CategoryResource;
use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Admin\Resources\Dishes\DishResource;
use App\Filament\Admin\Resources\Packages\PackageResource;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Filament\Admin\Resources\RestaurantSocialLinks\RestaurantSocialLinkResource;
use App\Filament\Admin\Resources\Templates\TemplateResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Widgets\PackagesEndingSoon;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Services\Media\MediaService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\Components\Section;
use Filament\Support\Colors\Color;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\HasMedia;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                UserResource::class,
                RestaurantResource::class,
                TemplateResource::class,
                CategoryResource::class,
                DishResource::class,
                RestaurantSocialLinkResource::class,
                ContactMessageResource::class,
                PackageResource::class,
                BlockedIpResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                PackagesEndingSoon::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsAdmin::class,
            ])
            ->authGuard('web')
            ->authPasswordBroker('users');
    }

    /**
     * Defaults every admin form and table follows, so no resource has to
     * remember them: a select is always the searchable dropdown, never the
     * browser's own, a section takes the full width of wherever it sits
     * (Filament v4 no longer does, which left forms half-empty), and an
     * upload shows its image from this app.
     */
    public function boot(): void
    {
        Select::configureUsing(fn (Select $select) => $select->native(false));
        SelectFilter::configureUsing(fn (SelectFilter $filter) => $filter->native(false));
        Section::configureUsing(fn (Section $section) => $section->columnSpanFull());

        // Upload previews load through this app, not the bucket's domain: the
        // field fetches the file, and R2 refuses a cross-origin fetch unless
        // it sends CORS headers.
        SpatieMediaLibraryFileUpload::configureUsing(fn (SpatieMediaLibraryFileUpload $upload) => $upload->getUploadedFileUsing(
            static function (SpatieMediaLibraryFileUpload $component, string $file): ?array {
                $media = $component->getRecord()?->getRelationValue('media')->firstWhere('uuid', $file);

                return $media === null ? null : [
                    'name' => $media->name ?? $media->file_name,
                    'size' => $media->size,
                    'type' => $media->mime_type,
                    'url' => route('admin.media.preview', $media->uuid),
                ];
            },
        )->saveUploadedFileUsing(
            // An admin upload is stored like an owner's: optimized to WebP
            // and put on the media disk. Filament's own save used its default
            // disk (the private `local` one), which the menu cannot serve.
            static fn (SpatieMediaLibraryFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string => $record instanceof HasMedia
                ? app(MediaService::class)->storeUpload($record, $file, match ($component->getCollection()) {
                    'logo', 'cover_image' => $component->getCollection(),
                    'image' => 'dish',
                    default => 'generic',
                }, (string) $component->getCollection())->uuid
                : null,
        ));
    }
}
