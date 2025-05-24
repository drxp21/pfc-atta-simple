<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartementController extends Controller
{
    /**
     * Afficher la liste des départements
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $departements = Departement::all();
        return response()->json([
            'data' => $departements->items(),
            'links' => [
                'first' => $departements->url(1),
                'last' => $departements->url($departements->lastPage()),
                'prev' => $departements->previousPageUrl(),
                'next' => $departements->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $departements->currentPage(),
                'from' => $departements->firstItem(),
                'last_page' => $departements->lastPage(),
                'path' => $departements->path(),
                'per_page' => $departements->perPage(),
                'to' => $departements->lastItem(),
                'total' => $departements->total(),
            ],
        ]);
    }

    /**
     * Créer un nouveau département
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255|unique:departements',
            'code' => 'required|string|max:50|unique:departements',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $departement = Departement::create([
            'nom' => $request->nom,
            'code' => $request->code,
        ]);

        return response()->json($departement, 201);
    }

    /**
     * Afficher un département spécifique
     *
     * @param  \App\Models\Departement  $departement
     * @return \Illuminate\Http\Response
     */
    public function show(Departement $departement)
    {
        return response()->json($departement);
    }

    /**
     * Mettre à jour un département
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Departement  $departement
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Departement $departement)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255|unique:departements,nom,' . $departement->id,
            'code' => 'required|string|max:50|unique:departements,code,' . $departement->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $departement->update([
            'nom' => $request->nom,
            'code' => $request->code,
        ]);

        return response()->json($departement);
    }

    /**
     * Supprimer un département
     *
     * @param  \App\Models\Departement  $departement
     * @return \Illuminate\Http\Response
     */
    public function destroy(Departement $departement)
    {
        // Vérifier si le département est utilisé
        if ($departement->users()->count() > 0 || $departement->elections()->count() > 0) {
            return response()->json([
                'message' => 'Ce département ne peut pas être supprimé car il est utilisé par des utilisateurs ou des élections.'
            ], 422);
        }

        $departement->delete();

        return response()->json([
            'message' => 'Département supprimé avec succès'
        ]);
    }
}
