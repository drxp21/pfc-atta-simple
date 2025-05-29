<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidature;
use App\Models\Departement;
use App\Models\Election;
use App\Models\Resultat;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultatController extends Controller
{
    /**
     * Afficher les résultats d'une élection
     *
     * @param  \App\Models\Election  $election
     * @return \Illuminate\Http\Response
     */
    public function index(Election $election)
    {
        // Vérifier si l'élection est fermée
        if ($election->statut !== 'FERMEE') {
            return response()->json([
                'message' => 'Les résultats ne sont disponibles que pour les élections fermées'
            ], 422);
        }

        $resultats = Resultat::where('election_id', $election->id)
            ->with(['candidature', 'candidature.candidat'])
            ->orderBy('rang')
            ->get();

        // Statistiques
        $nbElecteursInscrits = $this->getNbElecteursPotentiels($election);
        $nbVotesExprimes = Vote::where('election_id', $election->id)->count();
        $nbVotesBlancs = Vote::where('election_id', $election->id)->where('vote_blanc', true)->count();
        $nbAbstentions = $nbElecteursInscrits - $nbVotesExprimes;
        $tauxParticipation = $nbElecteursInscrits > 0 ? round(($nbVotesExprimes / $nbElecteursInscrits) * 100, 2) : 0;

        return response()->json([
            'resultats' => $resultats,
            'statistiques' => [
                'nb_electeurs_inscrits' => $nbElecteursInscrits,
                'nb_votes_exprimes' => $nbVotesExprimes,
                'nb_votes_blancs' => $nbVotesBlancs,
                
                'taux_participation' => $tauxParticipation,
            ]
        ]);
    }

    /**
     * Calculer les résultats d'une élection
     *
     * @param  \App\Models\Election  $election
     * @return \Illuminate\Http\Response
     */
    public function calculer(Election $election)
    {
        // Vérifier si l'élection est fermée
        if ($election->statut !== 'FERMEE') {
            return response()->json([
                'message' => 'Les résultats ne peuvent être calculés que pour les élections fermées'
            ], 422);
        }

        // Supprimer les résultats existants
        Resultat::where('election_id', $election->id)->delete();

        // Récupérer toutes les candidatures validées
        $candidatures = Candidature::where('election_id', $election->id)
            ->where('statut', 'VALIDEE')
            ->get();

        // Compter les votes pour chaque candidature
        $nbElecteursInscrits = $this->getNbElecteursPotentiels($election);
        $nbVotesExprimes = Vote::where('election_id', $election->id)->count();
        $nbVotesBlancs = Vote::where('election_id', $election->id)->where('vote_blanc', true)->count();

        // Tableau pour stocker les résultats
        $resultats = [];

        foreach ($candidatures as $candidature) {
            $nbVoix = Vote::where('election_id', $election->id)
                ->where('candidature_id', $candidature->id)
                ->count();

            $pourcentage = $nbVotesExprimes > 0 ? round(($nbVoix / $nbVotesExprimes) * 100, 2) : 0;

            $resultats[] = [
                'candidature_id' => $candidature->id,
                'nb_voix' => $nbVoix,
                'pourcentage' => $pourcentage,
            ];
        }

        // Trier les résultats par nombre de voix décroissant
        usort($resultats, function ($a, $b) {
            return $b['nb_voix'] - $a['nb_voix'];
        });

        // Attribuer les rangs
        $rang = 1;
        $dernierNbVoix = null;
        $dernierRang = 1;

        foreach ($resultats as &$resultat) {
            if ($dernierNbVoix !== null && $resultat['nb_voix'] < $dernierNbVoix) {
                $rang = $dernierRang + 1;
            }
            $resultat['rang'] = $rang;
            $dernierNbVoix = $resultat['nb_voix'];
            $dernierRang = $rang;
            $rang++;
        }

        // Enregistrer les résultats
        foreach ($resultats as $resultat) {
            Resultat::updateOrCreate([
                'election_id' => $election->id,
                'candidature_id' => $resultat['candidature_id'],
                'nb_voix' => $resultat['nb_voix'],
                'pourcentage' => $resultat['pourcentage'],
                'rang' => $resultat['rang'],
            ]);
        }

        return response()->json([
            'message' => 'Résultats calculés avec succès',
            'resultats' => Resultat::where('election_id', $election->id)
                ->with(['candidature', 'candidature.candidat'])
                ->orderBy('rang')
                ->get(),
            'statistiques' => [
                'nb_electeurs_inscrits' => $nbElecteursInscrits,
                'nb_votes_exprimes' => $nbVotesExprimes,
                'nb_votes_blancs' => $nbVotesBlancs,
                'taux_participation' => $nbElecteursInscrits > 0 ? round(($nbVotesExprimes / $nbElecteursInscrits) * 100, 2) : 0,
            ]
        ]);
    }
    
    /**
     * Calculer le nombre d'électeurs potentiels pour une élection
     *
     * @param  \App\Models\Election  $election
     * @return int
     */
    private function getNbElecteursPotentiels(Election $election)
    {
        // Calculer le nombre d'électeurs potentiels selon le type d'élection
        if ($election->type_election === 'CHEF_DEPARTEMENT') {
            // PER du département
            return User::where('type_personnel', 'PER')
                ->where('departement_id', $election->departement_id)
                ->count();
        } elseif ($election->type_election === 'DIRECTEUR_UFR') {
            // PER de l'UFR (tous les départements de l'UFR)
            $departementsIds = Departement::where('ufr_id', $election->departement->ufr_id)
                ->pluck('id')
                ->toArray();
            return User::where('type_personnel', 'PER')
                ->whereIn('departement_id', $departementsIds)
                ->count();
        } elseif ($election->type_election === 'VICE_RECTEUR') {
            // PER + PATS
            return User::whereIn('type_personnel', ['PER', 'PATS'])->count();
        }
        
        return 0;
    }
}
