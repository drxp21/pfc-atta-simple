<?php

namespace Database\Seeders;

use App\Models\Candidature;
use App\Models\Election;
use App\Models\User;
use App\Models\Departement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CandidatureSeeder extends Seeder
{
    public function run()
    {
        $admin = User::where('type_personnel', 'ADMIN')->firstOrFail();
        $perUsers = User::where('type_personnel', 'PER')->get();

        if ($perUsers->count() < 3) {
            // Not enough PER users, create some or throw error
            // For this example, we assume UserSeeder provided enough.
            $this->command->error('CandidatureSeeder: Not enough PER users to seed candidatures. Please run UserSeeder.');
            // You might want to create users here if necessary:
            // User::factory()->count(5)->create(['type_personnel' => 'PER', 'departement_id' => Departement::first()->id]);
            // $perUsers = User::where('type_personnel', 'PER')->get();
            return; 
        }

        // Clean existing candidatures
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Candidature::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // --- Candidatures for Election 2: CHEF_DEPARTEMENT, EN_COURS ---
        $electionChefDept = Election::where('titre', 'Élection Chef du Département Informatique - En Cours')->first();
        if ($electionChefDept) {
            $departementInfo = Departement::where('code', 'INFO')->first();
            // Try to get PER from the same department
            $candidatsChefDept = $perUsers->where('departement_id', $departementInfo ? $departementInfo->id : null)->shuffle()->take(2);
            if ($candidatsChefDept->count() < 1) { // At least one candidate
                $candidatsChefDept = $perUsers->shuffle()->take(2); // Fallback to any PER
            }

            foreach ($candidatsChefDept as $candidat) {
                $dateSoumission = $this->getRandomDateInRange(
                    Carbon::parse($electionChefDept->date_debut_candidature),
                    Carbon::parse($electionChefDept->date_fin_candidature)
                );
                Candidature::create([
                    'election_id' => $electionChefDept->id,
                    'candidat_id' => $candidat->id,
                    'programme' => $this->genererProgramme($candidat->prenom, $candidat->nom, $electionChefDept->type_election),
                    'statut' => 'VALIDEE',
                    'date_soumission' => $dateSoumission,
                    'date_validation' => $this->getRandomDateInRange($dateSoumission, Carbon::parse($electionChefDept->date_fin_candidature)->addDays(1)), // Validated shortly after submission
                    'validee_par' => $admin->id,
                ]);
            }
        } else {
            $this->command->warn('CandidatureSeeder: Election \'Élection Chef du Département Informatique - En Cours\' not found.');
        }

        // --- Candidatures for Election 3: VICE_RECTEUR, EN_COURS ---
        $electionViceRecteur = Election::where('titre', 'Élection Vice-Recteur - En Cours')->first();
        if ($electionViceRecteur) {
            $candidatsViceRecteur = $perUsers->shuffle()->take(2); // Take 2 random PER users

            foreach ($candidatsViceRecteur as $candidat) {
                $dateSoumission = $this->getRandomDateInRange(
                    Carbon::parse($electionViceRecteur->date_debut_candidature),
                    Carbon::parse($electionViceRecteur->date_fin_candidature)
                );
                Candidature::create([
                    'election_id' => $electionViceRecteur->id,
                    'candidat_id' => $candidat->id,
                    'programme' => $this->genererProgramme($candidat->prenom, $candidat->nom, $electionViceRecteur->type_election),
                    'statut' => 'VALIDEE',
                    'date_soumission' => $dateSoumission,
                    'date_validation' => $this->getRandomDateInRange($dateSoumission, Carbon::parse($electionViceRecteur->date_fin_candidature)->addDays(1)), // Validated shortly after submission
                    'validee_par' => $admin->id,
                ]);
            }
        } else {
            $this->command->warn('CandidatureSeeder: Election \'Élection Vice-Recteur - En Cours\' not found.');
        }
        
        // Election 1 (Ouverte aux candidatures) is intentionally left without seeded candidatures.

        $this->command->info('CandidatureSeeder: Seeded specific candidatures.');
    }
    
    private function genererProgramme($prenom, $nom, $typeElection)
    {
        $poste = $this->getPosteFromType($typeElection);
        $themes = $this->getThemesByElectionType($typeElection);
        
        $programme = "# Programme électoral pour le poste de $poste\n\n";
        $programme .= "Candidat: $prenom $nom\n\n";
        $programme .= "## Engagement\n\n";
        $programme .= "Je m'engage à œuvrer pour :\n\n";
        
        // Sélectionner 3 à 5 thèmes aléatoires
        $nbThemes = rand(3, min(5, count($themes)));
        if (empty($themes)) {
            $themesSelectionnes = [];
        } else {
            $themesSelectionnes = (array) array_rand($themes, $nbThemes);
        }
                
        foreach ($themesSelectionnes as $index => $themeKey) {
            // If array_rand returns single key, it's not in an array
            $theme = $themes[$themeKey]; 
            $programme .= ($index + 1) . ". **" . $theme['titre'] . "**\n";
            $programme .= "   - " . $theme['description'] . "\n";
            $programme .= "   - Objectif: " . $theme['objectif'] . "\n\n";
        }
        
        $programme .= "## Vision\n\n";
        $programme .= $this->getVisionStatement($typeElection, $prenom, $nom);
        
        return $programme;
    }
    
    private function getPosteFromType($typeElection)
    {
        return match($typeElection) {
            'CHEF_DEPARTEMENT' => 'Chef de Département',
            'DIRECTEUR_UFR' => 'Directeur d\'UFR',
            'VICE_RECTEUR' => 'Vice-Recteur',
            'REPRESENTANT_CONSEIL' => 'Représentant au Conseil',
            default => 'Représentant'
        };
    }
    
    private function getThemesByElectionType($typeElection)
    {
        $themesCommuns = [
            [
                'titre' => 'Amélioration de la qualité de l\'enseignement',
                'description' => 'Renforcement des méthodes pédagogiques et des ressources éducatives.',
                'objectif' => 'Mise en place de formations pour les enseignants et d\'un système d\'évaluation continue.'
            ],
            [
                'titre' => 'Développement de la recherche',
                'description' => 'Soutien aux projets de recherche et aux publications scientifiques.',
                'objectif' => 'Augmenter de 30% le nombre de publications dans des revues indexées.'
            ]
        ];
        
        $themesSpecifiques = match($typeElection) {
            'CHEF_DEPARTEMENT' => [
                [
                    'titre' => 'Gestion du département',
                    'description' => 'Optimisation de l\'organisation et de la gestion administrative du département.',
                    'objectif' => 'Mise en place d\'un système de gestion plus efficace des ressources humaines et matérielles.'
                ],
                [
                    'titre' => 'Cohésion d\'équipe',
                    'description' => 'Renforcement de la collaboration entre les enseignants du département.',
                    'objectif' => 'Organisation d\'au moins 3 séminaires de travail par an.'
                ]
            ],
            'DIRECTEUR_UFR' => [
                [
                    'titre' => 'Politique de formation',
                    'description' => 'Mise à jour des maquettes de formation en adéquation avec le marché de l\'emploi.',
                    'objectif' => 'Révision complète des offres de formation d\'ici la fin du mandat.'
                ],
                [
                    'titre' => 'Partenariats',
                    'description' => 'Développement de partenariats avec les entreprises et les institutions académiques.',
                    'objectif' => 'Signature de 10 nouveaux partenariats par an.'
                ]
            ],
            'VICE_RECTEUR' => [
                [
                    'titre' => 'Stratégie universitaire',
                    'description' => 'Élaboration d\'une vision stratégique pour le développement de l\'université.',
                    'objectif' => 'Définition d\'un plan stratégique sur 5 ans.'
                ],
                [
                    'titre' => 'Relations internationales',
                    'description' => 'Renforcement de la visibilité internationale de l\'université.',
                    'objectif' => 'Doubler le nombre d\'accords de coopération internationaux.'
                ]
            ],
            'REPRESENTANT_CONSEIL' => [
                [
                    'titre' => 'Représentation étudiante active',
                    'description' => 'Assurer une voix forte et constructive pour les étudiants au sein du conseil.',
                    'objectif' => 'Participation à toutes les réunions et consultation régulière des étudiants.'
                ]
            ],
            default => []
        };
        
        return array_merge($themesCommuns, $themesSpecifiques);
    }
    
    private function getVisionStatement($typeElection, $prenom, $nom)
    {
        $poste = $this->getPosteFromType($typeElection);
        
        $visions = [
            "En tant que $poste, je m'engage à faire de notre institution un modèle d'excellence académique et de recherche, en plaçant la réussite étudiante et l'innovation pédagogique au cœur de nos priorités.",
            "Ma vision pour ce mandat est de renforcer le rayonnement de notre institution, en développant des partenariats stratégiques et en améliorant continuellement la qualité de nos formations et de notre recherche.",
            "Je crois en une université ouverte, innovante et tournée vers l'avenir. Mon engagement est de travailler sans relâche pour offrir à tous les membres de notre communauté universitaire un environnement propice à l'épanouissement et à la réussite.",
            "Notre institution doit être un acteur clé du développement économique et social. Je m'engage à mettre en œuvre des actions concrètes pour renforcer nos liens avec le monde socio-économique et répondre aux défis de notre époque."
        ];
        
        return $visions[array_rand($visions)];
    }
    
    private function getRandomDateInRange($startDate, $endDate)
    {
        // Ensure dates are Carbon instances for comparison and manipulation
        $startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        if ($startDate->greaterThan($endDate)) {
            // If start is after end (e.g. candidature period already passed for new submissions)
            // and we need a date in this range for an *existing* submission, this logic might need adjustment.
            // For seeding new submission dates, this case implies an invalid range.
            // However, if this function is used for validation dates *after* submission period end, it's fine.
            // For now, let's assume endDate can be slightly before startDate if we're picking from a past range.
            // To be safe, if range is invalid for picking a *new* random date, return the earlier of the two.
             return $endDate->toDateTimeString(); // Or handle error, or $startDate if that makes more sense.
        }

        $min = $startDate->timestamp;
        $max = $endDate->timestamp;
        
        if ($min > $max) { // Additional safety for timestamp conversion issues or inverted range
            return $startDate->toDateTimeString();
        }

        try {
            $randomTimestamp = rand($min, $max);
            return Carbon::createFromTimestamp($randomTimestamp)->toDateTimeString();
        } catch (\Exception $e) {
            // Fallback if rand fails (e.g., $min > $max after all checks, though unlikely)
            return $startDate->toDateTimeString();
        }
    }
    
    private function getRandomRejectionReason()
    {
        $reasons = [
            "Dossier de candidature incomplet",
            "Conditions d'éligibilité non remplies",
            "Délai de dépôt dépassé",
            "Pièces justificatives manquantes",
            "Incompatibilité avec les critères de sélection"
        ];
        
        return $reasons[array_rand($reasons)];
    }
}
