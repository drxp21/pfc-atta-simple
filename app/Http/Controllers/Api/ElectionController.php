<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElecteurAutorise;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ElectionController extends Controller
{
    /**
     * Afficher la liste des élections
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Election::with(['departement', 'createdBy']);

        // Filtres optionnels
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('type_election')) {
            $query->where('type_election', $request->type_election);
        }

        if ($request->has('departement_id')) {
            $query->where('departement_id', $request->departement_id);
        }

        $elections = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $elections,
        ]);


    }

    /**
     * Créer une nouvelle élection
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'type_election' => ['required', Rule::in(['CHEF_DEPARTEMENT', 'DIRECTEUR_UFR', 'VICE_RECTEUR'])],
            'departement_id' => 'nullable|exists:departements,id',
            'date_debut_candidature' => 'required|date|after_or_equal:today',
            'date_fin_candidature' => 'required|date|after:date_debut_candidature',
            'date_debut_vote' => 'required|date|after:date_fin_candidature',
            'date_fin_vote' => 'required|date|after:date_debut_vote',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier la cohérence entre le type d'élection et le département
        if (in_array($request->type_election, ['CHEF_DEPARTEMENT', 'DIRECTEUR_UFR']) && !$request->departement_id) {
            return response()->json([
                'message' => 'Un département doit être spécifié pour ce type d\'élection'
            ], 422);
        }

        $election = Election::create([
            'titre' => $request->titre,
            'description' => $request->description,
            'type_election' => $request->type_election,
            'statut' => 'BROUILLON',
            'departement_id' => $request->departement_id,
            'date_debut_candidature' => $request->date_debut_candidature,
            'date_fin_candidature' => $request->date_fin_candidature,
            'date_debut_vote' => $request->date_debut_vote,
            'date_fin_vote' => $request->date_fin_vote,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($election->load(['departement', 'createdBy']), 201);
    }

    /**
     * Afficher une élection spécifique
     *
     * @param  \App\Models\Election  $election
     * @return \Illuminate\Http\Response
     */
    public function show(Election $election)
    {
        return response()->json($election->load(['departement', 'createdBy', 'candidatures.candidat']));
    }

    /**
     * Mettre à jour une élection
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Election  $election
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Election $election)
    {
        // Vérifier si l'élection est encore en brouillon
        if ($election->statut !== 'BROUILLON') {
            return response()->json([
                'message' => 'Seules les élections en brouillon peuvent être modifiées'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'type_election' => ['required', Rule::in(['CHEF_DEPARTEMENT', 'DIRECTEUR_UFR', 'VICE_RECTEUR'])],
            'departement_id' => 'nullable|exists:departements,id',
            'date_debut_candidature' => 'required|date|after_or_equal:today',
            'date_fin_candidature' => 'required|date|after:date_debut_candidature',
            'date_debut_vote' => 'required|date|after:date_fin_candidature',
            'date_fin_vote' => 'required|date|after:date_debut_vote',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier la cohérence entre le type d'élection et le département
        if (in_array($request->type_election, ['CHEF_DEPARTEMENT', 'DIRECTEUR_UFR']) && !$request->departement_id) {
            return response()->json([
                'message' => 'Un département doit être spécifié pour ce type d\'élection'
            ], 422);
        }

        $election->update([
            'titre' => $request->titre,
            'description' => $request->description,
            'type_election' => $request->type_election,
            'departement_id' => $request->departement_id,
            'date_debut_candidature' => $request->date_debut_candidature,
            'date_fin_candidature' => $request->date_fin_candidature,
            'date_debut_vote' => $request->date_debut_vote,
            'date_fin_vote' => $request->date_fin_vote,
        ]);

        return response()->json($election->load(['departement', 'createdBy']));
    }

    /**
     * Supprimer une élection
     *
     * @param  \App\Models\Election  $election
     * @return \Illuminate\Http\Response
     */
    public function destroy(Election $election)
    {
        // Vérifier si l'élection est encore en brouillon
        if ($election->statut !== 'BROUILLON') {
            return response()->json([
                'message' => 'Seules les élections en brouillon peuvent être supprimées'
            ], 422);
        }

        $election->delete();

        return response()->json([
            'message' => 'Élection supprimée avec succès'
        ]);
    }

    /**
     * Ouvrir une élection pour les candidatures
     *
     * @param  \App\Models\Election  $election
     * @return \Illuminate\Http\Response
     */
    public function ouvrir(Election $election)
    {
        // Vérifier si l'élection est en brouillon
        if ($election->statut !== 'BROUILLON') {
            return response()->json([
                'message' => 'Seules les élections en brouillon peuvent être ouvertes'
            ], 422);
        }

        // Vérifier si la date de début de candidature est valide
        if (Carbon::parse($election->date_debut_candidature)->isPast()) {
            return response()->json([
                'message' => 'La date de début de candidature est dépassée, veuillez la mettre à jour'
            ], 422);
        }

        // Mettre à jour le statut
        $election->update(['statut' => 'OUVERTE']);

        // Autoriser les électeurs selon le type d'élection
        $this->autoriserElecteurs($election);

        return response()->json([
            'message' => 'Élection ouverte avec succès',
            'election' => $election->load(['departement', 'createdBy'])
        ]);
    }

    /**
     * Fermer une élection
     *
     * @param  \App\Models\Election  $election
     * @return \Illuminate\Http\Response
     */
    public function fermer(Election $election)
    {
        // Vérifier si l'élection est en cours
        if ($election->statut !== 'EN_COURS') {
            return response()->json([
                'message' => 'Seules les élections en cours peuvent être fermées'
            ], 422);
        }

        // Mettre à jour le statut
        $election->update(['statut' => 'FERMEE']);

        return response()->json([
            'message' => 'Élection fermée avec succès',
            'election' => $election->load(['departement', 'createdBy'])
        ]);
    }

    /**
     * Autoriser les électeurs selon le type d'élection
     *
     * @param  \App\Models\Election  $election
     * @return void
     */
    private function autoriserElecteurs(Election $election)
    {
        $now = Carbon::now();
        $electeurs = [];

        switch ($election->type_election) {
            case 'CHEF_DEPARTEMENT':
            case 'DIRECTEUR_UFR':
                // Seuls les PER du département peuvent voter
                $electeurs = User::where('type_personnel', 'PER')
                    ->where('departement_id', $election->departement_id)
                    ->get();
                break;

            case 'VICE_RECTEUR':
                // Tous les PER et PATS peuvent voter
                $electeurs = User::whereIn('type_personnel', ['PER', 'PATS'])->get();
                break;
        }

        foreach ($electeurs as $electeur) {
            ElecteurAutorise::create([
                'election_id' => $election->id,
                'electeur_id' => $electeur->id,
                'a_vote' => false,
                'date_autorisation' => $now,
            ]);
        }
    }
}
