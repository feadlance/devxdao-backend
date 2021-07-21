<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\User;
use App\PreRegister;
use App\Profile;
use App\PendingAction;
use App\Proposal;
use App\ProposalHistory;
use App\ProposalChange;
use App\ProposalChangeSupport;
use App\ProposalChangeComment;
use App\Bank;
use App\Crypto;
use App\Grant;
use App\Milestone;
use App\Team;
use App\Setting;
use App\Vote;
use App\VoteResult;
use App\OnBoarding;
use App\Reputation;
use App\EmailerTriggerAdmin;
use App\EmailerTriggerUser;
use App\EmailerTriggerMember;
use App\EmailerAdmin;
use App\Shuftipro;
use App\ShuftiproTemp;
use App\FinalGrant;

use App\Http\Helper;

use App\Mail\AdminAlert;
use App\Mail\UserAlert;
use App\Mail\ResetKYC;
use App\SignatureGrant;

class AdminController extends Controller
{
	// Test Email
	public function testEmail() {
		// Emailer Admin
    $emailerData = Helper::getEmailerData();
	}

	// Update Emailer Trigger Member
	public function updateEmailerTriggerMember($recordId, Request $request) {
		$user = Auth::user();
		if ($user && $user->hasRole('admin')) {
			$record = EmailerTriggerMember::find($recordId);

			if ($record) {
				$enabled = (int) $request->get('enabled');
				$content = $request->get('content');

				$record->enabled = $enabled;
				if ($content) $record->content = $content;

				$record->save();

				return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// Update Emailer Trigger Admin
	public function updateEmailerTriggerAdmin($recordId, Request $request) {
		$user = Auth::user();
		if ($user && $user->hasRole('admin')) {
			$record = EmailerTriggerAdmin::find($recordId);

			if ($record) {
				$enabled = (int) $request->get('enabled');
				$record->enabled = $enabled;
				$record->save();

				return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// Update Emailer Trigger User
	public function updateEmailerTriggerUser($recordId, Request $request) {
		$user = Auth::user();
		if ($user && $user->hasRole('admin')) {
			$record = EmailerTriggerUser::find($recordId);

			if ($record) {
				$enabled = (int) $request->get('enabled');
				$content = $request->get('content');

				$record->enabled = $enabled;
				if ($content) $record->content = $content;

				$record->save();

				return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// Delete Emailer Admin
	public function deleteEmailerAdmin($adminId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			EmailerAdmin::where('id', $adminId)->delete();
			return ['success' => true];
		}

		return ['success' => false];
	}

	// Add Emailer Admin
	public function addEmailerAdmin(Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$email = $request->get('email');
			if (!$email) {
				return [
					'success' => false,
					'message' => 'Invalid email address'
				];
			}

			$record = EmailerAdmin::where('email', $email)->first();
			if ($record) {
				return [
					'success' => false,
					'message' => 'This emailer admin email address is already in use'
				];
			}

			$record = new EmailerAdmin;
			$record->email = $email;
			$record->save();

			return ['success' => true];
		}

		return ['success' => false];
	}

	// Add Reputation
	public function addReputation(Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$userId = (int) $request->get('userId');
			$reputation = (int) $request->get('reputation');

			// User Check
			$user = User::with('profile')->where('id', $userId)->first();
			if (!$user || !isset($user->profile)) {
				return [
					'success' => false,
					'message' => 'Invalid user'
				];
			}

			// Reputation Check
			if ($reputation <= 0) {
				return [
					'success' => false,
					'message' => 'Invalid reputation value'
				];
			}

			$user->profile->rep = (float) $user->profile->rep + $reputation;
			$user->profile->save();

			// Create Reputation Tracking
			$record = new Reputation;
			$record->user_id = $user->id;
			$record->value = $reputation;
			$record->event = "Admin Action";
			$record->type = "Gained";
			$record->save();

			return ['success' => true];
		}

		return ['success' => false];
	}

	// Subtract Reputation
	public function subtractReputation(Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$userId = (int) $request->get('userId');
			$reputation = (int) $request->get('reputation');

			// User Check
			$user = User::with('profile')->where('id', $userId)->first();
			if (!$user || !isset($user->profile)) {
				return [
					'success' => false,
					'message' => 'Invalid user'
				];
			}

			// Reputation Check
			if ($reputation <= 0) {
				return [
					'success' => false,
					'message' => 'Invalid reputation value'
				];
			}

			if ((float) $user->profile->rep < $reputation) {
				return [
					'success' => false,
					'message' => "SUBTRACT amount cannot be higher than the current reputation value"
				];
			}

			$user->profile->rep = (float) $user->profile->rep - $reputation;
			$user->profile->save();

			// Create Reputation Tracking
			$record = new Reputation;
			$record->user_id = $user->id;
			$record->value = -$reputation;
			$record->event = "Admin Action";
			$record->type = "Lost";
			$record->save();

			return ['success' => true];
		}

		return ['success' => false];
	}

	// Change User Type
	public function changeUserType(Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$userId = (int) $request->get('userId');
			$userType = $request->get('userType');

			if ($userId && $userType) {
				$user = User::find($userId);
				if (!$user) {
					return [
						'success' => false,
						'message' => 'Invalid user'
					];
				}

				if ($userType == "member" || $userType == "voting associate")
					Helper::upgradeToVotingAssociate($user);
				else if ($userType == "participant" || $userType == "associate") {
					$user->is_member = 0;
					$user->is_participant = 1;
					$user->removeRole('member');
					$user->assignRole('participant');
					$user->save();
				}

				return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// Approve KYC
	public function approveKYC($userId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$admin = $user;
			$user = User::with(['shuftipro', 'profile'])->where('id', $userId)->first();
			if ($user && $user->profile && $user->shuftipro) {
				$user->profile->step_kyc = 1;
				$user->profile->save();

				$user->shuftipro->status = 'approved';
				$user->shuftipro->reviewed = 1;
				$user->shuftipro->save();

				$user->shuftipro->manual_approved_at = $user->updated_at;
				$user->shuftipro->manual_reviewer = $admin->email;
				$user->shuftipro->save();

				// Emailer User
				$emailerData = Helper::getEmailerData();
				Helper::triggerUserEmail($user, 'AML Approve', $emailerData);

				return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// Deny KYC
	public function denyKYC($userId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$admin = $user;
			$user = User::with(['shuftipro', 'profile'])->where('id', $userId)->first();
			if ($user && $user->profile && $user->shuftipro) {
				$user->profile->step_kyc = 0;
				$user->profile->save();

				$user->shuftipro->status = 'denied';
				$user->shuftipro->reviewed = 1;
				$user->shuftipro->save();

				$user->shuftipro->manual_approved_at = $user->updated_at;
				$user->shuftipro->manual_reviewer = $admin->email;
				$user->shuftipro->save();

				// Emailer User
				$emailerData = Helper::getEmailerData();
				Helper::triggerUserEmail($user, 'AML Deny', $emailerData);

				return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// Reset KYC
	public function resetKYC($userId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$message = trim($request->get('message'));
			if (!$message) {
				return [
					'success' => false,
					'message' => 'Message is required'
				];
			}

			$user = User::with(['profile'])->where('id', $userId)->first();
			if ($user && $user->profile) {
				$user->profile->step_kyc = 0;
				$user->profile->save();

				Shuftipro::where('user_id', $user->id)->delete();
				ShuftiproTemp::where('user_id', $user->id)->delete();

				// Emailer User
        $emailerData = Helper::getEmailerData();
				Helper::triggerUserEmail($user, 'AML Reset', $emailerData);

				Mail::to($user)->send(new ResetKYC($message));

        return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// Reset User Password
	public function resetUserPassword(Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$userId = (int) $request->get('userId');
			$password = $request->get('password');

			if ($userId && $password) {
				$user = User::find($userId);
				if (!$user) {
					return [
						'success' => false,
						'message' => 'Invalid user'
					];
				}

	      $user->password = Hash::make($password);
	      $user->save();

	      return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// Get Move-To-Formal
	public function getMoveToFormalVotes(Request $request) {
		$user = Auth::user();
		$votes = [];

		// Variables
		$sort_key = $sort_direction = $search = '';
		$page_id = 0;
		$data = $request->all();
		if ($data && is_array($data)) extract($data);

		if (!$sort_key) $sort_key = 'vote.updated_at';
		if (!$sort_direction) $sort_direction = 'desc';
		$page_id = (int) $page_id;
		if ($page_id <= 0) $page_id = 1;

		$limit = isset($data['limit']) ? $data['limit'] : 10;
		$start = $limit * ($page_id - 1);

		// We need to get successfully completed informal votes
		if ($user->hasRole('admin')) {
			$votes = Vote::join('proposal', 'proposal.id', '=', 'vote.proposal_id')
											->join('users', 'users.id', '=', 'proposal.user_id')
											->where('vote.status', 'completed')
											->where('vote.result', 'success')
											->where('vote.type', 'informal')
											->where('vote.content_type', '!=', 'grant')
											->where('vote.formal_vote_id', 0)
											->where(function ($query) use ($search) {
												if ($search) {
													$query->where('proposal.title', 'like', '%' . $search . '%');
												}
											})
											->select([
												'proposal.id as proposalId',
												'proposal.type as proposalType',
												'proposal.title',
												'vote.*'
											])
											->orderBy($sort_key, $sort_direction)
											->offset($start)
											->limit($limit)
											->get();
		}

		return [
			'success' => true,
			'votes' => $votes,
			'finished' => count($votes) < $limit ? true : false
		];
	}

	// Get Pending Grant Onboardings
	public function getPendingGrantOnboardings(Request $request) {
		$user = Auth::user();
		$onboardings = [];

		// Variables
		$sort_key = $sort_direction = $search = '';
		$page_id = 0;
		$data = $request->all();
		if ($data && is_array($data)) extract($data);

		if (!$sort_key) $sort_key = 'onboarding.id';
		if (!$sort_direction) $sort_direction = 'desc';
		$page_id = (int) $page_id;
		if ($page_id <= 0) $page_id = 1;

		$limit = isset($data['limit']) ? $data['limit'] : 10;
		$start = $limit * ($page_id - 1);

		// Records
		if ($user && $user->hasRole('admin')) {
			$onboardings = OnBoarding::with([
																	'user',
																	'user.profile',
																	'user.shuftipro',
																	'user.shuftiproTemp',
																	'proposal',
																	'proposal.votes',
																	'proposal.signatures'
																])
																->has('user')
																->has('proposal')
																->join('proposal', 'proposal.id', '=', 'onboarding.proposal_id')
																->join('users', 'users.id', '=', 'onboarding.user_id')
																->leftJoin('final_grant', 'onboarding.proposal_id', '=', 'final_grant.proposal_id')
																->leftJoin('shuftipro', 'shuftipro.user_id', '=', 'onboarding.user_id')
																->where('final_grant.id', null)
																->whereNotExists(function ($query){
																	$query->select('id')
																		->from('vote')
																		->whereColumn('onboarding.proposal_id', 'vote.proposal_id')
																		->where('vote.type', 'formal');
																	})
																->where('onboarding.status', 'pending')
																->where(function ($query) use ($search) {
																	if ($search) {
																		$query->where('proposal.title', 'like', '%' . $search . '%');
																	}
																})
																->select([
																	'onboarding.*',
																	'proposal.title as proposal_title',
																	'proposal.include_membership',
																	'proposal.short_description',
																	'proposal.form_submitted',
																	'proposal.signature_request_id',
																	'proposal.hellosign_form',
																	'proposal.signed_count',
																	'shuftipro.status as shuftipro_status',
																	'shuftipro.reviewed as shuftipro_reviewed'
																])
																->orderBy($sort_key, $sort_direction)
																->offset($start)
																->limit($limit)
								                ->get();

			if ($onboardings) {
				foreach ($onboardings as $onboarding) {
					$user = $onboarding->user;
					if (
						$user &&
						isset($user->shuftipro) &&
						isset($user->shuftipro->data)
					) {
						$user->shuftipro_data = json_decode($user->shuftipro->data);
					}
				}
			}
		}

		return [
			'success' => true,
			'onboardings' => $onboardings,
			'finished' => count($onboardings) < $limit ? true : false
		];
	}

	// Get Emailer Data
	public function getEmailerData(Request $request) {
		$user = Auth::user();
		$data = [];

		if ($user && $user->hasRole('admin')) {
			$admins = EmailerAdmin::where('id', '>', 0)->orderBy('email', 'asc')->get();
			$triggerAdmin = EmailerTriggerAdmin::where('id', '>', 0)->orderBy('id', 'asc')->get();
			$triggerUser = EmailerTriggerUser::where('id', '>', 0)->orderBy('id', 'asc')->get();
			$triggerMember = EmailerTriggerMember::where('id', '>', 0)->orderBy('id', 'asc')->get();
			$data = [
				'admins' => $admins,
				'triggerAdmin' => $triggerAdmin,
				'triggerUser' => $triggerUser,
				'triggerMember' => $triggerMember,
				// 'data' => $request->headers->get('origin')
			];
		}

		return [
			'success' => true,
			'data' => $data
		];
	}

	// Start Formal Milestone Voting
	public function startFormalMilestoneVoting(Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$proposalId = (int) $request->get('proposalId');
			$voteId = (int) $request->get('voteId');

			$proposal = Proposal::find($proposalId);
			$informalVote = Vote::find($voteId);

			// Proposal Check
			if (!$proposal) {
				return [
					'success' => false,
					'message' => 'Invalid proposal'
				];
			}

			if (
				!$informalVote ||
				$informalVote->proposal_id != $proposal->id ||
				$informalVote->formal_vote_id ||
				$informalVote->content_type != "milestone"
			) {
				return [
					'success' => false,
					'message' => "Formal vote can't be started"
				];
			}

			$vote = new Vote;
			$vote->proposal_id = $informalVote->proposal_id;
			$vote->type = "formal";
			$vote->status = "active";
			$vote->content_type = "milestone";
			$vote->milestone_id = $informalVote->milestone_id;
			$vote->save();

			$informalVote->formal_vote_id = $vote->id;
			$informalVote->save();

			// Emailer Admin
			$emailerData = Helper::getEmailerData();
			Helper::triggerAdminEmail('Vote Started', $emailerData, $proposal, $vote);

			// Emailer Member
	    Helper::triggerMemberEmail('New Vote', $emailerData, $proposal, $vote);

			return ['success' => true];
		}

		return ['success' => false];
	}

	// Start Formal Voting
	public function startFormalVoting(Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$force = (int) $request->get('force');
			$proposalId = (int) $request->get('proposalId');
			$proposal = Proposal::find($proposalId);

			// Proposal Check
			if (!$proposal) {
				return [
					'success' => false,
					'message' => 'Invalid proposal'
				];
			}

			// Informal Vote Check
			$informalVote = Vote::where('proposal_id', $proposalId)
													->where('type', 'informal')
													->where('content_type', '!=', 'milestone')
													->where('status', 'completed')
													->first();
			if (!$informalVote) {
				return [
					'success' => false,
					'message' => "Formal vote can't be started"
				];
			}

			// Onboarding Check
			$onboarding = null;
			if ($proposal->type == "grant") {
				$onboarding = OnBoarding::where('proposal_id', $proposalId)->first();
				if (!$onboarding || $onboarding->status != 'pending') {
					return [
						'success' => false,
						'message' => 'Invalid proposal'
					];
				}
			}

			/* 3 Requirements Check */
			// if (!$force && $proposal->type == "grant") {
			// 	if (!$proposal->form_submitted) {
			// 		return [
			// 			'success' => false,
			// 			'message' => "Proposal payment form should be submitted"
			// 		];
			// 	}

			// 	$op = User::with(['profile', 'shuftipro'])
			// 						->has('profile')
			// 						->has('shuftipro')
			// 						->where('id', $proposal->user_id)
			// 						->first();

			// 	if (!$op || $op->shuftipro->status != "approved") {
			// 		return [
			// 			'success' => false,
			// 			'message' => "OP should have KYC approved"
			// 		];
			// 	}

			// 	if (!$proposal->signature_request_id || !$proposal->hellosign_form) {
			// 		return [
			// 			'success' => false,
			// 			'message' => "Grant Agreement should be signed by all signers"
			// 		];
			// 	}
			// }
			/* 3 Requirements Check End */

			$vote = Helper::startFormalVote($informalVote);
			if (!$vote) {
				return [
					'success' => false,
					'message' => 'Formal vote has been already started'
				];
			}

			if ($onboarding) {
				$onboarding->status = "completed";
				$onboarding->save();
			}

			// Emailer Admin
			$emailerData = Helper::getEmailerData();
			Helper::triggerAdminEmail('Vote Started', $emailerData, $proposal, $vote);

			// Emailer Member
	    Helper::triggerMemberEmail('New Vote', $emailerData, $proposal, $vote);

			return ['success' => true];
		}

		return ['success' => false];
	}

	// Update Global Settings
	public function updateGlobalSettings(Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			// Validator
	    $validator = Validator::make($request->all(), [
		'coo_email' => 'required|email',
		'cfo_email' => 'required|email',
		'board_member_email' => 'required|email',
		'time_before_op_do' => 'required',
        'time_unit_before_op_do' => 'required',
        'can_op_start_informal' => 'required',
        'time_before_op_informal' => 'required',
        'time_unit_before_op_informal' => 'required',
        'time_before_op_informal_simple' => 'required',
        'time_unit_before_op_informal_simple' => 'required',
        'time_informal' => 'required',
        'time_unit_informal' => 'required',
        'time_formal' => 'required',
        'time_unit_formal' => 'required',
        'time_simple' => 'required',
        'time_unit_simple' => 'required',
        'time_milestone' => 'required',
        'time_unit_milestone' => 'required',
        'dos_fee_amount' => 'required',
        'btc_address' => 'required',
        'eth_address' => 'required',
        'rep_amount' => 'required',
        'minted_ratio' => 'required',
        'op_percentage' => 'required',
        'pass_rate' => 'required',
        'quorum_rate' => 'required',
        'pass_rate_simple' => 'required',
        'quorum_rate_simple' => 'required',
        'pass_rate_milestone' => 'required',
        'quorum_rate_milestone' => 'required',
        'need_to_approve' => 'required',
        'autostart_formal_votes' => 'required',
        'autoactivate_grants' => 'required',
        'president_email' => 'required',
		'gate_new_grant_votes' => 'required'
	    ]);
	    if ($validator->fails()) {
	    	return [
	    		'success' => false,
	    		'message' => 'Provide all the necessary information'
	    	];
	    }

	    $items = [
	    	'coo_email' => $request->get('coo_email'),
	    	'cfo_email' => $request->get('cfo_email'),
	    	'board_member_email' => $request->get('board_member_email'),
	    	'time_before_op_do' => $request->get('time_before_op_do'),
	    	'time_unit_before_op_do' => $request->get('time_unit_before_op_do'),
	    	'can_op_start_informal' => $request->get('can_op_start_informal'),
	    	'time_before_op_informal' => $request->get('time_before_op_informal'),
	    	'time_unit_before_op_informal' => $request->get('time_unit_before_op_informal'),
	    	'time_before_op_informal_simple' => $request->get('time_before_op_informal_simple'),
	    	'time_unit_before_op_informal_simple' => $request->get('time_unit_before_op_informal_simple'),
	    	'time_informal' => $request->get('time_informal'),
	    	'time_unit_informal' => $request->get('time_unit_informal'),
	    	'time_formal' => $request->get('time_formal'),
	    	'time_unit_formal' => $request->get('time_unit_formal'),
	    	'time_simple' => $request->get('time_simple'),
	    	'time_unit_simple' => $request->get('time_unit_simple'),
	    	'time_milestone' => $request->get('time_milestone'),
	    	'time_unit_milestone' => $request->get('time_unit_milestone'),
	    	'dos_fee_amount' => $request->get('dos_fee_amount'),
        'btc_address' => $request->get('btc_address'),
        'eth_address' => $request->get('eth_address'),
        'rep_amount' => $request->get('rep_amount'),
        'minted_ratio' => $request->get('minted_ratio'),
        'op_percentage' => $request->get('op_percentage'),
        'pass_rate' => $request->get('pass_rate'),
        'quorum_rate' => $request->get('quorum_rate'),
        'pass_rate_simple' => $request->get('pass_rate_simple'),
        'quorum_rate_simple' => $request->get('quorum_rate_simple'),
        'pass_rate_milestone' => $request->get('pass_rate_milestone'),
        'quorum_rate_milestone' => $request->get('quorum_rate_milestone'),
        'need_to_approve' => $request->get('need_to_approve'),
        'autostart_formal_votes' => $request->get('autostart_formal_votes'),
        'autoactivate_grants' => $request->get('autoactivate_grants'),
        'president_email' => $request->get('president_email'),
        'gate_new_grant_votes' => $request->get('gate_new_grant_votes'),
	    ];

	    foreach ($items as $name => $value) {
	    	$setting = Setting::where('name', $name)->first();
	    	if ($setting) {
	    		$setting->value = $value;
	    		$setting->save();
	    	} else {
				$setting = new Setting();
				$setting->value = $value;
	    		$setting->save();
			}
	    }

	    return ['success' => true];
		}

		return ['success' => false];
	}

	// Approve Payment Proposal
	public function approveProposalPayment($proposalId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			// Proposal Check
			$proposal = Proposal::find($proposalId);

			if (!$proposal || $proposal->status != "payment" || !$proposal->dos_paid) {
				return [
					'success' => false,
					'message' => 'Invalid proposal'
				];
			}

			// Update Proposal
			$proposal->status = "approved";
			$proposal->save();

			// Update Timestamp
			$proposal->approved_at = $proposal->updated_at;
			$proposal->save();

			// Increase Change Count
			$proposal->changes = (int) $proposal->changes + 1;
			$proposal->save();

			$emailerData = Helper::getEmailerData();

			// Emailer User
			$op = User::find($proposal->user_id);
			if ($op) Helper::triggerUserEmail($op, 'DOS Confirmation', $emailerData);

			// Emailer Member
	    Helper::triggerMemberEmail('New Proposal Discussion', $emailerData, $proposal);

			return ['success' => true];
		}

		return ['success' => false];
	}

	// Deny Payment Proposal
	public function denyProposalPayment($proposalId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			// Proposal Check
			$proposal = Proposal::find($proposalId);
			if (!$proposal || $proposal->status != "payment" || !$proposal->dos_paid) {
				return [
					'success' => false,
					'message' => 'Invalid proposal'
				];
			}

			// This action is for crypto payments (not reputation). So we don't give rep back to OP

			$proposal->rep = 0;
			$proposal->dos_paid = 0;
			$proposal->dos_txid = null;
			$proposal->dos_eth_amount = null;
			$proposal->save();

			return ['success' => true];
		}

		return ['success' => false];
	}

	// Approve Proposal - Waiting for Payment
	public function approveProposal($proposalId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$proposal = Proposal::find($proposalId);

			if ($proposal) {
				$proposal->status = 'payment';
				$proposal->save();

				$op = User::find($proposal->user_id);

				// Emailer User
				if ($op) {
					$emailerData = Helper::getEmailerData();
					Helper::triggerUserEmail($op, 'Admin Approval', $emailerData, $proposal);
				}
			}
		}

		return ['success' => true];
	}

	// Deny Proposal
	public function denyProposal($proposalId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$reason = $request->get('reason');
			$proposal = Proposal::find($proposalId);

			if (!$reason) {
				return [
					'success' => false,
					'message' => 'Input deny reason'
				];
			}

			if (!$proposal) {
				return [
					'success' => false,
					'message' => 'Proposal does not exist'
				];
			}

			$proposal->status = 'denied';
			$proposal->deny_reason = $reason;
			$proposal->save();

			return ['success' => true];
		}

		return ['success' => false];
	}

	// Approve Participant Request from Pending Action
	public function approveParticipantRequest($userId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$participant = User::find($userId);

			if ($participant && $participant->hasRole('participant')) {
				$pendingAction = PendingAction::where('user_id', $userId)->first();

				if ($pendingAction && $pendingAction->status == 'new') {
					$pendingAction->status = 'pending_kyc';
					$pendingAction->save();
				}
			}
		}

		return ['success' => true];
	}

	// Deny Participant Request from Pending Action
	public function denyParticipantRequest($userId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$participant = User::find($userId);

			if ($participant && $participant->hasRole('participant')) {
				$pendingAction = PendingAction::where('user_id', $userId)->first();

				// Remove Pending Action
				if ($pendingAction && $pendingAction->status == 'new') {
					$pendingAction->delete();
				}
			}
		}

		return ['success' => true];
	}

	// Revoke Participant from Pending Action
	public function revokeParticipant($userId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$participant = User::find($userId);

			if ($participant && $participant->hasRole('participant')) {
				$pendingAction = PendingAction::where('user_id', $userId)->first();

				// Remove Pending Action
				if ($pendingAction && $pendingAction->status == 'pending_kyc') {
					$pendingAction->delete();
				}
			}
		}

		return ['success' => true];
	}

	// Activate Participant to Member
	public function activateParticipant($userId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$user = User::find($userId);

			if ($user) {
				$user->status = 'approved';
				$user->save();

				Helper::upgradeToVotingAssociate($user);

				// Remove Pending Action
				$pendingAction = PendingAction::where('user_id', $userId)->first();
				if ($pendingAction) $pendingAction->delete();
			}
		}

		return ['success' => true];
	}

	// Deny Participant
	public function denyParticipant($userId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$user = User::find($userId);

			if ($user) {
				$user->status = 'denied';
				$user->save();

				// Remove Pending Action
				$pendingAction = PendingAction::where('user_id', $userId)->first();
				if ($pendingAction) $pendingAction->delete();
			}
		}

		return ['success' => true];
	}

	// Activate Grant
	public function activateGrant($grantId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$finalGrant = FinalGrant::with(['proposal', 'user'])
															->has('proposal')
															->has('user')
															->where('id', $grantId)
															->first();

			if (!$finalGrant || $finalGrant->status != "pending") {
				return [
					'success' => false,
					'message' => 'Invalid grant'
				];
			}

			$file = $request->file('file');

			if ($file) {
				$path = $file->store('final_doc');
				$url = Storage::url($path);

				$finalGrant->proposal->final_document = $url;
				$finalGrant->proposal->save();

				$finalGrant->status = 'active';
				$finalGrant->save();
				$userGrant = User::where('id',$finalGrant->user_id)->first();
				if($userGrant) {
					$userGrant->check_active_grant = 1;
					$userGrant->save();
				}
				$user = $finalGrant->user;

				$emailerData = Helper::getEmailerData();
				Helper::triggerUserEmail($user, 'Grant Live', $emailerData);

				return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// begin Grant
	public function beginGrant($grantId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$finalGrant = FinalGrant::with(['proposal', 'user'])
				->has('proposal')
				->has('user')
				->where('id', $grantId)
				->first();

			if (!$finalGrant || $finalGrant->status != "pending") {
				return [
					'success' => false,
					'message' => 'Invalid grant'
				];
			}
			$signatureGrantsSigned = SignatureGrant::where('proposal_id', $finalGrant->proposal_id)->where('signed', 1)->count();
			$signatureGrantsTotal = SignatureGrant::where('proposal_id', $finalGrant->proposal_id)->count();
			if ($signatureGrantsSigned != $signatureGrantsTotal) {
				return [
					'success' => false,
					'message' => 'Please wait for the full signature'
				];
			}
			$finalGrant->status = 'active';
			$finalGrant->save();
			$userGrant = User::where('id', $finalGrant->user_id)->first();
			if($userGrant) {
				$userGrant->check_active_grant = 1;
				$userGrant->save();
			}
			$user = $finalGrant->user;

			$emailerData = Helper::getEmailerData();
			Helper::triggerUserEmail($user, 'Grant Live', $emailerData);

			return ['success' => true];
		}

		return ['success' => false];
	}

	// Approve Pre Register - Invitation
	public function approvePreRegister($recordId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$record = PreRegister::find($recordId);
			if ($record) {
				$email = $record->email;

				// User Check
				$user = User::where('email', $email)->first();
				if ($user) {
					$record->status = "completed";
					$record->hash = null;
					$record->save();

					return [
						'success' => false,
						'message' => "User with the same email address already exists"
					];
				}

				// Send Invitation
				$hash = Str::random(11);
				$record->status = 'approved';
				$record->hash = $hash;
				$record->save();

				$url = $request->header('origin') . '/register/form?hash=' . $hash;

				$emailerData = Helper::getEmailerData();
				Helper::triggerUserEmail($user, 'Pre-Register Approve', $emailerData, null, null, null, ['url' => $url]);

				return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// Deny Pre Register
	public function denyPreRegister($recordId) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$record = PreRegister::find($recordId);
			if ($record) {
				$email = $record->email;

				// User Check
				$user = User::where('email', $email)->first();
				if ($user) {
					$record->status = "completed";
					$record->hash = null;
					$record->save();

					return [
						'success' => false,
						'message' => "User with the same email address already exists"
					];
				}

				$record->status = 'denied';
				$record->hash = null;
				$record->save();

				$emailerData = Helper::getEmailerData();
				Helper::triggerUserEmail($user, 'Pre-Register Deny', $emailerData);

				return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// Allow Access User
	public function allowAccessUser($userId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$user = User::find($userId);

			if ($user && !$user->hasRole('admin')) {
				$user->can_access = 1;
				$user->save();

				// Emailer User
		    $emailerData = Helper::getEmailerData();
		    Helper::triggerUserEmail($user, 'Access Granted', $emailerData);

				return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// Deny Access User
	public function denyAccessUser($userId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$user = User::find($userId);

			if ($user && !$user->hasRole('admin') && !$user->can_access) {
				$emailerData = Helper::getEmailerData();
				Helper::triggerUserEmail($user, 'Deny Access', $emailerData);

				Profile::where('user_id', $user->id)->delete();
		    $user->delete();

				return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// Ban User
	public function banUser($userId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$user = User::find($userId);

			if ($user && !$user->hasRole('admin')) {
				$user->banned = 1;
				$user->save();

				return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// Unban User
	public function unbanUser($userId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$user = User::find($userId);

			if ($user && !$user->hasRole('admin')) {
				$user->banned = 0;
				$user->save();

				return ['success' => true];
			}
		}

		return ['success' => false];
	}

	// Reputation By User
	public function getReputationByUser($userId, Request $request) {
		$user = Auth::user();
		$items = [];

		// Variables
		$sort_key = $sort_direction = $search = '';
		$page_id = 0;
		$data = $request->all();
		if ($data && is_array($data)) extract($data);

		if (!$sort_key) $sort_key = 'reputation.id';
		if (!$sort_direction) $sort_direction = 'desc';

		// Records
		if ($user && $user->hasRole('admin')) {
			$items = Reputation::leftJoin('proposal', 'proposal.id', '=', 'reputation.proposal_id')
													->leftJoin('users', 'users.id', '=', 'proposal.user_id')
													->where('reputation.user_id', $userId)
													->select([
														'reputation.*',
														'proposal.include_membership',
														'proposal.title as proposal_title',
														'users.first_name as op_first_name',
														'users.last_name as op_last_name'
													])
    											->orderBy($sort_key, $sort_direction)
													->get();
		}

		return [
			'success' => true,
			'items' => $items
		];
	}

	// Proposals By User
	public function getProposalsByUser($userId, Request $request) {
		$user = Auth::user();
		$proposals = [];

		// Variables
		$sort_key = $sort_direction = '';
		$data = $request->all();
		if ($data && is_array($data)) extract($data);

		if (!$sort_key) $sort_key = 'proposal.id';
		if (!$sort_direction) $sort_direction = 'desc';

		// Records
		if ($user && $user->hasRole('admin')) {
			$proposals = Proposal::with(['votes', 'onboarding'])
														->where('user_id', $userId)
														->orderBy($sort_key, $sort_direction)
						                ->get();
		}

		return [
			'success' => true,
			'proposals' => $proposals
		];
	}

	// Votes By User
	public function getVotesByUser($userId, Request $request) {
		$user = Auth::user();
		$items = [];

		// Variables
		$sort_key = $sort_direction = '';
		$data = $request->all();
		if ($data && is_array($data)) extract($data);

		if (!$sort_key) $sort_key = 'vote_result.id';
		if (!$sort_direction) $sort_direction = 'desc';

		// Records
		if ($user && $user->hasRole('admin')) {
			$items = VoteResult::with(['proposal', 'vote'])
													->has('proposal')
													->has('vote')
													->where('user_id', $userId)
													->orderBy($sort_key, $sort_direction)
													->get();
		}

		return [
			'success' => true,
			'items' => $items
		];
	}

	// Single Proposal By Id
	public function getProposalById($proposalId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$proposal = Proposal::where('id', $proposalId)
														->with(['bank', 'crypto', 'grants', 'citations', 'milestones', 'members', 'files'])
														->first();

			if ($proposal) {
				// Latest Changes
				$sections = ['short_description', 'total_grant', 'previous_work', 'other_work'];
				$changes = [];

				foreach ($sections as $section) {
					$change = ProposalChange::where('proposal_id', $proposal->id)
																	->where('what_section', $section)
																	->where('status', 'approved')
																	->orderBy('updated_at', 'desc')
																	->first();

					if ($change) {
						$changes[$section] = $change;
					}
				}

				$proposal->changes = $changes;

				return [
					'success' => true,
					'proposal' => $proposal
				];
			}
		}

		return ['success' => false];
	}

	// Pending Actions DataTable
	public function getPendingActions(Request $request) {
		$user = Auth::user();
		$actions = [];
		$total = 0;

		if ($user && $user->hasRole('admin')) {
			$page_id = (int) $request->get('page_id');
      $page_length = (int) $request->get('page_length');
      $sort_key = $request->get('sort_key');
      $sort_direction = $request->get('sort_direction');

      if ($page_id < 1) $page_id = 1;
      $start = ($page_id - 1) * $page_length;

      $total = PendingAction::join('users', 'pending_actions.user_id', '=', 'users.id')
      											->join('profile', 'users.id', '=', 'profile.user_id')
      											->get()
      											->count();

      $actions = PendingAction::join('users', 'pending_actions.user_id', '=', 'users.id')
      												->join('profile', 'users.id', '=', 'profile.user_id')
      												->select([
      													'pending_actions.*',
      													'users.email',
      													'users.first_name',
      													'users.last_name',
      													'users.status as user_status',
      													'profile.dob',
      													'profile.address',
      													'profile.city',
      													'profile.zip',
      													'profile.country_citizenship',
      													'profile.country_residence',
      													'profile.company',
      													'profile.step_review',
      													'profile.step_kyc'
      												])
      												->orderBy($sort_key, $sort_direction)
							                ->offset($start)
							                ->limit($page_length)
							                ->get()
							                ->toArray();
		}

		return [
      'success' => true,
      'actions' => $actions,
      'total' => $total
    ];
	}

	// Single User
	public function getSingleUser($userId, Request $request) {
		$user = Auth::user();
		if ($user && $user->hasRole('admin')) {
			$user = User::with(['profile', 'shuftipro', 'shuftiproTemp'])
									->where('id', $userId)->first();

			if ($user && $user->profile) {
				return [
					'success' => true,
					'user' => $user
				];
			}
		}

		return ['success' => false];
	}

	// Pre Register Users
	public function getPreRegisterUsers(Request $request) {
		$user = Auth::user();
		$users = [];

		// Variables
		$sort_key = $sort_direction = $search = '';
		$page_id = 0;
		$data = $request->all();
		if ($data && is_array($data)) extract($data);

		if (!$sort_key) $sort_key = 'pre_register.id';
		if (!$sort_direction) $sort_direction = 'desc';
		$page_id = (int) $page_id;
		if ($page_id <= 0) $page_id = 1;

		$limit = isset($data['limit']) ? $data['limit'] : 10;
		$start = $limit * ($page_id - 1);

		// Records
		if ($user && $user->hasRole('admin')) {
			$users = PreRegister::where(function ($query) use ($search) {
														if ($search) {
															$query->where('first_name', 'like', '%' . $search . '%')
																		->orWhere('last_name', 'like', '%' . $search . '%')
																		->orWhere('email', 'like', '%' . $search . '%');
														}
													})
													->orderBy($sort_key, $sort_direction)
													->offset($start)
													->limit($limit)
					                ->get();
		}

		return [
      'success' => true,
      'users' => $users,
      'finished' => count($users) < $limit ? true : false
    ];
	}

	// Pending Users DataTable
	public function getPendingUsers(Request $request) {
		$user = Auth::user();
		$users = [];

		// Variables
		$sort_key = $sort_direction = $search = '';
		$page_id = 0;
		$data = $request->all();
		if ($data && is_array($data)) extract($data);

		if (!$sort_key) $sort_key = 'users.id';
		if (!$sort_direction) $sort_direction = 'desc';
		$page_id = (int) $page_id;
		if ($page_id <= 0) $page_id = 1;

		$limit = isset($data['limit']) ? $data['limit'] : 10;
		$start = $limit * ($page_id - 1);

		// Records
		if ($user && $user->hasRole('admin')) {
			$users = User::join('profile', 'users.id', '=', 'profile.user_id')
      							->where('users.is_admin', 0)
      							->where('users.is_guest', 0)
      							->where('can_access', 0)
      							->where(function ($query) use ($search) {
											if ($search) {
												$query->where('users.email', 'like', '%' . $search . '%')
															->orWhere('users.first_name', 'like', '%' . $search . '%')
															->orWhere('users.last_name', 'like', '%' . $search . '%')
															->orWhere('profile.telegram', 'like', '%' . $search . '%');
											}
										})
      							->select([
		                  'users.*',
		                  'profile.company',
		                  'profile.dob',
		                  'profile.country_citizenship',
		                  'profile.country_residence',
		                  'profile.address',
		                  'profile.city',
		                  'profile.zip',
		                  'profile.step_review',
		                  'profile.step_kyc',
		                  'profile.rep',
		                  'profile.telegram',
		                ])
		                ->orderBy($sort_key, $sort_direction)
										->offset($start)
										->limit($limit)
		                ->get();
		}

		return [
      'success' => true,
      'users' => $users,
      'finished' => count($users) < $limit ? true : false
    ];
	}

	// Users DataTable
	public function getUsers(Request $request) {
		$user = Auth::user();
		$users = [];

		// Variables
		$sort_key = $sort_direction = $search = '';
		$page_id = 0;
		$data = $request->all();
		if ($data && is_array($data)) extract($data);

		if (!$sort_key) $sort_key = 'users.id';
		if (!$sort_direction) $sort_direction = 'desc';
		$page_id = (int) $page_id;
		if ($page_id <= 0) $page_id = 1;

		$limit = 30;
		$start = $limit * ($page_id - 1);

		// Records
		if ($user && $user->hasRole('admin')) {
			$users = User::join('profile', 'users.id', '=', 'profile.user_id')
      							->where('users.is_admin', 0)
      							->where('users.is_guest', 0)
      							->where('can_access', 1)
      							->where(function ($query) use ($search) {
											if ($search) {
												$query->where('users.email', 'like', '%' . $search . '%')
															->orWhere('users.first_name', 'like', '%' . $search . '%')
															->orWhere('users.last_name', 'like', '%' . $search . '%')
															->orWhere('profile.forum_name', 'like', '%' . $search . '%');
											}
										})
      							->select([
		                  'users.*',
		                  'profile.company',
		                  'profile.dob',
		                  'profile.country_citizenship',
		                  'profile.country_residence',
		                  'profile.address',
		                  'profile.city',
		                  'profile.zip',
		                  'profile.step_review',
		                  'profile.step_kyc',
		                  'profile.rep',
		                  'profile.forum_name',
		                ])
		                ->orderBy($sort_key, $sort_direction)
										->offset($start)
										->limit($limit)
		                ->get();
		}

		return [
      'success' => true,
      'users' => $users,
      'finished' => count($users) < $limit ? true : false
    ];
	}

	// Force Approve Proposal Change
	public function forceApproveProposalChange($proposalChangeId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$proposalChange = ProposalChange::find($proposalChangeId);
			if (!$proposalChange) {
				return [
					'success' => false,
					'message' => 'Invalid proposed change'
				];
			}

			$proposal = Proposal::find($proposalChange->proposal_id);
			if (!$proposal) {
				return [
					'success' => false,
					'message' => 'Invalid proposal'
				];
			}

			// Proposal Status is not approved
			if ($proposal->status != 'approved') {
				return [
					'success' => false,
					'message' => "You can't force approve this proposed change"
				];
			}

			// Proposal Change Status is not pending
			if ($proposalChange->status != 'pending') {
				return [
					'success' => false,
					'message' => "You can't force approve this proposed change"
				];
			}

			// Record Proposal History
			$proposalId = (int) $proposal->id;
			$history = ProposalHistory::where('proposal_id', $proposalId)
																->where('proposal_change_id', $proposalChangeId)
																->first();

			if (!$history) $history = new ProposalHistory;

			$history->proposal_id = $proposalId;
			$history->proposal_change_id = $proposalChangeId;
			$history->what_section = $proposalChange->what_section;

			// Apply Changes
			$what_section = $proposalChange->what_section;
			if ($what_section == "short_description") {
				$history->change_to_before = $proposal->short_description;
				$proposal->short_description = $proposalChange->change_to;
			} else if ($what_section == "total_grant") {
				$rate = (float) $proposalChange->change_to / (float) $proposal->total_grant;

				$history->change_to_before = $proposal->total_grant;
				$proposal->total_grant = (float) $proposalChange->change_to;

				// Grants
				$grants = Grant::where('proposal_id', $proposalId)->get();
				if ($grants) {
					foreach ($grants as $grant) {
						$temp = (float) $grant->grant * $rate;
						$temp = round($temp, 2);
						$grant->grant = $temp;
						$grant->save();
					}
				}

				// Milestones
				$milestones = Milestone::where('proposal_id', $proposalId)->get();
				if ($milestones) {
					foreach ($milestones as $milestone) {
						$temp = (float) $milestone->grant * $rate;
						$temp = round($temp, 2);
						$milestone->grant = $temp;
						$milestone->save();
					}
				}
			} else if ($what_section == "previous_work") {
				$history->change_to_before = $proposal->previous_work;
				$proposal->previous_work = $proposalChange->change_to;
			} else if ($what_section == "other_work") {
				$history->change_to_before = $proposal->other_work;
				$proposal->other_work = $proposalChange->change_to;
			}

			$history->save();
			$proposal->save();

			// Change Proposal Change
			$proposalChange->status = 'approved';
			$proposalChange->save();

			return ['success' => true];
		}

		return ['success' => false];
	}

	// Force Deny Proposal Change
	public function forceDenyProposalChange($proposalChangeId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$proposalChange = ProposalChange::find($proposalChangeId);
			if (!$proposalChange) {
				return [
					'success' => false,
					'message' => 'Invalid proposed change'
				];
			}

			$proposal = Proposal::find($proposalChange->proposal_id);
			if (!$proposal) {
				return [
					'success' => false,
					'message' => 'Invalid proposal'
				];
			}

			// Proposal Status is not approved
			if ($proposal->status != 'approved') {
				return [
					'success' => false,
					'message' => "You can't force deny this proposed change"
				];
			}

			// Proposal Change Status is not pending
			if ($proposalChange->status != 'pending') {
				return [
					'success' => false,
					'message' => "You can't force deny this proposed change"
				];
			}

			$proposalChange->status = 'denied';
			$proposalChange->save();

			return ['success' => true];
		}

		return ['success' => false];
	}

	// Force Withdraw Proposal Change
	public function forceWithdrawProposalChange($proposalChangeId, Request $request) {
		$user = Auth::user();

		if ($user && $user->hasRole('admin')) {
			$proposalChange = ProposalChange::find($proposalChangeId);
			if (!$proposalChange) {
				return [
					'success' => false,
					'message' => 'Invalid proposed change'
				];
			}

			$proposal = Proposal::find($proposalChange->proposal_id);
			if (!$proposal) {
				return [
					'success' => false,
					'message' => 'Invalid proposal'
				];
			}

			// Proposal Status is not approved
			if ($proposal->status != 'approved') {
				return [
					'success' => false,
					'message' => "You can't force withdraw this proposed change"
				];
			}

			// Proposal Change Status is not pending
			if ($proposalChange->status != 'pending') {
				return [
					'success' => false,
					'message' => "You can't force withdraw this proposed change"
				];
			}

			$proposalChange->status = "withdrawn";
			$proposalChange->save();

			return ['success' => true];
		}

		return ['success' => false];
	}

	// resned Send grant Hellosign Request
	public static function resendHellosignGrant($grantId)
	{
		$user = Auth::user();
		if ($user && $user->hasRole('admin')) {
			$finalGrant = FinalGrant::with(['proposal', 'user'])
				->has('proposal')
				->has('user')
				->where('id', $grantId)
				->first();
			$user = $finalGrant->user;
			$proposal = $finalGrant->proposal;
			$settings = Helper::getSettings();
			$signature_grant_request_id = $finalGrant->proposal->signature_grant_request_id;
			if (!$finalGrant || $finalGrant->status != "pending" || !$signature_grant_request_id) {
				return [
					'success' => false,
					'message' => 'Invalid grant'
				];
			}
			$client = new \HelloSign\Client(config('services.hellosign.api_key'));
			$client->cancelSignatureRequest($signature_grant_request_id);
			SignatureGrant::where('proposal_id', $finalGrant->proposal_id)
				->update(['signed' => 0]);
			Helper::sendGrantHellosign($user, $proposal, $settings);
			return ['success' => true];
		}
		return ['success' => false];
	}

	// remind Send grant Hellosign Request
	public static function remindHellosignGrant($grantId)
	{
		try {
			$user = Auth::user();
			if ($user && $user->hasRole('admin')) {
				$finalGrant = FinalGrant::with(['proposal', 'user'])
					->has('proposal')
					->has('user')
					->where('id', $grantId)
					->first();
				$signature_grant_request_id = $finalGrant->proposal->signature_grant_request_id;
					if (!$finalGrant || $finalGrant->status != "pending" || !$signature_grant_request_id) {
					return [
						'success' => false,
						'message' => 'Invalid grant'
					];
				}
				$signatureGrants = SignatureGrant::where('proposal_id', $finalGrant->proposal_id)->where('signed', 0)->get();
				foreach ($signatureGrants as $value) {
					$client = new \HelloSign\Client(config('services.hellosign.api_key'));
					$client->requestEmailReminder($signature_grant_request_id, $value->email, $value->name);
				}
				return ['success' => true];
			}
			return ['success' => false];
		} catch (\Exception $ex) {
			return [
				'success' => false,
				'message' => $ex->getMessage()
			];
		}
		
	}

	public function updateKYCinfo(Request $request, $userId)
	{
		$admin = Auth::user();
		if ($admin && $admin->hasRole('admin')) {
			$profile = Profile::where('user_id', $userId)->first();
			if ($profile) {
				if ($request->address) {
					$profile->address = $request->address;
				}
				if ($request->city) {
					$profile->city = $request->city;
				}
				if ($request->zip) {
					$profile->zip = $request->zip;
				}
				$profile->save();
				return ['success' => true];
			}
			return ['success' => false];
		}
		return ['success' => false];
	}

	public function getUrlFileHellosignGrant($grantId)
	{
		$admin = Auth::user();
		if ($admin && $admin->hasRole('admin')) {
			$finalGrant = FinalGrant::where('id', $grantId)->first();
			if(!$finalGrant) {
				return ['success' => false];
			}

			$proposal = Proposal::where('id', $finalGrant->proposal_id)->first();

			if(!$proposal || !$proposal->signature_grant_request_id) {
				return ['success' => false];
			}
			$signature_grant_request_id = $proposal->signature_grant_request_id;
			$client = new \HelloSign\Client(config('services.hellosign.api_key'));
			$respone = $client->getFiles($signature_grant_request_id, null, \HelloSign\SignatureRequest::FILE_TYPE_PDF);
			$respone = $respone->toArray();
			return [
				'success' => true,
				'file_url' => $respone['file_url'] ?? '',
			];
		}
		return ['success' => false];
		
	}
	public function getListUserNotVote($id, Request $request) {
		$page_id = 0;
		$data = $request->all();
		if ($data && is_array($data)) extract($data);

		$page_id = (int) $page_id;
		if ($page_id <= 0) $page_id = 1;

		$limit = isset($data['limit']) ? $data['limit'] : 10;
		$start = $limit * ($page_id - 1);

		$vote = Vote::where('id', $id)->first();
		if ($vote) {
			$type = $vote->type;
			if ($type == 'informal') {
				return $this->getUserNotVoteInformal($vote, $start, $limit);
			} else {
				return $this->getUserNotVoteFormal($vote, $start, $limit);
			}

		} else {
			return [
				'success' => false,
				'message' => 'Vote not exist'
			];
		}
	}

	private function getUserNotVoteInformal($vote, $start, $limit) {
		$user = User::where('is_member', 1)
				->where('banned', 0)
				->where('can_access', 1)
				->whereNotIn('id', function($query) use ($vote) {
					$query->select('user_id')
						->from(with(new VoteResult())->getTable())
						->where('vote_id', $vote->id);
				})->orderBy('id', 'asc')
				->with('profile')
				->offset($start)
				->limit($limit)
				->get();
		return [
			'success' => true,
			'data' => $user
		];
	}

	private function getUserNotVoteFormal($vote, $start, $limit) {
		$informal = Vote::where('content_type', $vote->content_type)
						->where('type', 'informal')
						->where('proposal_id', $vote->proposal_id)->first();
		$user = User::where('is_member', 1)
				->where('banned', 0)
				->where('can_access', 1)
				->whereIn('id', function($query) use ($informal) {
					$query->select('user_id')
						->from(with(new VoteResult())->getTable())
						->where('vote_id', $informal->id);
				})
				->whereNotIn('id', function($query) use ($vote) {
					$query->select('user_id')
						->from(with(new VoteResult())->getTable())
						->where('vote_id', $vote->id);
				})->orderBy('id', 'asc')
				->with('profile')
				->offset($start)
				->limit($limit)
				->get();
		
		return [
			'success' => true,
			'data' => $user
		];
	}

	public function getMetrics()
	{
		$totalGrant = FinalGrant::join('proposal', 'final_grant.proposal_id', '=', 'proposal.id')
		->where('final_grant.status', '!=', 'pending')
		->sum('proposal.total_grant');
		$data['totalGrant'] = $totalGrant;
		return [
			'success' => true,
			'data' => $data
		];
	}

}
