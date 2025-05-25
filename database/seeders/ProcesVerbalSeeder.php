<?php

namespace Database\Seeders;

use App\Models\Election;
use App\Models\ProcesVerbal;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProcesVerbalSeeder extends Seeder
{
    public function run()
    {
        // Récupérer les élections terminées
        $elections = Election::where('statut', 'termine')->get();
        
        // Récupérer les utilisateurs administrateurs
        $administrateurs = User::where('type_personnel', 'ADMIN')->get();
        
        foreach ($elections as $election) {
            // Vérifier s'il n'existe pas déjà un PV pour cette élection
            $pvExiste = ProcesVerbal::where('election_id', $election->id)->exists();
            
            if (!$pvExiste) {
                $admin = $administrateurs->random();
                
                ProcesVerbal::create([
                    'election_id' => $election->id,
                    'user_id' => $admin->id,
                    'contenu' => $this->genererContenuPV($election),
                    'date_redaction' => now(),
                    'statut' => 'validé',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
    
    private function genererContenuPV($election)
    {
        $date = now()->format('d/m/Y');
        $heure = now()->format('H:i');
        
        return "# PROCÈS-VERBAL DE L'ÉLECTION\n\n" .
               "**Titre de l'élection :** {$election->titre}\n\n" .
               "**Date de l'élection :** {$election->date_debut->format('d/m/Y')} au {$election->date_fin->format('d/m/Y')}\n\n" .
               "**Lieu :** Université de Thiès, Sénégal\n\n" .
               "**Présents :**\n" .
               "- Membres du bureau électoral\n" .
               "- Représentants des candidats\n" .
               "- Observateurs\n\n" .
               "**Ordre du jour :**\n" .
               "1. Ouverture de la séance\n" .
               "2. Vérification du quorum\n" .
               "3. Déroulement du scrutin\n" .
               "4. Dépouillement des votes\n" .
               "5. Proclamation des résultats\n" .
               "6. Divers\n\n" .
               "**Déroulement :**\n" .
               "L'élection s'est déroulée dans le calme et la transparence. Les opérations de vote se sont déroulées conformément au règlement électoral de l'université.\n\n" .
               "**Résultats :**\n" .
               "- Nombre d'inscrits : " . rand(50, 200) . "\n" .
               "- Nombre de votants : " . rand(30, 150) . "\n" .
               "- Bulletins nuls : " . rand(0, 5) . "\n" .
               "- Suffrages exprimés : " . rand(25, 145) . "\n\n" .
               "**Résultats détaillés :**\n" .
               "(Les résultats détaillés seront ajoutés après le dépouillement complet)\n\n" .
               "**Observations :**\n" .
               "Aucun incident à signaler.\n\n" .
               "**Clôture :**\n" .
               "La séance est levée à {$heure}.\n\n" .
               "Fait à Thiès, le {$date}\n\n" .
               "Le Président du bureau électoral\n\n" .
               "_Signature_\n\n" .
               "Le Secrétaire\n\n" .
               "_Signature_";
    }
}
