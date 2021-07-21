<?php

namespace App\Http;

use Illuminate\Support\Facades\Mail;

use App\EmailerTriggerAdmin;
use App\EmailerTriggerUser;
use App\EmailerTriggerMember;
use App\EmailerAdmin;
use App\Proposal;
use App\Setting;
use App\User;
use App\Profile;
use App\Vote;
use App\VoteResult;
use App\Reputation;
use App\OnBoarding;
use App\Citation;
use App\FinalGrant;
use App\SponsorCode;
use App\Signature;

use App\Mail\AdminAlert;
use App\Mail\UserAlert;

use App\Jobs\MemberAlert;
use App\Shuftipro;
use App\SignatureGrant;
use Carbon\Carbon;

class Helper {
  // Upgrade to Voting Associate
  public static function upgradeToVotingAssociate($user) {
    $count = User::where('is_member', 1)->get()->count();

    $user->is_member = 1;
    $user->assignRole('member');
    $user->save();

    $user->member_no = $count + 1;
    $user->member_at = $user->updated_at;
    $user->save();
  }

  // Generate Random Two FA Code
  public static function generateTwoFACode() {
    $randlist = ['1','2','3','4','5','6','7','8','9','A','C','E','F','G','H','K','N','P','Q','R','T','W','X','Z'];
    $code1 = $randlist[rand(0,23)];
    $code2 = $randlist[rand(0,23)];
    $code3 = $randlist[rand(0,23)];
    $code4 = $randlist[rand(0,23)];
    $code5 = $randlist[rand(0,23)];
    $code6 = $randlist[rand(0,23)];
    $code = $code1 . $code2 . $code3 . $code4 . $code5 . $code6;
    return $code;
  }

  // Generate Random String
  public static function generateRandomString($length_of_string) {
    // String of all alphanumeric character
    $str_result = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';

    // Shufle the $str_result and returns substring
    return substr(str_shuffle($str_result), 0, $length_of_string);
  }

  // Complete Proposal
  public static function completeProposal($proposal) {
    $proposal->status = 'completed';
    $proposal->save();

    $items = Reputation::where('proposal_id', $proposal->id)
                      ->where('type', 'Minted Pending')
                      ->get();

    if ($items) {
      foreach ($items as $item) {
        $user = User::with('profile')
                    ->has('profile')
                    ->where('id', $item->user_id)
                    ->first();

        $value = (float) $item->pending;
        if ($value > 0) {
          $user->profile->rep_pending = (float) $user->profile->rep_pending - $value;
          if ((float) $user->profile->rep_pending < 0)
            $user->profile->rep_pending = 0;
          $user->profile->rep = (float) $user->profile->rep + $value;
          $user->profile->save();
        }

        $item->type = 'Minted';
        $item->value = (float) $item->pending;
        $item->pending = 0;
        $item->save();
      }
    }
  }

  // Start Final Grant
  public static function startFinalGrant($proposal) {
    $finalGrant = FinalGrant::where('proposal_id', $proposal->id)->first();
    if (!$finalGrant) {
      $finalGrant = new FinalGrant;
      $finalGrant->proposal_id = $proposal->id;
      $finalGrant->user_id = $proposal->user_id;
      $finalGrant->status = 'pending';
      $finalGrant->milestones_complete = 0;
      $finalGrant->milestones_total = count($proposal->milestones);
      $finalGrant->save();
    }
    return $finalGrant;
  }

  // Get Sponsor
  public static function getSponsor($proposal) {
    $sponsor_code_id = (int) $proposal->sponsor_code_id;
    if ($sponsor_code_id) {
      $codeObject = SponsorCode::find($sponsor_code_id);
      if ($codeObject) {
        $user_id = (int) $codeObject->user_id;
        $sponsor = User::with('profile')
                        ->has('profile')
                        ->where('id', $user_id)
                        ->first();

        return $sponsor;
      }
    }

    return null;
  }

