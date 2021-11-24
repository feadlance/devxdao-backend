<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
        return $this->hasMany(Comment::class, 'parent_id')->with(['children', 'user:id,first_name']);
    }

    public function votes()
    {
        return $this->hasMany(CommentVote::class);
    }
}
