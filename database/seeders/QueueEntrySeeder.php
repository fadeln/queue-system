<?php

namespace Database\Seeders;

use App\Models\QueueEntry;
use Illuminate\Database\Seeder;

class QueueEntrySeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 10) as $i) {
            QueueEntry::create([
                'code' => sprintf('A-%03d', $i),
                'position' => $i,
                'status' => $i === 1 ? QueueEntry::STATUS_CALLING : QueueEntry::STATUS_PENDING,
            ]);
        }
    }
}