	// Run Winner Flow
	public static function runWinnerFlow($proposal, $vote, $settings) {
		$op = User::with('profile')->where('id', $proposal->user_id)->first();

    // $sponsor = self::getSponsor($proposal);
    $sponsor = null;

		$for_value = (int) $vote->for_value;
    $against_value = (int) $vote->against_value;

    // Get For Voters
    $itemsFor = VoteResult::where('proposal_id', $vote->proposal_id)
                        ->where('vote_id', $vote->id)
                        ->where('type', 'for')
                        ->get();

    // Get Against Voters
    $itemsAgainst = VoteResult::where('proposal_id', $vote->proposal_id)
                        ->where('vote_id', $vote->id)
                        ->where('type', 'against')
                        ->get();

    // Get Winning Side Voters
    $items = $itemsFor;

    // Minted Pending Rep
    $total_minted_pending = (float) $proposal->total_grant * (float) $settings['minted_ratio'];
    $total_minted_pending = round($total_minted_pending, 2);

    $op_minted_pending = $total_minted_pending * (float)((float) $settings['op_percentage'] / 100);
    $op_minted_pending = round($op_minted_pending, 2);

    $minted_pending = $total_minted_pending - $op_minted_pending;
    $minted_pending = round($minted_pending, 2);
    if ($minted_pending < 0) $minted_pending = 0;

    $op_rate = (float) $proposal->rep / ($for_value + (float) $proposal->rep);
    $op_extra = (float) $against_value * $op_rate;
    $op_extra = round($op_extra);

    // Split Algorithm - Grant Has Minted Pending
    foreach ($items as $item) {
    	$value = (int) $item->value;
      $rate = (float) $value / ($for_value + (float) $proposal->rep);

      $extra = (float) $against_value * $rate;
      $extra = round($extra, 2);

      $extra_minted = (float) $minted_pending * $rate;
      $extra_minted = round($extra_minted, 2);

      $rep = $value + $extra;
      $rep = (float) round($rep, 2);

      $voter = User::with('profile')->where('id', $item->user_id)->first();
      if ($voter && isset($voter->profile)) {
        $voter->profile->rep = (float) $voter->profile->rep + $rep;
        if (
          $proposal->type == "grant" &&
          $vote->content_type != "milestone"
        ) {
        	$voter->profile->rep_pending = (float) $voter->profile->rep_pending + $extra_minted;
        }
        $voter->profile->save();

        // Stake Returned
        if ($value != 0) {
          Reputation::where('user_id', $voter->id)
                    ->where('proposal_id', $vote->proposal_id)
                    ->where('type', 'Staked')
                    //->where('event', 'Proposal Vote')
                    ->where('vote_id', $vote->id)
                    ->delete();
				}

        // Gained
        if ($extra != 0) {
          $reputation = new Reputation;
          $reputation->user_id = $voter->id;
          $reputation->proposal_id = $vote->proposal_id;
          $reputation->vote_id = $vote->id;
          $reputation->value = $extra;
          $reputation->event = "Proposal Vote Result";
          $reputation->type = "Gained";
          $reputation->save();
        }

        // Minted Pending
        if (
          $extra_minted != 0 &&
          $proposal->type == "grant" &&
          $vote->content_type != "milestone"
        ) {
          $reputation = new Reputation;
          $reputation->user_id = $voter->id;
          $reputation->proposal_id = $vote->proposal_id;
          $reputation->vote_id = $vote->id;
          $reputation->pending = $extra_minted;
          $reputation->event = "Proposal Vote Result";
          $reputation->type = "Minted Pending";
          $reputation->save();
        }
    	}
    }

    // Stake Lost
    foreach ($itemsAgainst as $item) {
    	$voter = User::with('profile')->where('id', $item->user_id)->first();

      if ($voter && isset($voter->profile)) {
        $reputation = Reputation::where('user_id', $voter->id)
                                ->where('proposal_id', $vote->proposal_id)
                                ->where('type', 'Staked')
                                ->where('event', 'Proposal Vote')
                                ->where('vote_id', $vote->id)
                                ->first();
        if ($reputation) {
        	$value = (float) $reputation->staked;

          if ($value != 0) {
            $reputationNew = new Reputation;
            $reputationNew->user_id = $voter->id;
            $reputationNew->proposal_id = $vote->proposal_id;
            $reputationNew->vote_id = $vote->id;
            $reputationNew->value = $value;
            $reputationNew->type = "Stake Lost";
            $reputationNew->event = "Proposal Vote Result";
            $reputationNew->save();
          }

          $reputation->delete();
        }
      }
    }

    // OP
    if ($op && isset($op->profile)) {
      $citations = Citation::with([
                            'repProposal',
                            'repProposal.user',
                            'repProposal.user.profile'
                          ])
                          ->has('repProposal')
                          ->has('repProposal.user')
                          ->has('repProposal.user.profile')
                          ->where('proposal_id', $proposal->id)
                          ->get();

      $percentage = 100;
      if (
        $citations &&
        $proposal->type == "grant" &&
        $vote->content_type != "milestone"
      ) {
        foreach ($citations as $citation) {
          $p = (int) $citation->percentage;
          $percentage -= $p;

          $pending = (float)($op_minted_pending * $p / 100);
          $pending = round($pending, 2);

          if ($pending > 0) {
            $current = (float) $citation->repProposal->user->profile->rep_pending;

            $citation->repProposal->user->profile->rep_pending = $pending + $current;
            $citation->repProposal->user->profile->save();

            $reputation = new Reputation;
            $reputation->user_id = $citation->repProposal->user->id;
            $reputation->proposal_id = $vote->proposal_id;
            $reputation->vote_id = $vote->id;
            $reputation->pending = $pending;
            $reputation->event = "Proposal Vote Result - Citation";
            $reputation->type = "Minted Pending";
            $reputation->save();
          }
        }
      }
      if ($percentage < 0) $percentage = 0;

      $op_minted_pending = (float)($op_minted_pending * $percentage / 100);
      $op_minted_pending = round($op_minted_pending, 2);

      $op->profile->rep =
          (float) $op->profile->rep +
          (float) $op_extra +
          (float) $proposal->rep;

      if ($proposal->type == "grant" && $vote->content_type != "milestone") {
      	if ($sponsor) {
          $sponsor->profile->rep_pending = (float) $sponsor->profile->rep_pending + $op_minted_pending;
          $sponsor->profile->save();
        } else {
          $op->profile->rep_pending = (float) $op->profile->rep_pending + $op_minted_pending;
        }
      }

      $op->profile->save();

      // Stake Returned
      if ((float) $proposal->rep != 0) {
        Reputation::where('user_id', $op->id)
                  ->where('proposal_id', $vote->proposal_id)
                  ->where('type', 'Staked')
                  ->delete();
      }

      // Gained
      if ($op_extra != 0) {
        $reputation = new Reputation;
        $reputation->user_id = $op->id;
        $reputation->proposal_id = $vote->proposal_id;
        $reputation->vote_id = $vote->id;
        $reputation->value = $op_extra;
        $reputation->event = "Proposal Vote Result - OP";
        $reputation->type = "Gained";
        $reputation->save();
      }

      // Minted Pending
      if (
        $op_minted_pending != 0 &&
        $proposal->type == "grant" &&
        $vote->content_type != "milestone"
      ) {
        if ($sponsor) {
          $reputation = new Reputation;
          $reputation->user_id = $sponsor->id;
          $reputation->proposal_id = $vote->proposal_id;
          $reputation->vote_id = $vote->id;
          $reputation->pending = $op_minted_pending;
          $reputation->event = "Proposal Vote Result - Sponsor";
          $reputation->type = "Minted Pending";
          $reputation->save();
        } else {
          $reputation = new Reputation;
          $reputation->user_id = $op->id;
          $reputation->proposal_id = $vote->proposal_id;
          $reputation->vote_id = $vote->id;
          $reputation->pending = $op_minted_pending;
          $reputation->event = "Proposal Vote Result - OP";
          $reputation->type = "Minted Pending";
          $reputation->save();
        }
      }
    }
	}

