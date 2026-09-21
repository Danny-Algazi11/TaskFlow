<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['board_list_id', 'title', 'description', 'position', 'due_date'])]
class Card extends Model
{
    public function list()
    {
        return $this->belongsTo(BoardList::class, 'board_list_id');
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'card_user');
    }

    public function labels()
    {
        return $this->belongsToMany(Label::class, 'card_label');
    }

    public function checklistItems()
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('position');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->latest();
    }
}
