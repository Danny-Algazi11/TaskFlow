<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['card_id', 'user_id', 'body'])]
class Comment extends Model
{
    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
