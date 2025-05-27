<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidature;
use App\Models\Election;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CandidatureController extends Controller
{
    /**
     * Afficher la liste des candidatures
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Candidature::with(['election', 'candidat', 'valideePar']);

        // Filtres optionnels
        if ($request->has('election_id')) {
            $query->where('election_id', $request->election_id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('candidat_id')) {
            $query->where('candidat_id', $request->candidat_id);
        }

        $candidatures = $query->orderBy('date_soumission', 'desc')->get();

        return response()->json($candidatures);
    }

    /**
     * Créer une nouvelle candidature
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'election_id' => 'required|exists:elections,id',
            'programme' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier si l'utilisateur est un PER
        if ($request->user()->type_personnel !== 'PER') {
            return response()->json([
                'message' => 'Seuls les personnels enseignants-chercheurs (PER) peuvent soumettre une candidature'
            ], 403);
        }

        // Récupérer l'élection
        $election = Election::findOrFail($request->election_id);

        // Vérifier si l'élection est ouverte aux candidatures
        if ($election->statut !== 'OUVERTE') {
            return response()->json([
                'message' => 'Cette élection n\'est pas ouverte aux candidatures'
            ], 422);
        }

        // Vérifier les dates de candidature
        $now = Carbon::now();
        if ($now->lt(Carbon::parse($election->date_debut_candidature)) || $now->gt(Carbon::parse($election->date_fin_candidature))) {
            return response()->json([
                'message' => 'La période de candidature n\'est pas active'
            ], 422);
        }

        // Vérifier si l'utilisateur a déjà une candidature pour cette élection
        $existingCandidature = Candidature::where('election_id', $request->election_id)
            ->where('candidat_id', $request->user()->id)
            ->first();

        if ($existingCandidature) {
            return response()->json([
                'message' => 'Vous avez déjà soumis une candidature pour cette élection'
            ], 422);
        }

        $candidature = Candidature::create([
            'election_id' => $request->election_id,
            'candidat_id' => $request->user()->id,
            'programme' => $request->programme,
            'statut' => 'EN_ATTENTE',
            'date_soumission' => $now,
        ]);

        return response()->json($candidature->load(['election', 'candidat']), 201);
    }

    /**
     * Afficher une candidature spécifique
     *
     * @param  \App\Models\Candidature  $candidature
     * @return \Illuminate\Http\Response
     */
    public function show(Candidature $candidature)
    {
        return response()->json($candidature->load(['election', 'candidat', 'valideePar']));
    }

    /**
     * Mettre à jour une candidature
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidature  $candidature
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Candidature $candidature)
    {
        // Vérifier si l'utilisateur est le propriétaire de la candidature
        if ($request->user()->id !== $candidature->candidat_id) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à modifier cette candidature'
            ], 403);
        }

        // Vérifier si la candidature est encore en attente
        if ($candidature->statut !== 'EN_ATTENTE') {
            return response()->json([
                'message' => 'Seules les candidatures en attente peuvent être modifiées'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'programme' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier si l'élection est encore ouverte aux candidatures
        $election = $candidature->election;
        $now = Carbon::now();
        if ($now->gt(Carbon::parse($election->date_fin_candidature))) {
            return response()->json([
                'message' => 'La période de candidature est terminée'
            ], 422);
        }

        $candidature->update([
            'programme' => $request->programme,
            'date_soumission' => $now,
        ]);

        return response()->json($candidature->load(['election', 'candidat']));
    }

    /**
     * Valider ou rejeter une candidature
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidature  $candidature
     * @return \Illuminate\Http\Response
     */
    public function valider(Request $request, Candidature $candidature)
    {
        $validator = Validator::make($request->all(), [
            'statut' => ['required', Rule::in(['VALIDEE', 'REJETEE'])],
            'commentaire_admin' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier si la candidature est en attente
        if ($candidature->statut !== 'EN_ATTENTE') {
            return response()->json([
                'message' => 'Cette candidature a déjà été traitée'
            ], 422);
        }

        $candidature->update([
            'statut' => $request->statut,
            'commentaire_admin' => $request->commentaire_admin,
            'date_validation' => Carbon::now(),
            'validee_par' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Candidature ' . ($request->statut === 'VALIDEE' ? 'validée' : 'rejetée') . ' avec succès',
            'candidature' => $candidature->load(['election', 'candidat', 'valideePar'])
        ]);
    }

    /**
     * Retirer une candidature
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidature  $candidature
     * @return \Illuminate\Http\Response
     */
    public function retirer(Request $request, Candidature $candidature)
    {
        // Vérifier si l'utilisateur est le propriétaire de la candidature
        if ($request->user()->id !== $candidature->candidat_id) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à retirer cette candidature'
            ], 403);
        }

        // Vérifier si la candidature peut être retirée
        if ($candidature->election->statut === 'EN_COURS' || $candidature->election->statut === 'FERMEE') {
            return response()->json([
                'message' => 'Vous ne pouvez pas retirer votre candidature une fois que l\'élection est en cours ou fermée'
            ], 422);
        }

        $candidature->delete();

        return response()->json([
            'message' => 'Candidature retirée avec succès'
        ]);
    }

    public function ownCandidature(Request $request)
    {
        return response()->json($request->user()->candidatures()->with('election')->get());
    }

    public function pending()
    {
        return response()->json(
            Candidature::with(['election', 'candidat'])
                ->where('statut', 'EN_ATTENTE')
                ->orderBy('date_soumission', 'desc')
                ->get()
        );
    }
}
