<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['workspace_id', 'name', 'color'])]
class Label extends Model
{
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function cards()
    {
        return $this->belongsToMany(Card::class, 'card_label');
    }
}
