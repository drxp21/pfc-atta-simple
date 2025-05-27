<?php

namespace Database\Seeders;

use App\Models\Departement;
use App\Models\Election;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ElectionSeeder extends Seeder
{
    public function run()
    {
        // Récupérer les départements
        $departements = Departement::all();
        $admin = User::where('type_personnel', 'ADMIN')->first();
        
        if (!$admin) {
            $admin = User::first();
        }
        
        $now = now();
        
        // 1. Élection de chef de département (une par département)
        foreach ($departements as $departement) {
            // Dates pour la période de candidature (début il y a 10 jours, fin dans 2 jours)
            $dateDebutCandidature = $now->copy()->subDays(10);
            $dateFinCandidature = $now->copy()->addDays(2);
            
            // Dates pour la période de vote (début après la fin des candidatures, durée 3 jours)
            $dateDebutVote = $now->copy()->addDays(3);
            $dateFinVote = $now->copy()->addDays(6);
            
            $election = [
                'titre' => "Élection du Chef du Département " . $departement->nom,
                'description' => "Élection pour le poste de Chef du Département " . $departement->nom . " pour le mandat 2024-2027.",
                'type_election' => 'CHEF_DEPARTEMENT',
                'statut' => 'BROUILLON',
                'departement_id' => $departement->id,
                'date_debut_candidature' => $dateDebutCandidature,
                'date_fin_candidature' => $dateFinCandidature,
                'date_debut_vote' => $dateDebutVote,
                'date_fin_vote' => $dateFinVote,
                'created_by' => $admin->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            
            Election::create($election);
        }
        
        // 2. Élection du Directeur d'UFR (une élection par UFR, mais simplifions avec une seule pour l'exemple)
        $dateDebutCandidature = $now->copy()->subDays(5);
        $dateFinCandidature = $now->copy()->addDays(5);
        $dateDebutVote = $now->copy()->addDays(6);
        $dateFinVote = $now->copy()->addDays(9);
        
        Election::create([
            'titre' => "Élection du Directeur de l'UFR SAT",
            'description' => "Élection pour le poste de Directeur de l'UFR des Sciences Appliquées et de Technologie pour le mandat 2024-2027.",
            'type_election' => 'DIRECTEUR_UFR',
            'statut' => 'OUVERTE', // En période de candidature
            'departement_id' => 1, // Associer à un département arbitraire
            'date_debut_candidature' => $dateDebutCandidature,
            'date_fin_candidature' => $dateFinCandidature,
            'date_debut_vote' => $dateDebutVote,
            'date_fin_vote' => $dateFinVote,
            'created_by' => $admin->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        // 3. Élection du Vice-Recteur (en cours de vote)
        $dateDebutCandidature = $now->copy()->subDays(15);
        $dateFinCandidature = $now->copy()->subDays(5);
        $dateDebutVote = $now->copy()->subDays(2);
        $dateFinVote = $now->copy()->addDays(2);
        
        Election::create([
            'titre' => "Élection du Vice-Recteur de l'Université de Thiès",
            'description' => "Élection pour le poste de Vice-Recteur de l'Université de Thiès pour le mandat 2024-2027.",
            'type_election' => 'VICE_RECTEUR',
            'statut' => 'EN_COURS', // En cours de vote
            'departement_id' => 1, // Associer à un département arbitraire
            'date_debut_candidature' => $dateDebutCandidature,
            'date_fin_candidature' => $dateFinCandidature,
            'date_debut_vote' => $dateDebutVote,
            'date_fin_vote' => $dateFinVote,
            'created_by' => $admin->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        // 4. Élection de Vice-Recteur terminée (pour l'historique)
        $dateDebutCandidature = $now->copy()->subDays(30);
        $dateFinCandidature = $now->copy()->subDays(20);
        $dateDebutVote = $now->copy()->subDays(15);
        $dateFinVote = $now->copy()->subDays(10);
        
        Election::create([
            'titre' => "Élection du Vice-Recteur 2023",
            'description' => "Élection pour le poste de Vice-Recteur de l'Université de Thiès pour le mandat 2023-2024.",
            'type_election' => 'VICE_RECTEUR',
            'statut' => 'FERMEE', // Élection terminée
            'departement_id' => 1,
            'date_debut_candidature' => $dateDebutCandidature,
            'date_fin_candidature' => $dateFinCandidature,
            'date_debut_vote' => $dateDebutVote,
            'date_fin_vote' => $dateFinVote,
            'created_by' => $admin->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