	// Run Loser Flow
	public static function runLoserFlow($proposal, $vote, $settings) {
		$op = User::with('profile')->where('id', $proposal->user_id)->first();

    $for_value = (int) $vote->for_value;
    $against_value = (int) $vote->against_value;

    // Get For Voters
    $itemsFor = VoteResult::where('proposal_id', $vote->proposal_id)
                        ->where('vote_id', $vote->id)
                        ->where('type', 'for')
                        ->get();

    // Get Against Voters
    $itemsAgainst = VoteResult::where('proposal_id', $vote->proposal_id)
                        ->where('vote_id', $vote->id)
                        ->where('type', 'against')
                        ->get();

    // Get Losing Side Voters
    $items = $itemsAgainst;

    // Split Algorithm - Has No Minted Pending
    foreach ($items as $item) {
    	$value = (int) $item->value;
      $rate = (float) $value / $against_value;

      $extra = (float) ($for_value + (float) $proposal->rep) * $rate;
      $extra = round($extra, 2);

      $rep = $value + $extra;
      $rep = (float) round($rep, 2);

     	$voter = User::with('profile')->where('id', $item->user_id)->first();
      if ($voter && isset($voter->profile)) {
        $voter->profile->rep = (float) $voter->profile->rep + $rep;
        $voter->profile->save();

        // Stake Returned
        if ($value != 0) {
          Reputation::where('user_id', $voter->id)
                    ->where('proposal_id', $vote->proposal_id)
                    ->where('vote_id', $vote->id)
                    ->where('type', 'Staked')
                    //->where('event', 'Proposal Vote')
                    ->delete();
        }

        // Gained
        if ($extra != 0) {
          $reputation = new Reputation;
          $reputation->user_id = $voter->id;
          $reputation->proposal_id = $vote->proposal_id;
          $reputation->vote_id = $vote->id;
          $reputation->value = $extra;
          $reputation->event = "Proposal Vote Result";
          $reputation->type = "Gained";
          $reputation->save();
        }
      }
    }

    // Stake Lost
    foreach ($itemsFor as $item) {
      $voter = User::with('profile')->where('id', $item->user_id)->first();

      if ($voter && isset($voter->profile)) {
        $reputation = Reputation::where('user_id', $voter->id)
                                ->where('proposal_id', $vote->proposal_id)
                                ->where('vote_id', $vote->id)
                                ->where('event', 'Proposal Vote')
                                ->where('type', 'Staked')
                                ->first();
        if ($reputation) {
          $value = (float) $reputation->staked;

	        if ($value != 0) {
            $reputationNew = new Reputation;
            $reputationNew->user_id = $voter->id;
            $reputationNew->proposal_id = $vote->proposal_id;
            $reputationNew->vote_id = $vote->id;
            $reputationNew->value = $value;
            $reputationNew->type = "Stake Lost";
            $reputationNew->event = "Proposal Vote Result";
            $reputationNew->save();
	        }

          $reputation->delete();
        }
      }
    }

    // OP
    if ($op && isset($op->profile)) {
      // Create Reputation Track
      $reputation = Reputation::where('user_id', $op->id)
                              ->where('proposal_id', $vote->proposal_id)
                              ->where('type', 'Staked')
                              ->first();
      if ($reputation) {
      	$value = (float) $reputation->staked;

        if ($value != 0) {
          $reputationNew = new Reputation;
          $reputationNew->user_id = $op->id;
          $reputationNew->proposal_id = $vote->proposal_id;
          $reputationNew->vote_id = $vote->id;
          $reputationNew->value = $value;
          $reputationNew->type = "Stake Lost";
          $reputationNew->event = "Proposal Vote Result - OP";
          $reputationNew->save();
        }

        $reputation->delete();
      }
    }
	}

