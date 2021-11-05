<?php

namespace App\Http\Controllers;

use App\ComplianceUser;
use App\Exports\InvoiceExport;
use App\FinalGrant;
use App\IpHistoryCompliance;
use App\OnBoarding;
use App\Proposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use App\Http\Helper;
use App\Invoice;
use App\Shuftipro;
use App\User;
use App\Vote;
use App\VoteResult;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ComplianceController extends Controller
{
    public function login(Request $request)
    {
        // Validator
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required'
        ]);
        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Email or Password is not correct'
            ];
        }

        $email = $request->get('email');
        $password = $request->get('password');

        $user = ComplianceUser::where('email', $email)->first();
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Email does not exist'
            ];
        }

        if ($user->is_super_admin == 1 ||  $user->is_pa == 1) {
            if (!Hash::check($password, $user->password)) {
                return [
                    'success' => false,
                    'message' => 'Email or Password is not correct'
                ];
            }

            if ($user->status == 'revoked' || $user->banned == 1) {
                return [
                    'success' => false,
                    'message' => 'You are banned. Please contact us for further details.'
                ];
            }

            $user->last_login_ip_address = request()->ip();
            $user->last_login_at = now();
            $user->save();
            $ipHistory = new IpHistoryCompliance();
            $ipHistory->user_id = $user->id;
            $ipHistory->ip_address = request()->ip();
            $ipHistory->save();

            $tokenResult = $user->createToken('User Access Token');
            $user->accessTokenAPI = $tokenResult->accessToken;

            return [
                'success' => true,
                'user' => $user
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Role is not valid'
            ];
        }
        return [
            'success' => false,
            'message' => 'Login info is not correct'
        ];
    }

    public function logout()
    {
        auth()->user()->token()->revoke();
        return [
            'success' => true,
        ];
    }

    public function getMe()
    {
        $user = Auth::user();
        // Total Members
        $user->totalMembers = Helper::getTotalMembers();
        return [
            'success' => true,
            'me' => $user
        ];
    }

    public function createPAUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Invalid format Email'
            ];
        }

        $isExist = ComplianceUser::where(['email' => $request->email])->count() > 0;
        if ($isExist) {
            return [
                'success' => false,
                'message' => 'This email has already been exist'
            ];
        }
        $user = new ComplianceUser;
        $user->first_name = '';
        $user->last_name = '';
        $user->email =  $request->email;
        $user->password = bcrypt($request->password);
        $user->status = 'active';
        $user->is_pa = 1;
        $user->save();
        return [
            'success' => true,
        ];
    }

    public function getListUser(Request $request)
    {
        // Users DataTable
        $user = Auth::user();
        $users = [];

        // Variables
        $search = $request->search;
        $sort_key = $sort_direction = '';
        $page_id = 0;
        $data = $request->all();
        if ($data && is_array($data)) extract($data);

        if (!$sort_key) $sort_key = 'compliance_users.id';
        if (!$sort_direction) $sort_direction = 'desc';
        $page_id = (int) $page_id;
        if ($page_id <= 0) $page_id = 1;

        $limit = $request->limit ?? 15;
        $start = $limit * ($page_id - 1);

        // Records
        if ($user) {
            $users = ComplianceUser::where('id', '!=', $user->id)
                ->where(function ($query) {
                    $query->where('compliance_users.is_super_admin', 1)
                        ->orWhere('compliance_users.is_pa', 1);
                })->where(function ($query) use ($search) {
                    if ($search) {
                        $query->where('compliance_users.email', 'like', '%' . $search . '%');
                    }
                })
                ->orderBy($sort_key, $sort_direction)
                ->offset($start)
                ->limit($limit)
                ->get();
            return [
                'success' => true,
                'users' => $users,
                'finished' => count($users) < $limit ? true : false
            ];
        } else {
            return [
                'success' => false,
                'users' => $users,
            ];
        }
    }

    public function getIpHistories(Request $request, $id)
    {
        $user = ComplianceUser::find($id);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Not found user'
            ];
        }
        // Variables
        $sort_key = $sort_direction = '';
        $page_id = 0;
        $data = $request->all();
        if ($data && is_array($data)) extract($data);

        if (!$sort_key) $sort_key = 'created_at';
        if (!$sort_direction) $sort_direction = 'desc';
        $page_id = (int) $page_id;
        if ($page_id <= 0) $page_id = 1;

        $limit = $request->limit ?? 15;
        $start = $limit * ($page_id - 1);

        // Records
        $ips = IpHistoryCompliance::where('user_id', $user->id)
            ->orderBy($sort_key, $sort_direction)
            ->offset($start)
            ->limit($limit)
            ->get();
        return [
            'success' => true,
            'ip-histories' => $ips,
            'finished' => count($ips) < $limit ? true : false
        ];
    }
    public function revokeUser(Request $request, $id)
    {
        $user = ComplianceUser::find($id);
        if ($user && $user->is_pa == 1) {
            $user->banned = 1;
            $user->status = 'revoked';
            $user->save();
            return [
                'success' => true
            ];
        } else {
            return [
                'success' => false,
                'message' => 'No user to revoke'
            ];
        }
    }

    public function resetPassword(Request $request, $id)
    {
        $user = ComplianceUser::find($id);
        if ($user && $user->is_pa == 1) {
            $validator = Validator::make($request->all(), [
                'password' => 'required',
            ]);
            if ($validator->fails()) {
                return [
                    'success' => false,
                    'message' => 'Invalid password'
                ];
            }
            $user->password = bcrypt($request->password);
            $user->save();
            return ['success' => true];
        } else {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
    }

    public function undoRevokeUser(Request $request, $id)
    {
        $user = ComplianceUser::find($id);
        if ($user && $user->is_pa == 1) {
            $user->banned = 0;
            $user->status = 'active';
            $user->save();
            return [
                'success' => true
            ];
        } else {
            return [
                'success' => false,
                'message' => 'No admin to un-revoke'
            ];
        }
    }

    public function updateComplianceStatus(Request $request, $id)
    {
        // Validator
        $validator = Validator::make($request->all(), [
            'compliance' => 'required|in:0,1',
        ]);
        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'compliance must 0 or 1'
            ];
        }
        $user = ComplianceUser::find($id);
        if ($user) {
            $user->compliance = $request->compliance;
            $user->save();
            return [
                'success' => true
            ];
        } else {
            return [
                'success' => false,
                'message' => 'No admin to un-revoke'
            ];
        }
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $validator = Validator::make($request->all(), [
                'password' => 'required',
            ]);
            if ($validator->fails()) {
                return [
                    'success' => false,
                    'message' => 'Invalid password'
                ];
            }
            $user->password = bcrypt($request->password);
            $user->save();
            return ['success' => true];
        } else {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
    }

    // Get Pending Grant Onboardings
    public function getPendingGrantOnboardings(Request $request)
    {
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
        $onboardings = OnBoarding::with([
            'user',
            'user.profile',
            'user.shuftipro',
            'user.shuftiproTemp',
            'proposal',
            'proposal.signtureGrants',
            'proposal.grantLogs',
            'proposal.votes',
            'proposal.votes.results',
            'proposal.votes.results.user',
        ])
            ->has('user')
            ->has('proposal')
            ->join('proposal', 'proposal.id', '=', 'onboarding.proposal_id')
            ->join('users', 'users.id', '=', 'onboarding.user_id')
            ->leftJoin('shuftipro', 'shuftipro.user_id', '=', 'onboarding.user_id')
            ->leftJoin('final_grant', 'onboarding.proposal_id', '=', 'final_grant.proposal_id')
            ->whereIn('onboarding.status', ['pending', 'completed'])
            ->where('onboarding.compliance_status', '!=', 'denied')
            ->where('shuftipro.status', '!=', 'denied')
            ->where(function ($query) {
                $query->where('final_grant.status', null)
                    ->orWhere('final_grant.status', 'pending');
            })
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

        return [
            'success' => true,
            'onboardings' => $onboardings,
            'finished' => count($onboardings) < $limit ? true : false
        ];
    }

    // Get Grants
    public function getGrants(Request $request)
    {
        $user = Auth::user();
        // Variables
        $sort_key = $sort_direction = $search = '';
        $page_id = 0;
        $data = $request->all();
        if ($data && is_array($data)) extract($data);

        if (!$sort_key) $sort_key = 'final_grant.id';
        if (!$sort_direction) $sort_direction = 'desc';
        $page_id = (int) $page_id;
        if ($page_id <= 0) $page_id = 1;

        $limit = isset($data['limit']) ? $data['limit'] : 10;
        $start = $limit * ($page_id - 1);
        $status = $request->status ?? 'active';

        $proposals = FinalGrant::with([
            'proposal', 'proposal.user', 'proposal.milestones',
            'proposal.votes',
            'proposal.votes.results',
            'proposal.votes.results.user',
            'proposal.milestones.votes', 'proposal.milestones.votes.results.user', 'grantLogs',
            'user', 'user.shuftipro', 'signtureGrants'
        ])
            ->where('final_grant.status', $status)
            ->has('proposal.milestones')
            ->has('user')
            ->where(function ($subQuery)  use ($search) {
                $subQuery->whereHas('proposal', function ($query) use ($search) {
                    $query->where('proposal.title', 'like', '%' . $search . '%')
                        ->orWhere('proposal.id', 'like', '%' . $search . '%');
                });
            })
            ->orderBy($sort_key, $sort_direction)
            ->offset($start)
            ->limit($limit)
            ->get();
        return [
            'success' => true,
            'proposals' => $proposals,
            'finished' => count($proposals) < $limit ? true : false
        ];
    }

    public function approveComplianceReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proposalId' => 'required',
        ]);
        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Provide all the necessary information'
            ];
        }
        $proposalId = $request->proposalId;
        $proposal = Proposal::find($proposalId);
        $onboarding  = OnBoarding::where('proposal_id', $proposalId)->first();
        if (!$onboarding || !$proposal) {
            return [
                'success' => false,
                'message' => 'Proposal does not exist'
            ];
        }
        $settings = Helper::getSettings();
        $onboarding->compliance_status = 'approved';
        $onboarding->compliance_reviewed_at = now();
        $onboarding->save();
        Helper::createGrantTracking($proposalId, "ETA compliance complete", 'eta_compliance_complete');
        $shuftipro = Shuftipro::where('user_id', $onboarding->user_id)->where('status', 'approved')->first();
        if ($shuftipro) {
            $onboarding->status = 'completed';
            $onboarding->save();
            $vote = Vote::find($onboarding->vote_id);
            $op = User::find($onboarding->user_id);
            $emailerData = Helper::getEmailerData();
            if ($vote && $op && $proposal) {
                Helper::triggerUserEmail($op, 'Passed Informal Grant Vote', $emailerData, $proposal, $vote);
            }
            Helper::startFormalVote($vote);
        }
        return [
            'success' => true,
            'proposal' => $proposal,
            'onboarding' => $onboarding,
            'compliance_admin' => $settings['compliance_admin'],
        ];
    }

    public function denyComplianceReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proposalId' => 'required',
            'reason' => 'required',
        ]);
        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Provide all the necessary information'
            ];
        }
        $proposalId = $request->proposalId;
        $proposal = Proposal::find($proposalId);
        $onboarding  = OnBoarding::where('proposal_id', $proposalId)->first();
        if (!$onboarding || !$proposal) {
            return [
                'success' => false,
                'message' => 'Proposal does not exist'
            ];
        }
        $settings = Helper::getSettings();
        $onboarding->compliance_status = 'denied';
        $onboarding->compliance_reviewed_at = now();
        $onboarding->deny_reason = $request->reason;
        $onboarding->save();
        return [
            'success' => true,
            'proposal' => $proposal,
            'onboarding' => $onboarding,
            'compliance_admin' => $settings['compliance_admin'],
        ];
    }

    public function getComplianceReview($proposalId)
    {
        $proposal = Proposal::find($proposalId);
        $onboarding  = OnBoarding::where('proposal_id', $proposalId)->first();
        if (!$onboarding || !$proposal) {
            return [
                'success' => false,
                'message' => 'Proposal does not exist'
            ];
        }
        $settings = Helper::getSettings();
        return [
            'success' => true,
            'proposal' => $proposal,
            'onboarding' => $onboarding,
            'compliance_admin' => $settings['compliance_admin'],
        ];
    }

    // Get compliance proposal
    public function getComplianceProposal(Request $request)
    {
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
        $status = $request->status;
        // Records
        $onboardings = OnBoarding::with([
            'user',
            'user.profile',
            'user.shuftipro',
            'user.shuftiproTemp',
            'proposal',
            'proposal.signtureGrants',
            'proposal.grantLogs',
            'proposal.votes',
            'proposal.votes.results',
            'proposal.votes.results.user',
        ])
            ->has('user')
            ->has('proposal')
            ->join('proposal', 'proposal.id', '=', 'onboarding.proposal_id')
            ->join('users', 'users.id', '=', 'onboarding.user_id')
            ->leftJoin('shuftipro', 'shuftipro.user_id', '=', 'onboarding.user_id')
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->where('proposal.title', 'like', '%' . $search . '%');
                }
            });
        if ($status == 'need-review') {
            $onboardings->where('onboarding.status', 'pending')
                ->where(function ($query) {
                    $query->where('onboarding.compliance_status', 'pending')
                        ->orWhere('shuftipro.status', 'pending')
                        ->orWhere('shuftipro.status', null);
                });
        }

        if ($status == 'approved') {
            $onboardings->where('onboarding.status', 'completed')
                ->where('onboarding.compliance_status', 'approved')
                ->where('shuftipro.status', 'approved');
        }

        if ($status == 'denied') {
            $onboardings->where(function ($query) {
                $query->where('onboarding.compliance_status', 'denied')
                    ->orWhere('shuftipro.status', 'denied');
            });
        }
        $onboardings = $onboardings->select([
            'onboarding.*',
            'proposal.title as proposal_title',
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

        return [
            'success' => true,
            'onboardings' => $onboardings,
            'finished' => count($onboardings) < $limit ? true : false
        ];
    }

    public function getAllInvoices(Request $request)
    {
        $admin = Auth::user();
        $invoices = [];

        // Variables
        $sort_key = $sort_direction = '';
        $email = $proposalId = $search = $startDate = $endDate = '';
        $page_id = 0;
        $data = $request->all();
        if ($data && is_array($data)) extract($data);

        if (!$sort_key) $sort_key = 'invoice.id';
        if (!$sort_direction) $sort_direction = 'desc';
        $page_id = (int) $page_id;
        if ($page_id <= 0) $page_id = 1;

        $limit = isset($data['limit']) ? $data['limit'] : 10;
        $start = $limit * ($page_id - 1);

        $query = Helper::queryGetInvoice($email, $proposalId, $startDate, $endDate, $search);
        $totalGrant = $query->sum('milestone.grant');
        $invoiceCount = $query->count();

        $query->where('invoice.paid', '=', 1);
        $totalPaid = $query->sum('milestone.grant');
        $InvoicePaidCount = $query->count();

        $query = Helper::queryGetInvoice($email, $proposalId, $startDate, $endDate, $search);
        $invoices = $query->select(['invoice.*'])
            ->with(['proposal', 'proposal.milestones', 'milestone'])
            ->orderBy($sort_key, $sort_direction)
            ->offset($start)
            ->limit($limit)
            ->get();

        return [
            'success' => true,
            'totalGrant' => $totalGrant,
            'totalPaid' => $totalPaid,
            'totalUnpaid' => $totalGrant - $totalPaid,
            'invoiceCount' => $invoiceCount,
            'InvoicePaidCount' => $InvoicePaidCount,
            'InvoiceUnpaidCount' => $invoiceCount - $InvoicePaidCount,
            'finished' => count($invoices) < $limit ? true : false,
            'invoices' => $invoices,
        ];
    }

    public function updateInvoicePaid($id, Request $request)
    {
        $invoice = Invoice::where('id', $id)->first();

        if (!$invoice) {
            return [
                'success' => false,
                'message' => 'Invalid Invoice'
            ];
        }

        $invoice->paid = $request->paid;
        $invoice->marked_paid_at = now();
        $invoice->save();

        return [
            'success' => true,
            'invoice' => $invoice,
        ];
    }

    public function exportCSVInvoices(Request $request)
    {
        $admin = Auth::user();
        // Variables
        $sort_key = $sort_direction = '';
        $email = $proposalId = $search = $startDate = $endDate = '';
        $data = $request->all();
        if ($data && is_array($data)) extract($data);

        if (!$sort_key) $sort_key = 'invoice.id';
        if (!$sort_direction) $sort_direction = 'desc';

        $query = Helper::queryGetInvoice($email, $proposalId, $startDate, $endDate, $search);
        $invoices = $query->select(['invoice.*'])
            ->with(['proposal'])
            ->orderBy($sort_key, $sort_direction)
            ->get();

        return Excel::download(new InvoiceExport($invoices), "invoices_.csv");
    }

    public function getInvoicePdfUrl($invoiceId)
    {
        $invoice = Invoice::with(['proposal', 'milestone', 'user', 'user.profile', 'user.shuftipro'])
            ->where('invoice.id', $invoiceId)
            ->first();

        if (!$invoice) {
            return [
                'success' => false,
                'message' => 'Not found invoice'
            ];
        }
        $vote = Vote::where('milestone_id', $invoice->milestone_id)->where('type', 'formal')
            ->where('result', 'success')->first();
        if (!$vote) {
            return [
                'success' => false,
                'message' => 'Not found vote formal milestone'
            ];
        }
        $milestoneResult = Helper::getResultMilestone($invoice->milestone);
        $invoice->milestone_number =  $milestoneResult['Milestone'];
        $invoice->public_grant_url = config('app.fe_url') . "/public-proposals/$invoice->proposal_id";

        $vote->results = VoteResult::join('profile', 'profile.user_id', '=', 'vote_result.user_id')
            ->where('vote_id', $vote->id)
            ->where('proposal_id', $invoice->proposal_id)
            ->select([
                'vote_result.*',
                'profile.forum_name'
            ])
            ->orderBy('vote_result.created_at', 'asc')
            ->get();
        $pdf = App::make('dompdf.wrapper');
        $pdfFile = $pdf->loadView('pdf.invoice', compact('invoice', 'vote'));
        $fullpath = 'pdf/invoice/invoice_' . $invoice->id . '.pdf';
        Storage::disk('local')->put($fullpath, $pdf->output());
        $url = Storage::disk('local')->url($fullpath);

        $invoice = Invoice::find($invoiceId);
        $invoice->pdf_url = $url;
        $invoice->save();
        return [
            'success' => true,
            'pdf_link_url' => $invoice->pdf_link_url
        ];
    }
}
