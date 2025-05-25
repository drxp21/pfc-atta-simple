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
        // Récupérer toutes les élections terminées ou en cours
        $elections = Election::whereIn('statut', ['en_cours', 'termine'])->get();
        
        foreach ($elections as $election) {
            // Récupérer les utilisateurs éligibles pour voter (PER et PATS)
            $votants = User::whereIn('type_personnel', ['PER', 'PATS'])
                ->inRandomOrder()
                ->take(rand(10, 30)) // Entre 10 et 30 votants par élection
                ->get();
            
            // Récupérer les candidatures valides pour cette élection
            $candidatures = Candidature::where('election_id', $election->id)
                ->where('statut', 'validée')
                ->get();
                
            if ($candidatures->isEmpty()) {
                continue; // Passer à l'élection suivante si pas de candidats
            }
            
            // Pour chaque votant, créer un vote aléatoire
            foreach ($votants as $votant) {
                // Voter pour un candidat aléatoire
                $candidature = $candidatures->random();
                
                // Vérifier si l'utilisateur n'a pas déjà voté pour cette élection
                $dejaVote = Vote::where('user_id', $votant->id)
                    ->where('election_id', $election->id)
                    ->exists();
                    
                if (!$dejaVote) {
                    Vote::create([
                        'user_id' => $votant->id,
                        'candidature_id' => $candidature->id,
                        'election_id' => $election->id,
                        'date_vote' => now()->subHours(rand(1, 24 * 30)), // Vote dans les 30 derniers jours
                        'ip_address' => $this->genererIp(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
    
    private function genererIp()
    {
        return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
    }
}
