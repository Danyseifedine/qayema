<?php

namespace App\View\Components;

use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * The portal's <head> tags: title, description, canonical link, Open Graph
 * and Twitter cards, and on the landing page the Organization and WebSite
 * structured data.
 */
class Seo extends Component
{
    public string $description;

    public string $keywords;

    public string $author;

    public string $url;

    public string $image;

    public string $siteName;

    public string $locale;

    public ?string $twitterHandle;

    public ?string $facebookAppId;

    public ?string $schemaData;

    public function __construct(public string $title, string $description)
    {
        $this->description = Str::limit($description, 160);
        $this->keywords = (string) config('seo.keywords');
        $this->author = (string) config('seo.default_author');
        $this->url = url()->current();
        $this->image = asset('images/logo/logo.png');
        $this->siteName = (string) config('seo.organization.name');
        $this->locale = app()->getLocale();
        $this->twitterHandle = config('seo.twitter_username');
        $this->facebookAppId = config('seo.facebook_app_id');
        $this->schemaData = $this->schema();
    }

    public function render(): View
    {
        return view('components.seo');
    }

    public function fullTitle(): string
    {
        return $this->title.' '.config('seo.title_separator').' '.$this->siteName;
    }

    /** The landing page's structured data; no other page carries any. */
    private function schema(): ?string
    {
        if (! request()->is('/')) {
            return null;
        }

        $organization = config('seo.organization');

        return json_encode([
            [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => $organization['name'],
                'url' => $organization['url'],
                'logo' => $organization['logo'],
                'description' => $organization['description'],
                'contactPoint' => $organization['contact'],
                'sameAs' => $organization['social_links'],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $this->siteName,
                'url' => config('app.url'),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => [
                        '@type' => 'EntryPoint',
                        'urlTemplate' => config('app.url').'/search?q={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
