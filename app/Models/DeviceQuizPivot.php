<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class DeviceQuizPivot extends Pivot
{
    protected $table = 'device_quiz';

    protected $casts = [
        'random_bank_levels' => 'array',
    ];
}
