<?php

namespace Database\Seeders;

use App\Models\Departement;
use App\Models\Election;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Added for truncate

class ElectionSeeder extends Seeder
{
    public function run()
    {
        // Ensure Carbon locale is set if needed for date operations, though not strictly for storage
        // Carbon::setLocale('fr'); 

        // Get Admin User
        $admin = User::where('type_personnel', 'ADMIN')->first();
        if (!$admin) {
            // Fallback if no admin, though UserSeeder should create one
            $admin = User::firstOrFail(); 
        }

        // Get a specific department for CHEF_DEPARTEMENT election
        $departementInfo = Departement::where('code', 'INFO')->first();
        if (!$departementInfo) {
            // Fallback or error if 'INFO' department doesn't exist
            // For simplicity, let's assume DepartementSeeder ran and it exists.
            // Or create it here if necessary: $departementInfo = Departement::factory()->create(['code' => 'INFO', 'nom' => 'Informatique']);
            $departementInfo = Departement::firstOrFail(); // Or handle error
        }

        // Clean existing elections before seeding new ones (optional, but good for consistency)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Disable FK checks for truncate
        Election::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Re-enable FK checks

        $now = Carbon::now();

        // --- Election 1: Ouverte aux candidatures --- 
        Election::create([
            'titre' => 'Election Test - Ouverte aux Candidatures',
            'description' => 'Cette élection est actuellement ouverte pour le dépôt des candidatures.',
            'type_election' => 'REPRESENTANT_CONSEIL', // Example type
            'statut' => 'OUVERTE',
            'departement_id' => null, // Or assign a generic one if needed by your model
            'date_debut_candidature' => $now->copy()->subDays(7),
            'date_fin_candidature' => $now->copy()->addDays(7),
            'date_debut_vote' => $now->copy()->addDays(10), // Vote starts after candidature ends
            'date_fin_vote' => $now->copy()->addDays(15),
            'created_by' => $admin->id,
        ]);

        // --- Election 2: CHEF_DEPARTEMENT, EN_COURS --- 
        Election::create([
            'titre' => 'Élection Chef du Département Informatique - En Cours',
            'description' => 'Élection pour le poste de Chef du Département Informatique.',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'EN_COURS',
            'departement_id' => $departementInfo->id,
            'date_debut_candidature' => $now->copy()->subDays(20),
            'date_fin_candidature' => $now->copy()->subDays(10), // Candidature period ended
            'date_debut_vote' => $now->copy()->subDays(5),    // Vote started
            'date_fin_vote' => $now->copy()->addDays(5),     // Vote ends in the future
            'created_by' => $admin->id,
        ]);

        // --- Election 3: VICE_RECTEUR, EN_COURS --- 
        Election::create([
            'titre' => 'Élection Vice-Recteur - En Cours',
            'description' => 'Élection pour le poste de Vice-Recteur.',
            'type_election' => 'VICE_RECTEUR',
            'statut' => 'EN_COURS',
            'departement_id' => null, // Typically not department-specific
            'date_debut_candidature' => $now->copy()->subDays(25),
            'date_fin_candidature' => $now->copy()->subDays(15), // Candidature period ended
            'date_debut_vote' => $now->copy()->subDays(7),    // Vote started
            'date_fin_vote' => $now->copy()->addDays(7),     // Vote ends in the future
            'created_by' => $admin->id,
        ]);

        $this->command->info('ElectionSeeder: Seeded 3 specific elections.');
    }
}
