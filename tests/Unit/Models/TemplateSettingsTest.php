<?php

namespace Tests\Unit\Models;

use App\Models\Template;
use Tests\TestCase;

class TemplateSettingsTest extends TestCase
{
    private function template(?array $schema): Template
    {
        return new Template(['settings_schema' => $schema, 'price' => 0]);
    }

    public function test_a_missing_schema_means_no_settings(): void
    {
        $this->assertSame([], $this->template(null)->settingsSchema());
        $this->assertSame([], $this->template(null)->defaultSettings());
        $this->assertSame([], $this->template(null)->resolveSettings(['anything' => 'x']));
    }

    public function test_defaults_are_keyed_by_the_schema_keys(): void
    {
        $template = $this->template([
            ['key' => 'primary', 'type' => 'color', 'default' => '#111111'],
            ['key' => 'heading', 'type' => 'text', 'default' => 'Menu'],
            ['key' => 'no_default', 'type' => 'text'],
        ]);

        $this->assertSame(['primary' => '#111111', 'heading' => 'Menu', 'no_default' => null], $template->defaultSettings());
    }

    public function test_rows_without_a_key_are_ignored(): void
    {
        $template = $this->template([['type' => 'color', 'default' => '#000'], ['key' => 'ok', 'default' => 1]]);

        $this->assertSame(['ok' => 1], $template->defaultSettings());
    }

    public function test_stored_values_override_defaults_but_nulls_do_not(): void
    {
        $template = $this->template([
            ['key' => 'primary', 'type' => 'color', 'default' => '#111111'],
            ['key' => 'heading', 'type' => 'text', 'default' => 'Menu'],
        ]);

        $this->assertSame(
            ['primary' => '#ABCDEF', 'heading' => 'Menu'],
            $template->resolveSettings(['primary' => '#ABCDEF', 'heading' => null]),
        );
    }

    public function test_undeclared_stored_keys_are_dropped(): void
    {
        $template = $this->template([['key' => 'primary', 'type' => 'color', 'default' => '#111111']]);

        $this->assertSame(['primary' => '#111111'], $template->resolveSettings(['legacy' => 'x', 'evil' => '<script>']));
    }

    public function test_free_is_exactly_a_zero_price(): void
    {
        $this->assertTrue((new Template(['price' => 0]))->isFree());
        $this->assertFalse((new Template(['price' => 1]))->isFree());
    }
}