	// Give Vote Rep Back
	public static function clearVoters($vote) {
		if ($vote->type != "formal") return false;

    $items = VoteResult::where('proposal_id', $vote->proposal_id)
                        ->where('vote_id', $vote->id)
                        ->get();

    foreach ($items as $item) {
    	$userId = (int) $item->user_id;
    	$value = (int) $item->value;

    	$profile = Profile::where('user_id', $userId)->first();
    	if ($profile) {
    		$profile->rep = (float) $profile->rep + $value;
    		$profile->save();

    		Reputation::where('user_id', $userId)
                  ->where('proposal_id', $vote->proposal_id)
                  ->where('vote_id', $vote->id)
                  ->where('type', 'Staked')
                  ->delete();
    	}
    }
	}

  // Send Admin Email
  public static function triggerAdminEmail($title, $emailerData, $proposal = null, $vote = null, $user = null) {
    if (count($emailerData['admins'] ?? [])) {
      $item = $emailerData['triggerAdmin'][$title] ?? null;
      if ($item) {
        $content = $item['content'];
        $subject =$item['subject'];
        if ($proposal) {
            $content = str_replace('[title]', $proposal->title, $content);
            $content = str_replace('[number]', $proposal->id, $content);
            $content = str_replace('[proposal title]', $proposal->title, $content);
            $content = str_replace('[proposal number]', $proposal->id, $content);
        }
        if ($vote) {
          $content = str_replace('[voteType]', $vote->type, $content);
          $content = str_replace('[voteContentType]', $vote->content_type, $content);
          $content = str_replace('[voteId]', $vote->id, $content);
        }
        if ($user) {
          $name =  $user->first_name . ' ' .  $user->last_name;
          $content = str_replace('[first_name]', $user->first_name, $content);
          $content = str_replace('[last_name]', $user->last_name, $content);

          $subject = str_replace('[name]', $name, $subject);
        }
        Mail::to($emailerData['admins'])->send(new AdminAlert($subject, $content));
      }
    }
  }

