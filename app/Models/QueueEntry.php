<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueEntry extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CALLING = 'calling';

    public const STATUS_CALLED = 'called';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'code',
        'position',
        'status',
        'served_at',
    ];

    protected function casts(): array
    {
        return [
            'served_at' => 'datetime',
        ];
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', self::STATUS_PENDING)->orderBy('position');
    }

    public static function current(): ?self
    {
        return static::where('status', self::STATUS_CALLING)->latest('position')->first();
    }

    public static function nextCode(): string
    {
        $position = (int) static::max('position') + 1;

        return sprintf('A-%03d', $position);
    }
}
