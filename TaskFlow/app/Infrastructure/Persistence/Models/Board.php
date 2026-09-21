<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['workspace_id', 'created_by', 'name'])]
class Board extends Model
{
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function lists()
    {
        return $this->hasMany(BoardList::class)->orderBy('position');
    }
}
