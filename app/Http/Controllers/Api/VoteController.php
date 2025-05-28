<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidature;
use App\Models\Election;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VoteController extends Controller
{
    /**
     * Enregistrer un vote
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'election_id' => 'required|exists:elections,id',
            'candidature_id' => 'nullable|exists:candidatures,id',
            'vote_blanc' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier la cohérence du vote
        if (!$request->vote_blanc && !$request->candidature_id) {
            return response()->json([
                'message' => 'Vous devez soit voter pour un candidat, soit voter blanc'
            ], 422);
        }

        if ($request->vote_blanc && $request->candidature_id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas voter blanc et pour un candidat en même temps'
            ], 422);
        }

        // Récupérer l'élection
        $election = Election::findOrFail($request->election_id);

        // Vérifier si l'élection est en cours
        if ($election->statut !== 'EN_COURS') {
            return response()->json([
                'message' => 'Cette élection n\'est pas en cours'
            ], 422);
        }

        // Vérifier les dates de vote
        $now = Carbon::now();
        if ($now->lt(Carbon::parse($election->date_debut_vote)) || $now->gt(Carbon::parse($election->date_fin_vote))) {
            return response()->json([
                'message' => 'La période de vote n\'est pas active'
            ], 422);
        }

        // Récupérer l'électeur authentifié
        $electeur = auth()->user();
        if (!$electeur) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }
        // Charger le département de l'électeur s'il existe pour les vérifications
        $electeur->load('departement');

        // Nouvelles règles de vérification du droit de vote
        $typeElection = $election->type_election;
        $userType = $electeur->type_personnel;
        $userDepartementId = $electeur->departement_id;
        $electionDepartementId = $election->departement_id;

        $canVote = false;
        $errorMessage = 'Vous n\'êtes pas autorisé à voter pour cette élection.';

        switch ($typeElection) {
            case 'CHEF_DEPARTEMENT':
                if ($userType === 'PER' && $userDepartementId === $electionDepartementId) {
                    $canVote = true;
                } else {
                    $errorMessage = 'Seuls les PER de ce département peuvent voter pour l\'élection du Chef de Département.';
                }
                break;
            case 'DIRECTEUR_UFR':
                // En supposant que $election->departement_id fait référence à l'UFR pour ce type d'élection
                // et que les PER de cet "UFR/département" peuvent voter.
                // Si la logique est que les PER de TOUS les départements d'une UFR peuvent voter,
                // la structure de la base de données (liaison Département <-> UFR) devrait être différente.
                // Pour l'instant, on se base sur $election->departement_id.
                if ($userType === 'PER' && $userDepartementId === $electionDepartementId) {
                    $canVote = true;
                } else {
                    $errorMessage = 'Seuls les PER de cette UFR peuvent voter pour l\'élection du Directeur d\'UFR.';
                }
                break;
            case 'VICE_RECTEUR':
                if ($userType === 'PER' || $userType === 'PATS') {
                    $canVote = true;
                } else {
                    $errorMessage = 'Seuls les PER et PATS peuvent voter pour l\'élection du Vice-Recteur.';
                }
                break;
            default:
                // Type d'élection non géré ou inconnu
                return response()->json(['message' => 'Type d\'élection non supporté pour la vérification des droits de vote'], 500);
        }

        if (!$canVote) {
            return response()->json(['message' => $errorMessage], 403); // 403 Forbidden
        }

        // Vérifier si l'utilisateur a déjà voté
        if (
            Vote::where('electeur_id', auth()->user()->id)
                ->where('election_id', $election->id)
                ->exists()
        ) {
            return response()->json([
                'message' => 'Vous avez déjà voté pour cette élection'
            ], 422);
        }

        // Si vote pour un candidat, vérifier que la candidature est validée et appartient à cette élection
        if (!$request->vote_blanc && $request->candidature_id) {
            $candidature = Candidature::findOrFail($request->candidature_id);

            if ($candidature->election_id !== (int) $request->election_id) {
                return response()->json([
                    'message' => 'Cette candidature n\'appartient pas à cette élection'
                ], 422);
            }

            if ($candidature->statut !== 'VALIDEE') {
                return response()->json([
                    'message' => 'Cette candidature n\'est pas validée'
                ], 422);
            }
        }

        // Enregistrer le vote
        $vote = Vote::create([
            'election_id' => $request->election_id,
            'electeur_id' => $request->user()->id,
            'candidature_id' => $request->vote_blanc ? null : $request->candidature_id,
            'vote_blanc' => $request->vote_blanc,
            'date_vote' => $now,
        ]);

        return response()->json([
            'message' => 'Vote enregistré avec succès',
            'vote' => $vote
        ], 201);
    }

    public function hasVoted($electionId)
    {
        $user = auth()->user();

        // Vérifier si l'utilisateur a voté pour une élection en cours
        $hasVoted = Vote::where('electeur_id', $user->id)
            ->where('election_id', $electionId)
            ->exists();
        return response()->json([
            'has_voted' => $hasVoted
        ]);
    }
}
