<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
	protected $table = 'proposal';

	public function user() {
		return $this->hasOne('App\User', 'id', 'user_id');
	}

	public function bank() {
    return $this->hasOne('App\Bank', 'proposal_id');
  }

  public function crypto() {
  	return $this->hasOne('App\Crypto', 'proposal_id');
  }

  public function grants() {
  	return $this->hasMany('App\Grant', 'proposal_id');
  }

  public function milestones() {
  	return $this->hasMany('App\Milestone', 'proposal_id');
  }

  public function signatures() {
    return $this->hasMany('App\Signature', 'proposal_id');
  }

  public function members() {
  	return $this->hasMany('App\Team', 'proposal_id');
  }

  public function citations() {
    return $this->hasMany('App\Citation', 'proposal_id');
  }

  public function files() {
    return $this->hasMany('App\ProposalFile', 'proposal_id');
  }

  public function votes() {
    return $this->hasMany('App\Vote', 'proposal_id');
  }

  public function onboarding() {
    return $this->hasOne('App\OnBoarding', 'proposal_id');
  }

  public function changes() {
    return $this->hasMany('App\ProposalChange', 'proposal_id');
  }
  
  public function teams() {
    return $this->hasMany('App\Team', 'proposal_id');
  }

  public function surveyRanks() {
    return $this->hasMany('App\SurveyRank', 'proposal_id');
  }

  public function milestoneSubmitHistories() {
    return $this->hasMany('App\MilestoneSubmitHistory', 'proposal_id');
  }
}