  // Send User Email
  public static function triggerUserEmail($to, $title, $emailerData, $proposal = null, $vote = null, $user = null, $extra = []) {
    $item = $emailerData['triggerUser'][$title] ?? null;
    if ($item) {
      $content = $item['content'];
      if ($proposal) {
        $content = str_replace('[title]', $proposal->title, $content);
      }
      if (isset($extra['pendingChangesCount'])) {
        $content = str_replace('[pendingChangesCount]', $extra['pendingChangesCount'], $content);
      }
      if (isset($extra['url'])) {
        $content = str_replace('[url]', $extra['url'], $content);
      }
      if ($vote) {
        $content = str_replace('[voteType]', $vote->type, $content);
        $content = str_replace('[voteContentType]', $vote->content_type, $content);
        $content = str_replace('[voteId]', $vote->id, $content);
      }
      if ($user) {
        $content = str_replace('[first_name]', $user->first_name, $content);
        $content = str_replace('[last_name]', $user->last_name, $content);
      }
      Mail::to($to)->send(new UserAlert($item['subject'], $content));
    }
  }

  // Send Member Email
  public static function triggerMemberEmail($title, $emailerData, $proposal = null, $vote = null, $discusstions = null, $votingToday = null,  $noQuorumVotes = null) {
    $item = $emailerData['triggerMember'][$title] ?? null;
    if ($item) {
      $subject = $item['subject'];
      $content = $item['content'];

      if ($proposal) {
        $subject = str_replace('[type]', $proposal->type, $subject);
        $content = str_replace('[title]', $proposal->title, $content);
        $content = str_replace('[content]', $proposal->short_description, $content);
      }

      if ($vote) {
        $subject = str_replace('[voteContentType]', $vote->content_type, $subject);
      }
      if($discusstions) {
        $titleDiscussion = '';
        foreach ($discusstions as  $value) {
          $titleDiscussion .= "- $value->title <br>";
        }
        $content = str_replace('[Proposal Tittle Discussions]', $titleDiscussion, $content);
      }
      if($votingToday) {
        $titleVoting = '';
        foreach ($votingToday as  $value) {
          $titleVoting .= "- $value->title - $value->type $value->content_type Vote <br>";
        }
        $content = str_replace('[Proposal started vote today]', $titleVoting, $content);
      }
      $now = Carbon::parse('UTC');
      if($noQuorumVotes) {
        $titleNoQuorum = '';
        foreach ($noQuorumVotes as  $value) {
          $titleNoQuorum .= "- $value->title <br>";
        }
        $content = str_replace('[Proposal not reached quorum]', $titleNoQuorum, $content);
      }
      $members = User::where('is_member', 1)
                      ->where('banned', 0)
                      ->get();

      if ($members) {
        foreach ($members as $member) {
          MemberAlert::dispatch($member, $subject, $content);
        }
      }
    }
  }

  // Send Membership Hellosign Request
  public static function sendMembershipHellosign($user, $proposal, $settings) {
    $client = new \HelloSign\Client(config('services.hellosign.api_key'));

    $request = new \HelloSign\TemplateSignatureRequest;
    // $request->enableTestMode();

    $request->setTemplateId('433b94ed3747e2d7a0831e3fc0a6bd0ab33f7d78');
    $request->setSubject('Membership Amendment');
    $request->setClientId(config('services.hellosign.client_id'));

    // OP Signer
    $request->setSigner(
      'OP',
      $user->email,
      $user->first_name . ' ' . $user->last_name
    );

    if (isset($settings['coo_email']) && $settings['coo_email']) {
      // COO Signer
      $request->setSigner(
        'COO',
        $settings['coo_email'],
        'COO'
      );
    }

    $response = $client->sendTemplateSignatureRequest($request);
    $signature_request_id = $response->getId();

    $proposal->membership_signature_request_id = $signature_request_id;
    $proposal->save();

    return $response;
  }

