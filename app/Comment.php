<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Comment extends Model
{
    protected $fillable = [
        'comment',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Comment::class, 'parent_id')->recursive();
    }

    public function votes()
    {
        return $this->hasMany(CommentVote::class);
    }

    public function scopeRecursive(Builder $query)
    {
        return $query->with([
            'children' => function ($query) {
                return $query->sortByVote();
            },
            'user:id,first_name'
        ]);
    }

    public function scopeSortByVote(Builder $query)
    {
        return $query->latest(DB::raw('`up_vote` - `down_vote`'));
    }
}
