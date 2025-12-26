<?php

if (!function_exists('seo_data')) {
    /**
     * Store SEO data in the request for later use
     */
    function seo_data(array $data): void
    {
        request()->attributes->set('seo_data', array_merge(
            request()->attributes->get('seo_data', []),
            $data
        ));
    }
}

if (!function_exists('get_seo_data')) {
    /**
     * Get stored SEO data
     */
    function get_seo_data(?string $key = null, $default = null)
    {
        $data = request()->attributes->get('seo_data', []);

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? $default;
    }
}

if (!function_exists('set_seo')) {
    /**
     * Quick helper to set SEO data
     *
     * @param string|array $titleOrData
     * @param string|null $description
     * @param string|null $image
     */
    function set_seo($titleOrData, ?string $description = null, ?string $image = null): void
    {
        if (is_array($titleOrData)) {
            seo_data($titleOrData);
            return;
        }

        $data = ['title' => $titleOrData];

        if ($description) {
            $data['description'] = $description;
        }

        if ($image) {
            $data['image'] = $image;
        }

        seo_data($data);
    }
}

if (!function_exists('seo_title')) {
    /**
     * Set SEO title
     */
    function seo_title(string $title): void
    {
        seo_data(['title' => $title]);
    }
}

if (!function_exists('seo_description')) {
    /**
     * Set SEO description
     */
    function seo_description(string $description): void
    {
        seo_data(['description' => $description]);
    }
}

if (!function_exists('seo_image')) {
    /**
     * Set SEO image
     */
    function seo_image(string $image): void
    {
        seo_data(['image' => $image]);
    }
}

if (!function_exists('seo_keywords')) {
    /**
     * Set SEO keywords
     */
    function seo_keywords($keywords): void
    {
        $keywordsString = is_array($keywords) ? implode(', ', $keywords) : $keywords;
        seo_data(['keywords' => $keywordsString]);
    }
}

if (!function_exists('seo_product')) {
    /**
     * Set SEO for product pages
     */
    function seo_product(
        string $title,
        string $description,
        string $image,
        float $price,
        string $currency = 'USD',
        string $availability = 'InStock',
        ?string $brand = null,
        ?float $rating = null,
        ?int $reviewCount = null
    ): void {
        seo_data([
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'type' => 'product',
            'price' => $price,
            'currency' => $currency,
            'availability' => $availability,
            'brand' => $brand,
            'rating' => $rating,
            'reviewCount' => $reviewCount,
        ]);
    }
}

if (!function_exists('seo_breadcrumb')) {
    /**
     * Add breadcrumb
     */
    function seo_breadcrumb(string $name, string $url): void
    {
        $breadcrumbs = get_seo_data('breadcrumbs', []);
        $breadcrumbs[] = ['name' => $name, 'url' => $url];
        seo_data(['breadcrumbs' => $breadcrumbs]);
    }
}

if (!function_exists('seo_noindex')) {
    /**
     * Disable indexing for current page
     */
    function seo_noindex(): void
    {
        seo_data(['noindex' => true]);
    }
}