<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidature;
use App\Models\Departement;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Afficher les statistiques du tableau de bord
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Carbon::setLocale('fr');
        // Quick Stats
        $ongoingElectionsCount = Election::where('statut', 'EN_COURS')->count();
        $activeCandidacyCount = Candidature::where('statut', 'VALIDEE') // Assuming 'VALIDEE' means active
            ->whereHas('election', function ($query) {
                $query->where('statut', 'OUVERTE'); // Only for elections where candidature is open
            })
            ->count();
        $votesCastCount = Vote::count();

        // Upcoming Elections (status 'OUVERTE' or 'BROUILLON' and date_debut_vote in the future)
        $upcomingElections = Election::whereIn('statut', ['OUVERTE', 'BROUILLON'])
            ->where('date_debut_vote', '>', Carbon::now())
            ->orderBy('date_debut_vote', 'asc')
            ->take(3)
            ->get()
            ->map(function ($election) {
                return [
                    'id' => $election->id, // Assuming you want the model ID
                    'title' => $election->titre,
                    'startDate' => Carbon::parse($election->date_debut_vote)->isoFormat('D MMMM YYYY'),
                    'detailsLink' => '/dashboard/elections/election/' . $election->id, // Generic link
                ];
            });

        // Recent Activities (combining votes, candidacies, and election results/status changes)
        // This is a simplified version. You might need a more sophisticated way to track "activities"
        // For example, creating an "Activity" model or using a notification system.

        $recentVotes = Vote::with('election')
            ->orderBy('date_vote', 'desc')
            ->take(2) // Adjust as needed
            ->get()
            ->map(function ($vote) {
                return [
                    'id' => 'vote_' . $vote->id,
                    'icon' => '✓',
                    'description' => 'Vote effectué - ' . $vote->election->titre,
                    'dateRelative' => Carbon::parse($vote->date_vote)->diffForHumans(),
                    'timestamp' => Carbon::parse($vote->date_vote)->timestamp,
                ];
            });

        $recentCandidatures = Candidature::with('election')
            ->orderBy('date_soumission', 'desc')
            ->take(2) // Adjust as needed
            ->get()
            ->map(function ($candidature) {
                return [
                    'id' => 'candidacy_' . $candidature->id,
                    'icon' => '📝',
                    'description' => 'Candidature soumise - ' . $candidature->election->titre,
                    'dateRelative' => Carbon::parse($candidature->date_soumission)->diffForHumans(),
                    'timestamp' => Carbon::parse($candidature->date_soumission)->timestamp,
                ];
            });

        // Example: Recent election status changes (e.g., an election became 'FERMEE')
        // This requires tracking when the status changed, which might not be directly available
        // on the Election model without a dedicated 'status_updated_at' or similar field.
        // For this example, I'll use 'updated_at' for elections that are 'FERMEE'
        // and assume 'updated_at' reflects the closure time.
        $recentResults = Election::where('statut', 'FERMEE')
            ->orderBy('updated_at', 'desc') // Assuming updated_at reflects when it was closed
            ->take(1) // Adjust as needed
            ->get()
            ->map(function ($election) {
                return [
                    'id' => 'results_' . $election->id,
                    'icon' => '📊',
                    'description' => 'Résultats publiés - ' . $election->titre, // Or "Election fermée"
                    'dateRelative' => Carbon::parse($election->updated_at)->diffForHumans(),
                    'timestamp' => Carbon::parse($election->updated_at)->timestamp,
                ];
            });

        $recentActivities = collect()
            ->merge($recentVotes)
            ->merge($recentCandidatures)
            ->merge($recentResults)
            ->sortByDesc('timestamp') // Sort all activities by their actual timestamp
            ->take(3) // Take the most recent 3 overall
            ->map(function ($activity) { // Remove the temporary timestamp
                unset($activity['timestamp']);
                return $activity;
            })
            ->values(); // Re-index the array


        return response()->json([
            'quickStats' => [
                'ongoingElectionsCount' => $ongoingElectionsCount,
                'activeCandidacyCount' => $activeCandidacyCount,
                'votesCastCount' => $votesCastCount,
            ],
            'upcomingElections' => $upcomingElections,
            'recentActivities' => $recentActivities,
        ]);
    }
}
