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
            'nom' => 'Ndiaye',
            'prenom' => 'Aminata',
            'email' => 'admin@univ-thies.sn',
            'password' => Hash::make('password'),
            'telephone' => '771234567',
            'type_personnel' => 'ADMIN',
            'departement_id' => null,
        ]);

        // Liste de noms et prénoms sénégalais réalistes
        $noms = ['Diop', 'Ndiaye', 'Gueye', 'Sy', 'Diouf', 'Fall', 'Sow', 'Kane', 'Ba', 'Niang'];
        $prenomsMasculins = ['Mamadou', 'Ibrahima', 'Abdoulaye', 'Moussa', 'Cheikh', 'Modou', 'Pape', 'Moustapha', 'Ousmane', 'Samba'];
        $prenomsFeminins = ['Aminata', 'Aissatou', 'Fatou', 'Mariama', 'Rokhaya', 'Awa', 'Khadidiatou', 'Sokhna', 'Ndèye', 'Mame'];

        // Créer 15 PER (Personnel Enseignant-Chercheur)
        for ($i = 1; $i <= 15; $i++) {
            $estFeminin = rand(0, 1);
            $prenom = $estFeminin
                ? $prenomsFeminins[array_rand($prenomsFeminins)]
                : $prenomsMasculins[array_rand($prenomsMasculins)];

            $nom = $noms[array_rand($noms)];
            $email = strtolower('per' . $i . '@univ-thies.sn');

            User::create([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'password' => Hash::make('password'),
                'telephone' => '77' . rand(1000000, 9999999),
                'type_personnel' => 'PER',
                'departement_id' => 1,
            ]);
        }

        // Créer 10 PATS (Personnel Administratif, Technique et de Service)
        $postesPATS = [
            'Secrétaire',
            'Comptable',
            'Responsable RH',
            'Technicien informatique',
            'Agent administratif',
            'Bibliothécaire',
            'Assistant administratif',
            'Agent de sécurité'
        ];

        for ($i = 1; $i <= 10; $i++) {
            $estFeminin = rand(0, 1);
            $prenom = $estFeminin
                ? $prenomsFeminins[array_rand($prenomsFeminins)]
                : $prenomsMasculins[array_rand($prenomsMasculins)];

            $nom = $noms[array_rand($noms)];
            $email = strtolower('pats'. $i) . '@univ-thies.sn';

            User::create([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'password' => Hash::make('password'),
                'telephone' => '76' . rand(1000000, 9999999),
                'type_personnel' => 'PATS',
                'departement_id' => 1,
            ]);
        }
    }
}
