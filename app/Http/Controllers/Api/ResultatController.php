<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidature;
use App\Models\Election;
use App\Models\ElecteurAutorise;
use App\Models\Resultat;
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
        $nbElecteursInscrits = ElecteurAutorise::where('election_id', $election->id)->count();
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
                'nb_abstentions' => $nbAbstentions,
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
        $nbElecteursInscrits = ElecteurAutorise::where('election_id', $election->id)->count();
        $nbVotesExprimes = Vote::where('election_id', $election->id)->count();
        $nbVotesBlancs = Vote::where('election_id', $election->id)->where('vote_blanc', true)->count();
        $nbVotesValides = $nbVotesExprimes - $nbVotesBlancs;

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
            Resultat::create([
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
                'nb_abstentions' => $nbElecteursInscrits - $nbVotesExprimes,
                'taux_participation' => $nbElecteursInscrits > 0 ? round(($nbVotesExprimes / $nbElecteursInscrits) * 100, 2) : 0,
            ]
        ]);
    }
}
