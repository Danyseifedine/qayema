<?php

namespace App\View\Components;

use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;

class Seo extends Component
{
    // Basic Meta
    public string $title;

    public string $description;

    public ?string $keywords;

    public ?string $author;

    // URLs and Canonical
    public string $url;

    public ?string $canonical;

    // Images
    public string $image;

    public ?string $imageAlt;

    public ?int $imageWidth;

    public ?int $imageHeight;

    // Open Graph
    public string $type;

    public ?string $siteName;

    public ?string $locale;

    public ?array $alternateLocales;

    // Twitter
    public string $twitterCard;

    public ?string $twitterSite;

    public ?string $twitterCreator;

    // Article Specific
    public ?string $publishedTime;

    public ?string $modifiedTime;

    public ?string $section;

    public ?array $tags;

    // Product Specific
    public ?string $price;

    public ?string $currency;

    public ?string $availability;

    public ?string $brand;

    public ?float $rating;

    public ?int $reviewCount;

    // Video Specific
    public ?string $videoUrl;

    public ?int $videoDuration;

    public ?string $videoThumbnail;

    // Robots & Indexing
    public bool $noindex;

    public bool $nofollow;

    public bool $noarchive;

    public bool $nosnippet;

    public ?int $maxSnippet;

    public ?int $maxImagePreview;

    public ?int $maxVideoPreview;

    // Schema.org JSON-LD
    public ?array $schema;

    public bool $enableBreadcrumbs;

    public ?array $breadcrumbs;

    public ?string $schemaData;

    // Additional Meta
    public ?string $facebookAppId;

    public ?array $additionalMeta;

    // Alternate Languages (hreflang)
    public ?array $hreflang;

    public function __construct(
        ?string $title = null,
        ?string $description = null,
        ?string $keywords = null,
        ?string $author = null,
        ?string $url = null,
        ?string $canonical = null,
        ?string $image = null,
        ?string $imageAlt = null,
        ?int $imageWidth = null,
        ?int $imageHeight = null,
        string $type = 'website',
        ?string $siteName = null,
        ?string $locale = null,
        ?array $alternateLocales = null,
        string $twitterCard = 'summary_large_image',
        ?string $twitterSite = null,
        ?string $twitterCreator = null,
        ?string $publishedTime = null,
        ?string $modifiedTime = null,
        ?string $section = null,
        ?array $tags = null,
        ?string $price = null,
        ?string $currency = null,
        ?string $availability = null,
        ?string $brand = null,
        ?float $rating = null,
        ?int $reviewCount = null,
        ?string $videoUrl = null,
        ?int $videoDuration = null,
        ?string $videoThumbnail = null,
        bool $noindex = false,
        bool $nofollow = false,
        bool $noarchive = false,
        bool $nosnippet = false,
        ?int $maxSnippet = null,
        ?int $maxImagePreview = null,
        ?int $maxVideoPreview = null,
        ?array $schema = null,
        bool $enableBreadcrumbs = true,
        ?array $breadcrumbs = null,
        ?string $facebookAppId = null,
        ?array $additionalMeta = null,
        ?array $hreflang = null
    ) {
        // MenuX defaults
        $currentLocale = app()->getLocale();

        $defaultTitles = [
            'en' => 'MenuX - Create Beautiful Digital Menus',
        ];

        $defaultDescriptions = [
            'en' => 'Create beautiful digital menus for your restaurant. Free up to 20 menu items. Share your menu with a simple link. Mobile optimized and easy to manage.',
        ];

        $defaultKeywords = [
            'en' => 'digital menu, restaurant menu, online menu, menu creator, food menu, restaurant menu online, digital menu maker, menu sharing, restaurant technology',
        ];

        $defaultSiteName = [
            'en' => 'MenuX',
        ];

        $defaultAuthor = [
            'en' => 'MenuX',
        ];

        $defaultImageAlt = [
            'en' => 'MenuX - Create Beautiful Digital Menus',
        ];

        $defaultTwitterSite = [
            'en' => env('TWITTER_USERNAME', ''),
        ];

        $defaultTwitterCreator = [
            'en' => env('TWITTER_USERNAME', ''),
        ];

        $defaultAvailability = [
            'ar' => 'متوفر',
            'en' => 'In Stock',
        ];

        $defaultSection = [
            'en' => 'Digital Menu Creator',
        ];

        $defaultCurrency = [
            'ar' => 'دولار',
            'en' => 'USD',
        ];

        $this->title = $title ?? $defaultTitles[$currentLocale] ?? $defaultTitles['en'];
        $this->description = Str::limit($description ?? $defaultDescriptions[$currentLocale] ?? $defaultDescriptions['en'], 160);
        $this->keywords = $keywords ?? $defaultKeywords[$currentLocale] ?? $defaultKeywords['en'];
        $this->author = $author ?? $defaultAuthor[$currentLocale] ?? $defaultAuthor['en'];

        $this->url = $url ?? url()->current();
        $this->canonical = $canonical ?? $this->url;

        $this->image = $image ?? asset('images/logo/logo.png');
        $this->imageAlt = $imageAlt ?? $defaultImageAlt[$currentLocale] ?? $defaultImageAlt['en'];
        $this->imageWidth = $imageWidth ?? 1200;
        $this->imageHeight = $imageHeight ?? 630;

        $this->type = $type;
        $this->siteName = $siteName ?? $defaultSiteName[$currentLocale] ?? $defaultSiteName['en'];
        $this->locale = $locale ?? $currentLocale;
        $this->alternateLocales = $alternateLocales;

        $this->twitterCard = $twitterCard;
        $this->twitterSite = $twitterSite ?? $defaultTwitterSite[$currentLocale] ?? $defaultTwitterSite['en'];
        $this->twitterCreator = $twitterCreator ?? $defaultTwitterCreator[$currentLocale] ?? $defaultTwitterCreator['en'];

        $this->publishedTime = $publishedTime;
        $this->modifiedTime = $modifiedTime;
        $this->section = $section ?? $defaultSection[$currentLocale] ?? $defaultSection['en'];
        $this->tags = $tags;

        $this->price = $price;
        $this->currency = $currency ?? $defaultCurrency[$currentLocale] ?? $defaultCurrency['en'];
        $this->availability = $availability ?? $defaultAvailability[$currentLocale] ?? $defaultAvailability['en'];
        $this->brand = $brand;
        $this->rating = $rating;
        $this->reviewCount = $reviewCount;

        $this->videoUrl = $videoUrl;
        $this->videoDuration = $videoDuration;
        $this->videoThumbnail = $videoThumbnail;

        $this->noindex = $noindex;
        $this->nofollow = $nofollow;
        $this->noarchive = $noarchive;
        $this->nosnippet = $nosnippet;
        $this->maxSnippet = $maxSnippet;
        $this->maxImagePreview = $maxImagePreview;
        $this->maxVideoPreview = $maxVideoPreview;

        $this->schema = $schema;
        $this->enableBreadcrumbs = $enableBreadcrumbs;
        $this->breadcrumbs = $breadcrumbs;

        $this->facebookAppId = $facebookAppId ?? config('seo.facebook_app_id');
        $this->additionalMeta = $additionalMeta;
        $this->hreflang = $hreflang;

        // Generate schema data
        $this->schemaData = $this->generateSchema();
    }

