<?php

namespace Database\Seeders;

use App\Models\Departement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les départements
        $departements = Departement::all();
        
        // Créer un administrateur
        User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@universite.edu',
            'password' => Hash::make('password'),
            'telephone' => '123456789',
            'type_personnel' => 'ADMIN',
            'departement_id' => null,
        ]);
        
        // Créer 10 PER (Personnel Enseignant-Chercheur)
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'nom' => 'Mansour',
                'prenom' => 'Diouf',
                'email' => 'mansour.diouf@univ-thies.sn',
                'password' => Hash::make('password'),
                'telephone' => '1234567' . $i,
                'type_personnel' => 'PER',
                'departement_id' => $departements->random()->id,
            ]);
        }
        
        // Créer 5 PATS (Personnel Administratif, Technique et de Service)
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'nom' => 'Personnel' . $i,
                'prenom' => 'Admin' . $i,
                'email' => 'pats' . $i . '@universite.edu',
                'password' => Hash::make('password'),
                'telephone' => '9876543' . $i,
                'type_personnel' => 'PATS',
                'departement_id' => $departements->random()->id,
            ]);
        }
    }
}
