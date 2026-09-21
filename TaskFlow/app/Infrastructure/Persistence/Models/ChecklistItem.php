<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['card_id', 'title', 'is_complete', 'position'])]
class ChecklistItem extends Model
{
    protected function casts(): array
    {
        return [
            'is_complete' => 'boolean',
        ];
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