  // Send Onboarding Hellosign Request 1
  public static function sendOnboardingHellosign1($user, $proposal, $settings) {
    $client = new \HelloSign\Client(config('services.hellosign.api_key'));

    $request = new \HelloSign\TemplateSignatureRequest;
    // $request->enableTestMode();

    $request->setTemplateId('433b94ed3747e2d7a0831e3fc0a6bd0ab33f7d78');
    $request->setSubject('Grant Agreement');
    if($proposal->pdf){
      $urlFile = public_path() . $proposal->pdf;
      $request->addFile($urlFile);
    }

    $request->setCustomFieldValue('FullName', $user->first_name . ' ' . $user->last_name);

    $initialA = substr($user->first_name, 0, 1);
    $initialB = substr($user->last_name, 0, 1);
    $request->setCustomFieldValue('Initial', $initialA . ' ' . $initialB);
    $request->setCustomFieldValue('ProjectTitle', $proposal->title);
    $request->setCustomFieldValue('ProjectDescription', $proposal->short_description);
    $request->setCustomFieldValue('ProposalId', $proposal->id);
    $request->setCustomFieldValue('TotalGrant', number_format($proposal->total_grant, 2));
    $request->setClientId(config('services.hellosign.client_id'));

    $request->setSigner(
      'OP',
      $user->email,
      $user->first_name . ' ' . $user->last_name
    );

    // OP Signer
    $signature = Signature::where('role', 'OP')
                            ->where('email', $user->email)
                            ->first();
    if (!$signature) $signature = new Signature;
    $signature->proposal_id = $proposal->id;
    $signature->name = $user->first_name . ' ' . $user->last_name;
    $signature->email = $user->email;
    $signature->role = 'OP';
    $signature->signed = 0;
    $signature->save();

    // if (isset($settings['coo_email']) && $settings['coo_email']) {
    //   $request->setSigner(
    //     'COO',
    //     $settings['coo_email'],
    //     'COO'
    //   );
    //   // COO Signer
    //   $signature = Signature::where('role', 'COO')
    //                           ->where('email', $settings['coo_email'])
    //                           ->first();
    //   if (!$signature) $signature = new Signature;
    //   $signature->proposal_id = $proposal->id;
    //   $signature->name = 'COO';
    //   $signature->email = $settings['coo_email'];
    //   $signature->role = 'COO';
    //   $signature->signed = 0;
    //   $signature->save();
    // }

    // if (isset($settings['cfo_email']) && $settings['cfo_email']) {
    //   $request->setSigner(
    //     'CFO',
    //     $settings['cfo_email'],
    //     'CFO'
    //   );
    //   // CFO Signer
    //   $signature = Signature::where('role', 'CFO')
    //                           ->where('email', $settings['cfo_email'])
    //                           ->first();
    //   if (!$signature) $signature = new Signature;
    //   $signature->proposal_id = $proposal->id;
    //   $signature->name = 'CFO';
    //   $signature->email = $settings['cfo_email'];
    //   $signature->role = 'CFO';
    //   $signature->signed = 0;
    //   $signature->save();
    // }

    $response = $client->sendTemplateSignatureRequest($request);
    $signature_request_id = $response->getId();

    $proposal->signature_request_id = $signature_request_id;
    $proposal->save();

    return $response;
  }

	// Start Onboarding
	public static function startOnboarding($proposal, $vote, $status = 'pending') {
		if ($vote->type != "informal") return null;

		$onboarding = OnBoarding::where('proposal_id', $proposal->id)
                            ->where('vote_id', $vote->id)
                            ->first();
    if (!$onboarding) {
        $onboarding = new OnBoarding;
        $onboarding->proposal_id = $proposal->id;
        $onboarding->vote_id = $vote->id;
        $onboarding->user_id = $proposal->user_id;
        $onboarding->status = $status;
        $onboarding->save();

        return $onboarding;
    }

    return null;
	}

	// Start Formal Vote
	public static function startFormalVote($vote) {
		if ($vote->type != "informal") return false;

		$proposal_id = (int) $vote->proposal_id;
    $temp = Vote::where('proposal_id', $proposal_id)
                ->where('type', 'formal')
                ->where('content_type', '!=', 'milestone')
                ->first();

    if (!$temp) {
      $temp = new Vote;
      $temp->proposal_id = $proposal_id;
      $temp->type = 'formal';
      $temp->content_type = $vote->content_type;
      $temp->status = 'active';
      $temp->save();

      // Save Formal Voting ID
      $vote->formal_vote_id = (int) $temp->id;
      $vote->save();

      return $temp;
    }

    return null;
	}

	// Get Vote Result
	public static function getVoteResult($proposal, $vote, $settings) {
		$pass_rate = 0;
		if ($vote->content_type == "grant")
			$pass_rate = $settings["pass_rate"] ?? 0;
		else if ($vote->content_type == "simple")
			$pass_rate = $settings["pass_rate_simple"] ?? 0;
    else if ($vote->content_type == "milestone")
      $pass_rate = $settings["pass_rate_milestone"] ?? 0;

		$pass_rate = (float) $pass_rate;

		$for_value = (float) $vote->for_value;
		$against_value = (float) $vote->against_value;
		$total = $for_value + $against_value;

		$standard = (float)($total * $pass_rate / 100);

		if ($for_value > $standard) return "success";
		return "fail";
	}

