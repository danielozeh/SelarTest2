<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class SubscriptionTransaction extends Model
{
    use HasFactory;

    public function getCreatedAtAttribute($date)
    {
        return Carbon::createFromTimestamp(strtotime($date))->format('d F Y');
    }

    public function getUpdatedAtAttribute($date)
    {
        return Carbon::createFromTimestamp(strtotime($date))->format('d F Y');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function plan() {
        return $this->belongsTo(Plan::class);
    }
}
