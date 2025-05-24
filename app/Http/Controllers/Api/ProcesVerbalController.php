<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidature;
use App\Models\Election;
use App\Models\ElecteurAutorise;
use App\Models\ProcesVerbal;
use App\Models\Resultat;
use App\Models\Vote;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ProcesVerbalController extends Controller
{
    /**
     * Afficher la liste des procès-verbaux
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = ProcesVerbal::with(['election', 'generePar']);

        if ($request->has('election_id')) {
            $query->where('election_id', $request->election_id);
        }

        $procesVerbaux = $query->orderBy('date_generation', 'desc')->get();

        return response()->json($procesVerbaux);
    }

    /**
     * Afficher un procès-verbal spécifique
     *
     * @param  \App\Models\ProcesVerbal  $procesVerbal
     * @return \Illuminate\Http\Response
     */
    public function show(ProcesVerbal $procesVerbal)
    {
        return response()->json($procesVerbal->load(['election', 'generePar']));
    }

    /**
     * Générer un procès-verbal pour une élection
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Election  $election
     * @return \Illuminate\Http\Response
     */
    public function generer(Request $request, Election $election)
    {
        // Vérifier si l'élection est fermée
        if ($election->statut !== 'FERMEE') {
            return response()->json([
                'message' => 'Un procès-verbal ne peut être généré que pour une élection fermée'
            ], 422);
        }

        // Vérifier si les résultats ont été calculés
        $resultats = Resultat::where('election_id', $election->id)->get();
        if ($resultats->isEmpty()) {
            return response()->json([
                'message' => 'Les résultats doivent être calculés avant de générer un procès-verbal'
            ], 422);
        }

        // Statistiques
        $nbElecteursInscrits = ElecteurAutorise::where('election_id', $election->id)->count();
        $nbVotesExprimes = Vote::where('election_id', $election->id)->count();
        $nbVotesBlancs = Vote::where('election_id', $election->id)->where('vote_blanc', true)->count();
        $nbAbstentions = $nbElecteursInscrits - $nbVotesExprimes;
        $tauxParticipation = $nbElecteursInscrits > 0 ? round(($nbVotesExprimes / $nbElecteursInscrits) * 100, 2) : 0;

        // Récupérer les candidatures validées avec leurs résultats
        $candidatures = Candidature::where('election_id', $election->id)
            ->where('statut', 'VALIDEE')
            ->with(['candidat', 'resultats'])
            ->get();

        // Générer le contenu HTML du procès-verbal
        $contenuHtml = $this->genererContenuHtml(
            $election,
            $candidatures,
            $resultats,
            $nbElecteursInscrits,
            $nbVotesExprimes,
            $nbVotesBlancs,
            $nbAbstentions,
            $tauxParticipation
        );

        // Créer le procès-verbal
        $procesVerbal = ProcesVerbal::create([
            'election_id' => $election->id,
            'contenu_html' => $contenuHtml,
            'nb_electeurs_inscrits' => $nbElecteursInscrits,
            'nb_votes_exprimes' => $nbVotesExprimes,
            'nb_votes_blancs' => $nbVotesBlancs,
            'nb_abstentions' => $nbAbstentions,
            'date_generation' => Carbon::now(),
            'genere_par' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Procès-verbal généré avec succès',
            'proces_verbal' => $procesVerbal->load(['election', 'generePar'])
        ], 201);
    }

    /**
     * Générer le contenu HTML du procès-verbal
     *
     * @param  \App\Models\Election  $election
     * @param  \Illuminate\Database\Eloquent\Collection  $candidatures
     * @param  \Illuminate\Database\Eloquent\Collection  $resultats
     * @param  int  $nbElecteursInscrits
     * @param  int  $nbVotesExprimes
     * @param  int  $nbVotesBlancs
     * @param  int  $nbAbstentions
     * @param  float  $tauxParticipation
     * @return string
     */
    private function genererContenuHtml(
        Election $election,
        $candidatures,
        $resultats,
        $nbElecteursInscrits,
        $nbVotesExprimes,
        $nbVotesBlancs,
        $nbAbstentions,
        $tauxParticipation
    ) {
        $html = '
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { text-align: center; color: #333; }
            h2 { color: #555; margin-top: 20px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .signature { margin-top: 50px; border-top: 1px solid #000; width: 200px; padding-top: 10px; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #777; }
        </style>
        
        <h1>PROCÈS-VERBAL DE DÉLIBÉRATION</h1>
        <h2>Élection : ' . htmlspecialchars($election->titre) . '</h2>
        
        <p><strong>Type d\'élection :</strong> ' . htmlspecialchars($election->type_election) . '</p>
        <p><strong>Date de l\'élection :</strong> du ' . $election->date_debut_vote->format('d/m/Y H:i') . ' au ' . $election->date_fin_vote->format('d/m/Y H:i') . '</p>';

        if ($election->departement) {
            $html .= '<p><strong>Département concerné :</strong> ' . htmlspecialchars($election->departement->nom) . '</p>';
        }

        $html .= '
        <h2>Résultats</h2>
        
        <table>
            <tr>
                <th>Rang</th>
                <th>Candidat</th>
                <th>Nombre de voix</th>
                <th>Pourcentage</th>
            </tr>';

        foreach ($resultats->sortBy('rang') as $resultat) {
            $candidature = $candidatures->firstWhere('id', $resultat->candidature_id);
            if ($candidature) {
                $html .= '
                <tr>
                    <td>' . $resultat->rang . '</td>
                    <td>' . htmlspecialchars($candidature->candidat->nom_complet) . '</td>
                    <td>' . $resultat->nb_voix . '</td>
                    <td>' . $resultat->pourcentage . '%</td>
                </tr>';
            }
        }

        $html .= '
        </table>
        
        <h2>Statistiques</h2>
        
        <table>
            <tr>
                <th>Statistique</th>
                <th>Valeur</th>
            </tr>
            <tr>
                <td>Nombre d\'électeurs inscrits</td>
                <td>' . $nbElecteursInscrits . '</td>
            </tr>
            <tr>
                <td>Nombre de votes exprimés</td>
                <td>' . $nbVotesExprimes . '</td>
            </tr>
            <tr>
                <td>Nombre de votes blancs</td>
                <td>' . $nbVotesBlancs . '</td>
            </tr>
            <tr>
                <td>Nombre d\'abstentions</td>
                <td>' . $nbAbstentions . '</td>
            </tr>
            <tr>
                <td>Taux de participation</td>
                <td>' . $tauxParticipation . '%</td>
            </tr>
        </table>
        
        <h2>Déclaration</h2>
        
        <p>
            En conséquence, ';

        // Déterminer le gagnant
        $gagnant = $resultats->where('rang', 1)->first();
        if ($gagnant) {
            $candidatureGagnante = $candidatures->firstWhere('id', $gagnant->candidature_id);
            if ($candidatureGagnante) {
                $html .= '<strong>' . htmlspecialchars($candidatureGagnante->candidat->nom_complet) . '</strong> est déclaré(e) élu(e) ';

                switch ($election->type_election) {
                    case 'CHEF_DEPARTEMENT':
                        $html .= 'Chef du Département ' . htmlspecialchars($election->departement->nom);
                        break;
                    case 'DIRECTEUR_UFR':
                        $html .= 'Directeur de l\'UFR ' . htmlspecialchars($election->departement->nom);
                        break;
                    case 'VICE_RECTEUR':
                        $html .= 'Vice-Recteur de l\'Université';
                        break;
                }

                $html .= ' avec <strong>' . $gagnant->nb_voix . ' voix</strong> (' . $gagnant->pourcentage . '%).';
            }
        } else {
            $html .= 'aucun candidat n\'a été déclaré élu.';
        }

        $html .= '
        </p>
        
        <p>
            Le présent procès-verbal a été établi le ' . Carbon::now()->format('d/m/Y à H:i') . '.
        </p>
        
        <div class="signature">
            Signature du responsable
        </div>
        
        <div class="footer">
            Système Électoral Universitaire - Procès-verbal généré automatiquement
        </div>';

        return $html;
    }

    /**
     * Télécharger un procès-verbal au format PDF
     *
     * @param  \App\Models\ProcesVerbal  $procesVerbal
     * @return \Illuminate\Http\Response
     */
    public function telecharger(ProcesVerbal $procesVerbal)
    {
        $election = $procesVerbal->election;
        $pdf = PDF::loadHTML($procesVerbal->contenu_html);

        return $pdf->download('proces-verbal-' . $election->id . '.pdf');
    }
}
