<?php

namespace App\View\Components;

use App\Services\Portal\StructuredData;
use App\Support\PortalUrl;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * The portal's <head> tags: title, description, the page's one canonical
 * address and its other-language twin (hreflang), robots, Open Graph and
 * Twitter cards, and structured data (the product on home, a breadcrumb on
 * the other public pages).
 */
class Seo extends Component
{
    /** Indexed, with a large image preview and no cap on the snippet. */
    public const INDEX = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

    /** Kept out of search results, links still followed (sign-in, setup). */
    public const NOINDEX = 'noindex, follow';

    public string $description;

    public string $robots;

    public string $url;

    public string $image;

    public string $imageAlt;

    public string $siteName;

    public string $locale;

    public string $ogLocale;

    /** @var list<string> */
    public array $ogLocaleAlternates;

    /** @var array<string, string> language => URL */
    public array $alternates;

    public ?string $twitterHandle;

    public ?string $facebookAppId;

    /** @var list<string> */
    public array $schemas;

    public function __construct(
        public string $title,
        string $description,
        ?string $robots = null,
    ) {
        $this->robots = $robots ?? self::INDEX;
        $this->description = Str::limit($description, 160);
        $this->siteName = (string) config('seo.site_name');
        $this->locale = app()->getLocale();
        $this->alternates = PortalUrl::alternates();
        // A public page's address without any query string, so ?utm=... or
        // a stray parameter never splits it into duplicates.
        $this->url = $this->alternates[$this->locale] ?? url()->current();
        $this->image = asset((string) config('seo.images.'.$this->locale, config('seo.images.en')));
        $this->imageAlt = __('portal.seo.image_alt');

        $ogLocales = (array) config('seo.og_locales');
        $this->ogLocale = $ogLocales[$this->locale] ?? $this->locale;
        $this->ogLocaleAlternates = array_values(array_diff_key(
            array_intersect_key($ogLocales, $this->alternates),
            [$this->locale => true],
        ));

        $this->twitterHandle = config('seo.twitter_username');
        $this->facebookAppId = config('seo.facebook_app_id');
        $this->schemas = $this->schemas();
    }

    public function render(): View
    {
        return view('components.seo');
    }

    /** "Page | Qayema", or the title as it is when it already names Qayema. */
    public function fullTitle(): string
    {
        return str_contains($this->title, $this->siteName)
            ? $this->title
            : $this->title.' '.config('seo.title_separator').' '.$this->siteName;
    }

    /** The English page is what anyone outside both languages is shown. */
    public function defaultAlternate(): ?string
    {
        return $this->alternates[PortalUrl::LOCALES[0]] ?? null;
    }

    /**
     * @return list<string> each a JSON-LD document
     */
    private function schemas(): array
    {
        $page = PortalUrl::current();

        if ($page === null || $this->robots !== self::INDEX) {
            return [];
        }

        $data = app(StructuredData::class);
        $documents = array_filter([
            $page === 'home' ? $data->home() : null,
            $data->breadcrumb($page, $this->title),
        ]);

        return array_values(array_map(
            fn (array $document): string => json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG),
            $documents,
        ));
    }
}
