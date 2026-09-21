<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'owner_id'])]
class Workspace extends Model
{
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        // Explicit pivot table name: Eloquent's default guess ("user_workspace",
        // alphabetical) doesn't match the migration's actual table
        // ("workspace_user") — this predates the refactor, just never got
        // exercised by a real query before now.
        return $this->belongsToMany(User::class, 'workspace_user')->withPivot('role')->withTimestamps();
    }

    public function boards()
    {
        return $this->hasMany(Board::class);
    }

    public function labels()
    {
        return $this->hasMany(Label::class);
    }
}
