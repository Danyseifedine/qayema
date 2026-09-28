<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Templates\Pages\CreateTemplate;
use App\Filament\Admin\Resources\Templates\Pages\EditTemplate;
use App\Filament\Admin\Resources\Templates\Pages\ListTemplates;
use App\Filament\Admin\Resources\Templates\TemplateResource;
use App\Models\Template;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * The templates (designs) resource: the list with real rows and its
 * toggles, bulk delete, and the edit form's settings and validation.
 */
class TemplateAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_the_list_shows_each_design_with_how_many_restaurants_use_it(): void
    {
        $used = Template::factory()->create(['name' => ['en' => 'Harbour'], 'slug' => 'harbour']);
        $unused = Template::factory()->create(['name' => ['en' => 'Orchard'], 'slug' => 'orchard']);
        $this->owner(['template_id' => $used->id]);
        $this->owner(['template_id' => $used->id]);

        Livewire::test(ListTemplates::class)
            ->assertCanSeeTableRecords([$used, $unused])
            ->assertTableColumnStateSet('restaurants_count', 2, $used)
            ->assertTableColumnStateSet('restaurants_count', 0, $unused)
            ->assertSee('Harbour')
            ->assertSee('orchard');
    }

    public function test_the_list_searches_by_name(): void
    {
        $harbour = Template::factory()->create(['name' => ['en' => 'Harbour']]);
        $orchard = Template::factory()->create(['name' => ['en' => 'Orchard']]);

        Livewire::test(ListTemplates::class)
            ->searchTable('Harb')
            ->assertCanSeeTableRecords([$harbour])
            ->assertCanNotSeeTableRecords([$orchard]);
    }

    public function test_the_active_and_premium_toggles_in_the_table_save(): void
    {
        $template = Template::factory()->create(['is_active' => true, 'is_premium' => false]);

        Livewire::test(ListTemplates::class)
            ->call('updateTableColumnState', 'is_active', (string) $template->getKey(), false)
            ->call('updateTableColumnState', 'is_premium', (string) $template->getKey(), true);

        $template = $template->fresh();
        $this->assertFalse($template->is_active);
        $this->assertTrue($template->is_premium);
    }

    public function test_bulk_delete_removes_the_designs_and_frees_the_restaurants_using_them(): void
    {
        $first = Template::factory()->create();
        $second = Template::factory()->create();
        $kept = Template::factory()->create();
        $restaurant = $this->owner(['template_id' => $first->id]);

        Livewire::test(ListTemplates::class)
            ->selectTableRecords([$first->id, $second->id])
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertDatabaseMissing('templates', ['id' => $first->id]);
        $this->assertDatabaseMissing('templates', ['id' => $second->id]);
        $this->assertDatabaseHas('templates', ['id' => $kept->id]);
        $this->assertNull($restaurant->fresh()->template_id);
    }

    public function test_the_edit_page_renders_a_design_with_every_setting_type(): void
    {
        $template = Template::factory()->withSettings([
            ['key' => 'ink', 'type' => 'color', 'default' => '#111111', 'label' => ['en' => 'Ink'], 'contrast_with' => 'paper'],
            ['key' => 'paper', 'type' => 'color', 'default' => '#FFFFFF'],
            ['key' => 'layout', 'type' => 'select', 'default' => 'grid', 'options' => ['grid', 'list']],
            ['key' => 'show_name', 'type' => 'boolean', 'default' => true],
            ['key' => 'tagline', 'type' => 'text', 'default' => 'Welcome'],
        ])->create(['name' => ['en' => 'Harbour'], 'slug' => 'harbour']);

        $this->get(TemplateResource::getUrl('edit', ['record' => $template]))
            ->assertOk()
            ->assertSee('Harbour')
            ->assertSee('Ink');

        Livewire::test(EditTemplate::class, ['record' => $template->id])
            ->assertSchemaStateSet(['slug' => 'harbour'])
            ->call('save')
            ->assertHasNoFormErrors();

        $rows = collect($template->fresh()->settings_schema)->keyBy('key');
        $this->assertSame(['ink', 'paper', 'layout', 'show_name', 'tagline'], $rows->keys()->all());
        $this->assertSame(['grid', 'list'], $rows['layout']['options']);
        $this->assertSame('paper', $rows['ink']['contrast_with']);
    }

    public function test_a_design_may_keep_its_own_slug_but_not_take_another(): void
    {
        Template::factory()->create(['slug' => 'taken']);
        $template = Template::factory()->create(['slug' => 'mine', 'sort_order' => 0]);

        Livewire::test(EditTemplate::class, ['record' => $template->id])
            ->fillForm(['slug' => 'mine', 'sort_order' => 4])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(4, $template->fresh()->sort_order);

        Livewire::test(EditTemplate::class, ['record' => $template->id])
            ->fillForm(['slug' => 'taken'])
            ->call('save')
            ->assertHasFormErrors(['slug' => 'unique']);

        $this->assertSame('mine', $template->fresh()->slug);
    }

    public function test_the_edit_form_requires_the_english_name_and_a_slug(): void
    {
        $template = Template::factory()->create(['name' => ['en' => 'Kept'], 'slug' => 'kept']);

        Livewire::test(EditTemplate::class, ['record' => $template->id])
            ->fillForm(['name.en' => '', 'slug' => ''])
            ->call('save')
            ->assertHasFormErrors(['name.en' => 'required', 'slug' => 'required']);

        $this->assertSame('kept', $template->fresh()->slug);
    }

    public function test_clearing_the_arabic_name_drops_that_language(): void
    {
        $template = Template::factory()->create(['name' => ['en' => 'Classic', 'ar' => 'كلاسيكي']]);

        Livewire::test(EditTemplate::class, ['record' => $template->id])
            ->fillForm(['name.ar' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['en' => 'Classic'], $template->fresh()->getTranslations('name'));
    }

    public function test_the_delete_header_action_removes_the_design(): void
    {
        $template = Template::factory()->create();

        Livewire::test(EditTemplate::class, ['record' => $template->id])
            ->callAction('delete');

        $this->assertDatabaseMissing('templates', ['id' => $template->id]);
    }

    public function test_a_setting_needs_a_key_and_a_type(): void
    {
        Livewire::test(CreateTemplate::class)
            ->fillForm([
                'name.en' => 'Midnight',
                'slug' => 'midnight',
                'settings_schema' => [['key' => '', 'type' => null, 'default' => '']],
            ])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertDatabaseMissing('templates', ['slug' => 'midnight']);
    }

    public function test_the_create_page_renders_over_http(): void
    {
        $this->get(TemplateResource::getUrl('create'))->assertOk();
    }
}
