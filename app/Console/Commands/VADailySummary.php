<?php

namespace App\Console\Commands;

use App\Http\Helper;
use App\Proposal;
use App\Vote;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VADailySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'va:daily-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'VA daily summary';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $totalMembersInfomal = Helper::getTotalMembers();
        // Get Settings
        $settings = Helper::getSettings();
        $today = Carbon::now('UTC');
        $yesterday = $today->subDay();
        $discussions = Proposal::has('user')
            ->where('status', 'approved')
            ->whereNotNull('approved_at')
            ->where('approved_at', '>=', $yesterday)
            ->get();
        $votes = Vote::join('proposal', 'proposal.id', '=', 'vote.proposal_id')
            ->where('vote.created_at', '>=', $yesterday)
            ->select(['vote.id', 'vote.type', 'vote.content_type', 'proposal.title'])->get();

        $noQuorumVotes = Vote::join('proposal', 'proposal.id', '=', 'vote.proposal_id')
            ->where('vote.result', 'no-quorum')
            ->where('vote.updated_at', '>=', $yesterday)
            ->select(['vote.id', 'vote.type', 'vote.content_type', 'proposal.title', 'vote.updated_at'])->get();
        $noQuorumVotes2 = Vote::join('proposal', 'proposal.id', '=', 'vote.proposal_id')
            ->where('vote.status', 'active')
            ->select(['vote.id', 'vote.type', 'vote.content_type', 'proposal.title', 'vote.updated_at'])->get();
        foreach ($noQuorumVotes2 as $vote) {
            if ($vote->content_type == 'grant') {
                $quorumRate = (float) $settings['quorum_rate'];
            } else if ($vote->content_type == 'milestone') {
                $quorumRate = (float) $settings['quorum_rate_milestone'];
            } else if ($vote->content_type == 'simple') {
                $quorumRate = (float) $settings['quorum_rate_simple'];
            }
            if ($vote->type == 'informal') {
                $minMembers = $totalMembersInfomal * $quorumRate / 100;
                $minMembers = ceil($minMembers);

                if ($vote->result_count < $minMembers) {
                    $noQuorumVotes->push($vote);
                }
            } else if ($vote->type == 'formal') {
                $voteInfomal = Vote::where('proposal_id', $vote->proposal_id)->where('type', 'informal')
                ->where('content_type', $vote->content_type)->first();
                $totalMembers =  $voteInfomal->result_count ?? 0;
                $minMembers = $totalMembers * $quorumRate / 100;
                $minMembers = ceil($minMembers);
                if ($vote->result_count < $minMembers) {
                    $noQuorumVotes->push($vote);
                }
            }
        }
        $emailerData = Helper::getEmailerData();
        Helper::triggerMemberEmail('VA daily summary', $emailerData, null, null, $discussions, $votes, $noQuorumVotes);
    }
}
