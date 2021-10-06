<?php

namespace App\Console\Commands;

use App\Proposal;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDF;

class GeneratePdfProposal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proposal:generate-pdf';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Proposal generate pdf';

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
        Proposal::chunk(100, function ($proposals) {
            foreach ($proposals as $proposal) {
                try {
                $pdf = PDF::loadView('proposal_pdf', compact('proposal'));
                $fullpath = 'pdf/proposal/proposal_' . $proposal->id . '.pdf';
                Storage::disk('local')->put($fullpath, $pdf->output());
                $url = Storage::disk('local')->url($fullpath);
                $proposal->pdf = $url;
                $proposal->save();
                } catch(Exception $ex) {
                    Log::error($ex->getMessage());
                }
            }
        });
    }
}