	// Get Total Members
	public static function getTotalMembers() {
		$totalMembers = User::where('is_member', 1)
												->where('banned', 0)
												->where('can_access', 1)
												->get()
												->count();
		return $totalMembers;
	}

  // Get Total Members
  public static function getTotalMemberProposal($proposalId)
  {
    $proposal = Proposal::where('id',
      $proposalId
    )->first();
    if ($proposal->type == 'grant') {
      $vote = Vote::where('proposal_id', $proposalId)->where('type', 'formal')
      ->where('content_type', 'milestone')->first();
      if ($vote) {
        $result = Vote::where('proposal_id', $proposalId)->where('type', 'informal')
          ->where('content_type', 'milestone')->orderBy('created_at', 'desc')->first();
        return $result->result_count ?? self::getTotalMembers();
      }
      $vote =  Vote::where('proposal_id', $proposalId)->where('type', 'informal')
        ->where('content_type', 'milestone')->first();
      if($vote) {
        return self::getTotalMembers();
      }
      $vote = Vote::where('proposal_id', $proposalId)->where('type', 'formal')
      ->where('content_type', 'grant')->first();
      if ($vote) {
        $result = Vote::where('proposal_id', $proposalId)->where('type', 'informal')
          ->where('content_type', 'grant')->orderBy('created_at', 'desc')->first();
        return $result->result_count ?? self::getTotalMembers();
      }

      return self::getTotalMembers();
    } else {
      $vote = Vote::where('proposal_id', $proposalId)->where('type', 'formal')
      ->where('content_type', 'simple')->first();
      if ($vote) {
        $result = Vote::where('proposal_id', $proposalId)->where('type', 'informal')
          ->where('content_type', 'simple')->orderBy('created_at', 'desc')->first();
        return $result->result_count ?? self::getTotalMembers();
      }
      return self::getTotalMembers();
    }
    return self::getTotalMembers();
  }

	// Get Settings
	public static function getSettings() {
		// Get Settings
    $settings = [];
    $items = Setting::get();
    if ($items) {
      foreach ($items as $item) {
      	$settings[$item->name] = $item->value;
      }
    }
    return $settings;
	}

	// Get Membership Proposal
	public static function getMembershipProposal($user) {
		return null;
	}

	// Get Emailer Data
	public static function getEmailerData() {
		$data = [
			'admins' => [],
			'triggerAdmin' => [],
			'triggerUser' => [],
      'triggerMember' => []
		];

		$admins = EmailerAdmin::where('id', '>', 0)
													->orderBy('email', 'asc')->get();
		$triggerAdmin = EmailerTriggerAdmin::where('id', '>', 0)
																				->orderBy('id', 'asc')
																				->get();
		$triggerUser = EmailerTriggerUser::where('id', '>', 0)
																			->orderBy('id', 'asc')
																			->get();
    $triggerMember = EmailerTriggerMember::where('id', '>', 0)
                                          ->orderBy('id', 'asc')
                                          ->get();

		if ($admins && count($admins)) {
			foreach ($admins as $admin) {
				$data['admins'][] = $admin->email;
			}
		}

		if ($triggerAdmin && count($triggerAdmin)) {
			foreach ($triggerAdmin as $item) {
				if ((int) $item->enabled)
					$data['triggerAdmin'][$item->title] = $item;
				else
					$data['triggerAdmin'][$item->title] = null;
			}
		}

		if ($triggerUser && count($triggerUser)) {
			foreach ($triggerUser as $item) {
				if ((int) $item->enabled)
					$data['triggerUser'][$item->title] = $item;
				else
					$data['triggerUser'][$item->title] = null;
			}
		}

    if ($triggerMember && count($triggerMember)) {
      foreach ($triggerMember as $item) {
        if ((int) $item->enabled)
          $data['triggerMember'][$item->title] = $item;
        else
          $data['triggerMember'][$item->title] = null;
      }
    }

		return $data;
	}

