<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class County extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'badge'];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'id_county');
    }

    /**
     * A badge oszlop teljes URL-lé alakítva: a seeder által tárolt helyi
     * útvonalakból asset() készít URL-t, a kézzel megadott http(s) címek
     * változatlanul maradnak.
     */
    public function getBadgeUrlAttribute(): ?string
    {
        if (! $this->badge) {
            return null;
        }

        return str_starts_with($this->badge, 'http') ? $this->badge : asset($this->badge);
    }
}
