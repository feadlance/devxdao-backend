<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $table = 'survey';

    protected $guarded = [];

    public function surveyRanks()
    {
        return $this->hasMany('App\SurveyRank', 'survey_id', 'id');
    }

    public function surveyDownvoteRanks()
    {
        return $this->hasMany('App\SurveyDownVoteRank', 'survey_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function getEndTimeAttribute($value)
    {
        return Carbon::parse($value);
    }
}
