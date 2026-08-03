<?php

namespace App\Http\Controllers;

use App\Events\QueueUpdated;
use App\Models\QueueEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QueueController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('queue/board', [
            'snapshot' => $this->snapshot(),
        ]);
    }

    public function dashboard(): Response
    {
        $current = QueueEntry::current();

        return Inertia::render('dashboard', [
            'stats' => [
                'current' => $current?->code,
                'waiting' => QueueEntry::where('status', QueueEntry::STATUS_PENDING)->count(),
                'servedToday' => QueueEntry::where('status', QueueEntry::STATUS_CALLED)
                    ->whereDate('served_at', today())
                    ->count(),
            ],
        ]);
    }

    public function display(): Response
    {
        return Inertia::render('queue/display', [
            'snapshot' => $this->snapshot(),
        ]);
    }

    public function next(): RedirectResponse
    {
        DB::transaction(function (): void {
            $current = QueueEntry::current();

            if ($current !== null) {
                $current->update([
                    'status' => QueueEntry::STATUS_CALLED,
                    'served_at' => now(),
                ]);
            }

            $next = QueueEntry::waiting()->first();

            if ($next !== null) {
                $next->update(['status' => QueueEntry::STATUS_CALLING]);
            }
        });

        broadcast(QueueUpdated::snapshot());

        return back();
    }

    public function prev(): RedirectResponse
    {
        DB::transaction(function (): void {
            $current = QueueEntry::current();

            if ($current !== null) {
                $current->update(['status' => QueueEntry::STATUS_PENDING]);
            }

            $previous = QueueEntry::where('status', QueueEntry::STATUS_CALLED)
                ->whereNotNull('served_at')
                ->orderByDesc('served_at')
                ->first();

            if ($previous !== null) {
                $previous->update([
                    'status' => QueueEntry::STATUS_CALLING,
                    'served_at' => null,
                ]);
            }
        });

        broadcast(QueueUpdated::snapshot());

        return back();
    }

    public function markCalled(int $entry): RedirectResponse
    {
        QueueEntry::findOrFail($entry)->update([
            'status' => QueueEntry::STATUS_CALLED,
            'served_at' => now(),
        ]);

        broadcast(QueueUpdated::snapshot());

        return back();
    }

    public function store(): RedirectResponse
    {
        $position = (int) QueueEntry::max('position') + 1;

        QueueEntry::create([
            'code' => sprintf('A-%03d', $position),
            'position' => $position,
            'status' => QueueEntry::STATUS_PENDING,
        ]);

        broadcast(QueueUpdated::snapshot());

        return back();
    }

    public function cancel(int $entry): RedirectResponse
    {
        $queueEntry = QueueEntry::findOrFail($entry);

        abort_unless($queueEntry->status === QueueEntry::STATUS_PENDING, 403);

        $queueEntry->update(['status' => QueueEntry::STATUS_SKIPPED]);

        broadcast(QueueUpdated::snapshot());

        return back();
    }

    public function reset(): RedirectResponse
    {
        DB::transaction(function (): void {
            QueueEntry::query()->update([
                'status' => QueueEntry::STATUS_PENDING,
                'served_at' => null,
            ]);
        });

        broadcast(QueueUpdated::snapshot());

        return back();
    }

    private function snapshot(): array
    {
        $current = QueueEntry::current();

        return [
            'current' => $current?->code,
            'entries' => QueueEntry::orderBy('position')->get()
                ->map(fn (QueueEntry $entry) => [
                    'id' => $entry->id,
                    'code' => $entry->code,
                    'position' => $entry->position,
                    'status' => $entry->status,
                ])
                ->values()
                ->toArray(),
        ];
    }
}
