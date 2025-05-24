<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidature;
use App\Models\Election;
use App\Models\ElecteurAutorise;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Afficher les statistiques du tableau de bord
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Nombre d'élections par statut
        $electionsParStatut = Election::select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->get()
            ->pluck('total', 'statut')
            ->toArray();
        
        // S'assurer que tous les statuts sont présents
        $statutsElection = ['BROUILLON', 'OUVERTE', 'EN_COURS', 'FERMEE'];
        foreach ($statutsElection as $statut) {
            if (!isset($electionsParStatut[$statut])) {
                $electionsParStatut[$statut] = 0;
            }
        }
        
        // Nombre de candidatures par statut
        $candidaturesParStatut = Candidature::select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->get()
            ->pluck('total', 'statut')
            ->toArray();
        
        // S'assurer que tous les statuts sont présents
        $statutsCandidature = ['EN_ATTENTE', 'VALIDEE', 'REJETEE'];
        foreach ($statutsCandidature as $statut) {
            if (!isset($candidaturesParStatut[$statut])) {
                $candidaturesParStatut[$statut] = 0;
            }
        }
        
        // Taux de participation global
        $nbElecteursInscrits = ElecteurAutorise::count();
        $nbElecteursAyantVote = ElecteurAutorise::where('a_vote', true)->count();
        $tauxParticipationGlobal = $nbElecteursInscrits > 0 
            ? round(($nbElecteursAyantVote / $nbElecteursInscrits) * 100, 2) 
            : 0;
        
        // Taux de participation par élection (pour les élections en cours ou fermées)
        $tauxParticipationParElection = [];
        $electionsAvecVotes = Election::whereIn('statut', ['EN_COURS', 'FERMEE'])->get();
        
        foreach ($electionsAvecVotes as $election) {
            $nbInscrits = ElecteurAutorise::where('election_id', $election->id)->count();
            $nbAyantVote = ElecteurAutorise::where('election_id', $election->id)
                ->where('a_vote', true)
                ->count();
            
            $tauxParticipationParElection[] = [
                'election_id' => $election->id,
                'titre' => $election->titre,
                'statut' => $election->statut,
                'nb_inscrits' => $nbInscrits,
                'nb_ayant_vote' => $nbAyantVote,
                'taux_participation' => $nbInscrits > 0 
                    ? round(($nbAyantVote / $nbInscrits) * 100, 2) 
                    : 0,
            ];
        }
        
        // Élections récentes
        $electionsRecentes = Election::with(['departement', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        // Candidatures récentes
        $candidaturesRecentes = Candidature::with(['election', 'candidat'])
            ->orderBy('date_soumission', 'desc')
            ->take(5)
            ->get();
        
        return response()->json([
            'elections_par_statut' => $electionsParStatut,
            'candidatures_par_statut' => $candidaturesParStatut,
            'taux_participation_global' => [
                'nb_inscrits' => $nbElecteursInscrits,
                'nb_ayant_vote' => $nbElecteursAyantVote,
                'taux_participation' => $tauxParticipationGlobal,
            ],
            'taux_participation_par_election' => $tauxParticipationParElection,
            'elections_recentes' => $electionsRecentes,
            'candidatures_recentes' => $candidaturesRecentes,
        ]);
    }
}
