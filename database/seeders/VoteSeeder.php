<?php

namespace Database\Seeders;

use App\Models\Candidature;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Seeder;

class VoteSeeder extends Seeder
{
    public function run()
    {
        $elections = Election::whereIn('statut', ['EN_COURS', 'FERMEE'])->get();
        
        foreach ($elections as $election) {
            $votants = User::whereIn('type_personnel', ['PER', 'PATS'])
                ->inRandomOrder()
                ->take(rand(10, 30))
                ->get();
            
            $candidatures = Candidature::where('election_id', $election->id)
                ->where('statut', 'validée')
                ->get();
                
            if ($candidatures->isEmpty()) {
                continue; 
            }
            
            foreach ($votants as $votant) {
                $candidature = $candidatures->random();
                
                $dejaVote = Vote::where('user_id', $votant->id)
                    ->where('election_id', $election->id)
                    ->exists();
                    
                if (!$dejaVote) {
                    Vote::create([
                        'user_id' => $votant->id,
                        'candidature_id' => $candidature->id,
                        'election_id' => $election->id,
                        'date_vote' => now()->subHours(rand(1, 24 * 30)), 'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                
            }
        }
    }
}
