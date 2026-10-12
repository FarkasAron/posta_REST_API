<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'zip_code', 'id_county', 'population'];

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class, 'id_county');
    }
}
