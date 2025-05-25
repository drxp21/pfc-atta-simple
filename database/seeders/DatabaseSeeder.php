<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Appeler les seeders dans l'ordre logique
        $this->call([
            // 1. Création des départements
            DepartementSeeder::class,
            
            // 2. Création des utilisateurs (ADMIN, PER, PATS)
            UserSeeder::class,
            
            // 3. Création des élections
            ElectionSeeder::class,
            
            // 4. Création des candidatures
            CandidatureSeeder::class,
            
            // 5. Création des votes (pour les élections en cours ou terminées)
            VoteSeeder::class,
            
            // 6. Création des procès-verbaux (pour les élections terminées)
            ProcesVerbalSeeder::class,
        ]);
    }
}
