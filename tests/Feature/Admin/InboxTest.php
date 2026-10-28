<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Admin\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Admin\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Models\ContactMessage;
use App\Models\Package;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The inbox keeps what is still to answer apart from what is done: open
 * first, handled a tab away, the menu's badge counting the open ones, and a
 * package request handled by applying it.
 */
class InboxTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_the_list_opens_on_the_open_messages(): void
    {
        $open = ContactMessage::factory()->create();
        $done = ContactMessage::factory()->create(['created_at' => now()->subDay()]);
        $done->markHandled();

        Livewire::test(ListContactMessages::class)
            ->assertSet('activeTab', 'open')
            ->assertCanSeeTableRecords([$open])
            ->assertCanNotSeeTableRecords([$done])
            ->set('activeTab', 'handled')
            ->assertCanSeeTableRecords([$done])
            ->assertCanNotSeeTableRecords([$open])
            ->set('activeTab', 'all')
            ->assertCanSeeTableRecords([$open, $done]);
    }

    public function test_the_requests_tab_lists_only_package_requests(): void
    {
        $plain = ContactMessage::factory()->create();
        $request = ContactMessage::factory()->packageRequest()->create();

        Livewire::test(ListContactMessages::class)
            ->set('activeTab', 'requests')
            ->assertCanSeeTableRecords([$request])
            ->assertCanNotSeeTableRecords([$plain]);
    }

    public function test_a_message_is_marked_handled_and_reopened_from_its_row(): void
    {
        $message = ContactMessage::factory()->create();

        Livewire::test(ListContactMessages::class)
            ->assertTableColumnStateSet('handled_at', 'Open', $message)
            ->assertActionHasLabel(TestAction::make('handle')->table($message), 'Mark handled')
            ->callAction(TestAction::make('handle')->table($message))
            ->assertNotified('Marked handled')
            ->assertCanNotSeeTableRecords([$message]);

        $this->assertTrue($message->fresh()->isHandled());

        Livewire::test(ListContactMessages::class)
            ->set('activeTab', 'handled')
            ->assertTableColumnStateSet('handled_at', $message->fresh()->handled_at->diffForHumans(), $message)
            ->assertActionHasLabel(TestAction::make('handle')->table($message), 'Reopen')
            ->callAction(TestAction::make('handle')->table($message))
            ->assertNotified('Back in the open messages');

        $this->assertFalse($message->fresh()->isHandled());
    }

    public function test_several_are_marked_handled_at_once(): void
    {
        $messages = ContactMessage::factory()->count(3)->create();

        Livewire::test(ListContactMessages::class)
            ->selectTableRecords($messages->take(2)->pluck('id')->all())
            ->callAction(TestAction::make('handleSelected')->table()->bulk());

        $this->assertSame(1, ContactMessage::query()->open()->count());
    }

    public function test_the_message_page_marks_it_handled_too(): void
    {
        $message = ContactMessage::factory()->create();

        Livewire::test(ViewContactMessage::class, ['record' => $message->id])
            ->assertSee('Still open')
            ->callAction('handle');

        $this->assertTrue($message->fresh()->isHandled());
    }

    public function test_applying_a_requested_package_marks_the_request_handled(): void
    {
        $restaurant = $this->owner();
        $message = ContactMessage::factory()->packageRequest(Package::findBySlug('pro'))->create(['user_id' => $restaurant->user_id]);

        Livewire::test(ViewContactMessage::class, ['record' => $message->id])
            ->callAction('applyPackage')
            ->assertHasNoFormErrors();

        $this->assertTrue($message->fresh()->isHandled());
    }

    public function test_the_menu_badge_counts_the_open_messages(): void
    {
        $this->assertNull(ContactMessageResource::getNavigationBadge());

        ContactMessage::factory()->count(2)->create();
        ContactMessage::factory()->create()->markHandled();

        // Counted once a request (the menu's badge and the tab's): the next page counts again.
        $this->app->instance('request', Request::create('/admin'));
        $this->assertSame('2', ContactMessageResource::getNavigationBadge());
        $this->assertSame('Inbox', ContactMessageResource::getNavigationLabel());
    }
}
