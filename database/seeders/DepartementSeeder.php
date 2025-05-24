<?php

namespace Database\Seeders;

use App\Models\Departement;
use Illuminate\Database\Seeder;

class DepartementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departements = [
            [
                'nom' => 'Informatique',
                'code' => 'INFO',
            ],
            [
                'nom' => 'Mathématiques',
                'code' => 'MATH',
            ],
            [
                'nom' => 'Physique',
                'code' => 'PHYS',
            ],
            [
                'nom' => 'Chimie',
                'code' => 'CHIM',
            ],
            [
                'nom' => 'Biologie',
                'code' => 'BIO',
            ],
            [
                'nom' => 'Géologie',
                'code' => 'GEO',
            ],
            [
                'nom' => 'Sciences Économiques',
                'code' => 'ECO',
            ],
            [
                'nom' => 'Droit',
                'code' => 'DROIT',
            ],
        ];

        foreach ($departements as $departement) {
            Departement::create($departement);
        }
    }
}