   // Send grant Hellosign Request 1
   public static function sendGrantHellosign($user,$proposal, $settings) {
    $client = new \HelloSign\Client(config('services.hellosign.api_key'));

    $request = new \HelloSign\TemplateSignatureRequest;
    // $request->enableTestMode();

    $request->setTemplateId('8f64a36e43db1478a499d76dfdc35f1f5b430203');
    $subject = 'Verify Grant for proposal '. $proposal->id; 
    $request->setSubject($subject);
    if($proposal->pdf) {
      $urlFile = public_path() . $proposal->pdf;
      if (file_exists($urlFile)) {
        $request->addFile($urlFile);
      }
    }
    $request->setCustomFieldValue('FullName', $user->first_name . ' ' . $user->last_name);
    
    $shuftipro = Shuftipro::where('user_id', $user->id)->first();

    $initialA = substr($user->first_name, 0, 1);
    $initialB = substr($user->last_name, 0, 1);
    $request->setCustomFieldValue('Initial', $initialA . ' ' . $initialB);
    $request->setCustomFieldValue('ProjectTitle', $proposal->title);
    $request->setCustomFieldValue('ProjectDescription', $proposal->short_description);
    $request->setCustomFieldValue('ProposalId', $proposal->id);
    $request->setCustomFieldValue('TotalGrant', number_format($proposal->total_grant, 2));
    if($shuftipro) {
      $request->setCustomFieldValue('ShuftiId', $shuftipro->reference_id);
    }
    $request->setClientId(config('services.hellosign.client_id'));

    $request->setSigner(
      'OP',
      $user->email,
      $user->first_name . ' ' . $user->last_name
    );

    // OP Signer
    $signature = SignatureGrant::where('role', 'OP')
                            ->where('email', $user->email)
                            ->where('proposal_id',  $proposal->id)
                            ->first();
    if (!$signature) $signature = new SignatureGrant;
    $signature->proposal_id = $proposal->id;
    $signature->name = $user->first_name . ' ' . $user->last_name;
    $signature->email = $user->email;
    $signature->role = 'OP';
    $signature->signed = 0;
    $signature->save();

    if (isset($settings['coo_email']) && $settings['coo_email']) {
      $request->setSigner(
        'COO',
        $settings['coo_email'],
        'COO'
      );
      // COO Signer
      $signature = SignatureGrant::where('role', 'COO')
                              ->where('email', $settings['coo_email'])
                              ->where('proposal_id',  $proposal->id)
                              ->first();
      if (!$signature) $signature = new SignatureGrant;
      $signature->proposal_id = $proposal->id;
      $signature->name = 'COO';
      $signature->email = $settings['coo_email'];
      $signature->role = 'COO';
      $signature->signed = 0;
      $signature->save();
    }

    if (isset($settings['cfo_email']) && $settings['cfo_email']) {
      $request->setSigner(
        'CFO',
        $settings['cfo_email'],
        'CFO'
      );
      // CFO Signer
      $signature = SignatureGrant::where('role', 'CFO')
                              ->where('email', $settings['cfo_email'])
                             ->where('proposal_id',  $proposal->id)
                              ->first();
      if (!$signature) $signature = new SignatureGrant;
      $signature->proposal_id = $proposal->id;
      $signature->name = 'CFO';
      $signature->email = $settings['cfo_email'];
      $signature->role = 'CFO';
      $signature->signed = 0;
      $signature->save();
    }

    if (isset($settings['board_member_email']) && $settings['board_member_email']) {
      $request->setSigner(
        'BM',
        $settings['board_member_email'],
        'BM'
      );
      // board_member email Signer
      $signature = SignatureGrant::where('role', 'BM')
                              ->where('email', $settings['board_member_email'])
                             ->where('proposal_id',  $proposal->id)
                              ->first();
      if (!$signature) $signature = new SignatureGrant;
      $signature->proposal_id = $proposal->id;
      $signature->name = 'BM';
      $signature->email = $settings['board_member_email'];
      $signature->role = 'BM';
      $signature->signed = 0;
      $signature->save();
    }

    if (isset($settings['president_email']) && $settings['president_email']) {
      $request->setSigner(
        'BP',
        $settings['president_email'],
        'BP'
      );
      // board_member email Signer
      $signature = SignatureGrant::where('role', 'BP')
                              ->where('email', $settings['president_email'])
                             ->where('proposal_id',  $proposal->id)
                              ->first();
      if (!$signature) $signature = new SignatureGrant;
      $signature->proposal_id = $proposal->id;
      $signature->name = 'BP';
      $signature->email = $settings['president_email'];
      $signature->role = 'BP';
      $signature->signed = 0;
      $signature->save();
    }

    $response = $client->sendTemplateSignatureRequest($request);
    $signature_request_id = $response->getId();

    $proposal->signature_grant_request_id = $signature_request_id;
    $proposal->save();

    return $response;
  }

  public static function checkPendingFinalGrant($user)
  {
    $count = FinalGrant::where('user_id', $user->id)->where('status', 'active')->count();
    return $count > 0 ? true : false;
  }
}
