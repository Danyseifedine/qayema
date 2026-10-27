<?php

namespace Tests\Integration\Models;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** An inbox message is open until an admin marks it handled, and can be opened again. */
class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_message_is_open(): void
    {
        $message = ContactMessage::factory()->create();

        $this->assertFalse($message->isHandled());
        $this->assertSame([$message->id], ContactMessage::query()->open()->pluck('id')->all());
    }

    public function test_marking_it_handled_keeps_the_first_date(): void
    {
        $this->freezeTime();
        $message = ContactMessage::factory()->create();

        $message->markHandled();
        $first = $message->fresh()->handled_at;
        $this->travel(1)->day();
        $message->markHandled();

        $this->assertTrue($message->fresh()->isHandled());
        $this->assertTrue($first->equalTo($message->fresh()->handled_at));
        $this->assertSame(0, ContactMessage::query()->open()->count());
    }

    public function test_reopening_puts_it_back_in_the_open_list(): void
    {
        $message = ContactMessage::factory()->create(['name' => 'Back Again']);
        $message->markHandled();

        $message->reopen();

        $this->assertFalse($message->fresh()->isHandled());
        $this->assertSame(1, ContactMessage::query()->open()->count());
    }

    public function test_the_public_form_cannot_mark_a_message_handled(): void
    {
        // Not fillable: strict models refuse it outright rather than drop it.
        $this->expectException(MassAssignmentException::class);

        ContactMessage::query()->create([
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'message' => 'Hi',
            'handled_at' => now(),
        ]);
    }
}
