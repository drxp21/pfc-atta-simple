<?php

namespace Database\Seeders;

use App\Models\Candidature;
use App\Models\Election;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CandidatureSeeder extends Seeder
{
    public function run()
    {
        // Récupérer les élections
        $elections = Election::all();
        $admin = User::where('type_personnel', 'ADMIN')->first();
        
        if (!$admin) {
            $admin = User::first();
        }
        
        // Pour chaque élection, créer des candidatures
        foreach ($elections as $election) {
            // Récupérer les utilisateurs éligibles (uniquement PER pour les postes importants)
            $users = User::where('type_personnel', 'PER') // Seuls les PER peuvent être candidats
                ->where('departement_id', $election->departement_id) // Uniquement les PER du département concerné
                ->inRandomOrder()
                ->take(rand(2, 5)) // Entre 2 et 5 candidats par élection
                ->get();
            
            // Si pas assez de PER dans le département, prendre des PER d'autres départements
            if ($users->count() < 2) {
                $users = User::where('type_personnel', 'PER')
                    ->inRandomOrder()
                    ->take(rand(2, 5))
                    ->get();
            }
            
            $statuts = ['EN_ATTENTE', 'VALIDEE', 'REJETEE'];
            
            foreach ($users as $user) {
                $statut = $statuts[array_rand($statuts)];
                $dateSoumission = $this->getRandomDateInRange(
                    $election->date_debut_candidature,
                    $election->date_fin_candidature
                );
                
                $candidatureData = [
                    'election_id' => $election->id,
                    'candidat_id' => $user->id,
                    'programme' => $this->genererProgramme($user->prenom, $user->nom, $election->type_election),
                    'statut' => $statut,
                    'date_soumission' => $dateSoumission,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                // Si la candidature est validée ou rejetée, ajouter les dates et la personne qui a validé
                if (in_array($statut, ['VALIDEE', 'REJETEE'])) {
                    $candidatureData['date_validation'] = $this->getRandomDateInRange(
                        $dateSoumission,
                        $election->date_fin_candidature
                    );
                    $candidatureData['validee_par'] = $admin->id;
                    
                    if ($statut === 'REJETEE') {
                        $candidatureData['commentaire_admin'] = $this->getRandomRejectionReason();
                    }
                }
                
                Candidature::create($candidatureData);
            }
        }
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
        $themesSelectionnes = array_rand($themes, $nbThemes);
        
        if (!is_array($themesSelectionnes)) {
            $themesSelectionnes = [$themesSelectionnes];
        }
        
        foreach ($themesSelectionnes as $index => $themeIndex) {
            $theme = $themes[$themeIndex];
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
        $min = strtotime($startDate);
        $max = strtotime($endDate);
        $randomDate = rand($min, $max);
        return date('Y-m-d H:i:s', $randomDate);
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
