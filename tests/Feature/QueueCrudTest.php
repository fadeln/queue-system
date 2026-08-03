<?php

namespace Tests\Feature;

use App\Events\QueueUpdated;
use App\Models\QueueEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class QueueCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);

        foreach (range(1, 3) as $i) {
            QueueEntry::create([
                'code' => sprintf('A-%03d', $i),
                'position' => $i,
                'status' => QueueEntry::STATUS_PENDING,
            ]);
        }
    }

    public function test_store_creates_a_new_pending_entry(): void
    {
        Event::fake([QueueUpdated::class]);

        $this->actingAs($this->admin)->post('/admin/queue');

        $entry = QueueEntry::where('position', 4)->first();

        $this->assertNotNull($entry);
        $this->assertSame('A-004', $entry->code);
        $this->assertSame(QueueEntry::STATUS_PENDING, $entry->status);

        Event::assertDispatched(QueueUpdated::class);
    }

    public function test_store_generates_unique_code_after_cancels(): void
    {
        QueueEntry::where('position', 3)->update(['status' => QueueEntry::STATUS_SKIPPED]);

        $this->actingAs($this->admin)->post('/admin/queue');
        $this->actingAs($this->admin)->post('/admin/queue');

        $this->assertSame(5, QueueEntry::count());
        $this->assertSame('A-005', QueueEntry::where('position', 5)->value('code'));
    }

    public function test_cancel_marks_a_pending_entry_as_skipped(): void
    {
        Event::fake([QueueUpdated::class]);

        $entry = QueueEntry::where('code', 'A-002')->first();

        $this->actingAs($this->admin)->post("/admin/queue/{$entry->id}/cancel");

        $this->assertDatabaseHas('queue_entries', [
            'id' => $entry->id,
            'status' => QueueEntry::STATUS_SKIPPED,
        ]);

        Event::assertDispatched(QueueUpdated::class);
    }

    public function test_cancel_keeps_the_row_for_history(): void
    {
        $entry = QueueEntry::where('code', 'A-002')->first();

        $this->actingAs($this->admin)->post("/admin/queue/{$entry->id}/cancel");

        $this->assertSame(3, QueueEntry::count());
    }

    public function test_cancel_rejects_calling_and_called_entries(): void
    {
        QueueEntry::where('position', 1)->update(['status' => QueueEntry::STATUS_CALLING]);
        QueueEntry::where('position', 2)->update(['status' => QueueEntry::STATUS_CALLED]);

        $calling = QueueEntry::where('position', 1)->first();
        $called = QueueEntry::where('position', 2)->first();

        $this->actingAs($this->admin)->post("/admin/queue/{$calling->id}/cancel")
            ->assertForbidden();
        $this->actingAs($this->admin)->post("/admin/queue/{$called->id}/cancel")
            ->assertForbidden();

        $this->assertSame(QueueEntry::STATUS_CALLING, $calling->fresh()->status);
        $this->assertSame(QueueEntry::STATUS_CALLED, $called->fresh()->status);
    }

    public function test_cancel_returns_404_for_missing_entry(): void
    {
        $this->actingAs($this->admin)->post('/admin/queue/9999/cancel')
            ->assertNotFound();
    }
}
