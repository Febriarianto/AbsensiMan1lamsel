<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmisSyncLog extends Model
{
    protected $table = 'emis_sync_logs';

    protected $fillable = [
        'total_synced',
        'status',
        'notes',
    ];
}