    public function render(): View
    {
        return view('components.seo');
    }

    public function fullTitle(): string
    {
        $separator = config('seo.title_separator', '|');

        return $this->title . ' ' . $separator . ' ' . $this->siteName;
    }

    public function robotsContent(): string
    {
        $robots = [];

        $robots[] = $this->noindex ? 'noindex' : 'index';
        $robots[] = $this->nofollow ? 'nofollow' : 'follow';

        if ($this->noarchive) {
            $robots[] = 'noarchive';
        }
        if ($this->nosnippet) {
            $robots[] = 'nosnippet';
        }
        if ($this->maxSnippet) {
            $robots[] = "max-snippet:{$this->maxSnippet}";
        }
        if ($this->maxImagePreview) {
            $robots[] = "max-image-preview:{$this->maxImagePreview}";
        }
        if ($this->maxVideoPreview) {
            $robots[] = "max-video-preview:{$this->maxVideoPreview}";
        }

        return implode(', ', $robots);
    }

    public function generateSchema(): ?string
    {
        if (! $this->schema && ! $this->enableBreadcrumbs && $this->type === 'website') {
            return null;
        }

        $schemas = [];

        // Organization Schema (if homepage)
        if (request()->is('/') && config('seo.organization')) {
            $schemas[] = $this->organizationSchema();
        }

        // Website Schema
        if (request()->is('/')) {
            $schemas[] = $this->websiteSchema();
        }

        // Breadcrumb Schema
        if ($this->enableBreadcrumbs && $this->breadcrumbs) {
            $schemas[] = $this->breadcrumbSchema();
        }

        // Article Schema
        if ($this->type === 'article') {
            $schemas[] = $this->articleSchema();
        }

        // Product Schema
        if ($this->type === 'product' && $this->price) {
            $schemas[] = $this->productSchema();
        }

        // Video Schema
        if ($this->videoUrl) {
            $schemas[] = $this->videoSchema();
        }

        // Custom Schema
        if ($this->schema) {
            $schemas = array_merge($schemas, (array) $this->schema);
        }

        return ! empty($schemas) ? json_encode($schemas, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null;
    }

    protected function organizationSchema(): array
    {
        $org = config('seo.organization');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $org['name'] ?? config('app.name'),
            'url' => $org['url'] ?? config('app.url'),
            'logo' => $org['logo'] ?? null,
            'description' => $org['description'] ?? null,
            'contactPoint' => $org['contact'] ?? null,
            'sameAs' => $org['social_links'] ?? [],
        ];
    }

    protected function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->siteName,
            'url' => config('app.url'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => config('app.url') . '/search?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    protected function breadcrumbSchema(): array
    {
        $items = [];
        foreach ($this->breadcrumbs as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'] ?? null,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    protected function articleSchema(): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'datePublished' => $this->publishedTime,
            'dateModified' => $this->modifiedTime ?? $this->publishedTime,
            'author' => [
                '@type' => 'Person',
                'name' => $this->author,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->siteName,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => config('seo.organization.logo'),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $this->url,
            ],
        ];

        if ($this->section) {
            $schema['articleSection'] = $this->section;
        }

        if ($this->tags) {
            $schema['keywords'] = implode(', ', $this->tags);
        }

        return $schema;
    }

    protected function productSchema(): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'offers' => [
                '@type' => 'Offer',
                'price' => $this->price,
                'priceCurrency' => $this->currency,
                'availability' => $this->availability ? "https://schema.org/{$this->availability}" : 'https://schema.org/InStock',
                'url' => $this->url,
            ],
        ];

        if ($this->brand) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => $this->brand,
            ];
        }

        if ($this->rating && $this->reviewCount) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $this->rating,
                'reviewCount' => $this->reviewCount,
            ];
        }

        return $schema;
    }

    protected function videoSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $this->title,
            'description' => $this->description,
            'thumbnailUrl' => $this->videoThumbnail ?? $this->image,
            'uploadDate' => $this->publishedTime,
            'duration' => $this->videoDuration ? "PT{$this->videoDuration}S" : null,
            'contentUrl' => $this->videoUrl,
            'embedUrl' => $this->videoUrl,
        ];
    }
}
