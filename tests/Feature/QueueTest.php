<?php

namespace Tests\Feature;

use App\Events\QueueUpdated;
use App\Models\QueueEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class QueueTest extends TestCase
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

    public function test_display_page_shows_current_number(): void
    {
        QueueEntry::where('position', 1)->update(['status' => QueueEntry::STATUS_CALLING]);

        $response = $this->get('/queue');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('queue/display')
            ->has('snapshot.entries', 3)
            ->where('snapshot.current', 'A-001'));
    }

    public function test_dashboard_shows_stats_for_admin(): void
    {
        QueueEntry::where('position', 1)->update([
            'status' => QueueEntry::STATUS_CALLING,
        ]);
        QueueEntry::where('position', 2)->update([
            'status' => QueueEntry::STATUS_CALLED,
            'served_at' => now(),
        ]);

        $this->actingAs($this->admin)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('stats.current', 'A-001')
                ->where('stats.waiting', 1)
                ->where('stats.servedToday', 1));
    }

    public function test_board_page_renders_for_admin(): void
    {
        $this->actingAs($this->admin)->get('/admin/queue')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('queue/board')
                ->has('snapshot.entries', 3));
    }

    public function test_admin_only_can_manage_queue(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->post('/admin/queue/next')->assertForbidden();

        auth()->forgetGuards();
        auth()->logout();
        $this->app['auth']->forgetGuards();

        $this->post('/admin/queue/next')->assertRedirect(route('login'));
    }

    public function test_next_advances_the_queue(): void
    {
        Event::fake([QueueUpdated::class]);

        $this->actingAs($this->admin)->post('/admin/queue/next');

        $this->assertSame('A-001', QueueEntry::current()?->code);
        $this->assertSame(QueueEntry::STATUS_CALLING, QueueEntry::where('code', 'A-001')->value('status'));

        $this->actingAs($this->admin)->post('/admin/queue/next');

        $this->assertSame('A-002', QueueEntry::current()?->code);
        $this->assertSame(QueueEntry::STATUS_CALLED, QueueEntry::where('code', 'A-001')->value('status'));

        Event::assertDispatchedTimes(QueueUpdated::class, 2);
    }

    public function test_prev_recalls_the_last_called_number(): void
    {
        $this->actingAs($this->admin)->post('/admin/queue/next'); // A-001 calling
        $this->actingAs($this->admin)->post('/admin/queue/next'); // A-002 calling, A-001 called

        $this->actingAs($this->admin)->post('/admin/queue/prev');

        $this->assertSame('A-001', QueueEntry::current()?->code);
        $this->assertSame(QueueEntry::STATUS_CALLING, QueueEntry::where('code', 'A-001')->value('status'));
        $this->assertSame(QueueEntry::STATUS_CALLED, QueueEntry::where('code', 'A-002')->value('status'));
    }

    public function test_mark_called_completes_a_pending_entry(): void
    {
        $entry = QueueEntry::where('code', 'A-002')->first();

        $this->actingAs($this->admin)->post("/admin/queue/{$entry->id}/called");

        $this->assertSame(QueueEntry::STATUS_CALLED, $entry->fresh()->status);
        $this->assertNotNull($entry->fresh()->served_at);
    }

    public function test_reset_returns_all_entries_to_pending(): void
    {
        $this->actingAs($this->admin)->post('/admin/queue/next');
        $this->actingAs($this->admin)->post('/admin/queue/reset');

        $this->assertSame(0, QueueEntry::where('status', '!=', QueueEntry::STATUS_PENDING)->count());
        $this->assertNull(QueueEntry::current());
    }
}
