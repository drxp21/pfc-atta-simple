<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Election;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateElectionStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'election:update-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update election statuses based on their start and end dates (BROUILLON/OUVERTE -> EN_COURS, EN_COURS -> FERMEE)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now();
        $updatedCount = 0;
        $closedCount = 0;

        
        $electionsToStart = Election::whereIn('statut', ['BROUILLON', 'OUVERTE'])
            ->where('date_debut_vote', '<=', $now)
            ->get();

        foreach ($electionsToStart as $election) {
            $election->statut = 'EN_COURS';
            $election->save();
            $updatedCount++;
            Log::info("Election ID {$election->id} ('{$election->titre}') status updated to EN_COURS.");
        }

     
        $electionsToEnd = Election::where('statut', 'EN_COURS')
            ->where('date_fin_vote', '<=', $now)
            ->get();

        foreach ($electionsToEnd as $election) {
            $election->statut = 'FERMEE';
            $election->save();
            $closedCount++;
            Log::info("Election ID {$election->id} ('{$election->titre}') status updated to FERMEE.");
        }

      
        return Command::SUCCESS;
    }
}
