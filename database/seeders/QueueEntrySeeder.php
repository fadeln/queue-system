<?php

namespace Database\Seeders;

use App\Models\QueueEntry;
use Illuminate\Database\Seeder;

class QueueEntrySeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 10) as $i) {
            $status = match ($i) {
                1 => QueueEntry::STATUS_CALLING,
                2, 3 => QueueEntry::STATUS_CALLED,
                4 => QueueEntry::STATUS_SKIPPED,
                default => QueueEntry::STATUS_PENDING,
            };

            QueueEntry::create([
                'code' => sprintf('A-%03d', $i),
                'position' => $i,
                'status' => $status,
                'served_at' => in_array($i, [2, 3], true) ? now() : null,
            ]);
        }
    }
}
