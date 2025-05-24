<?php

namespace Tests\Unit;

use App\Models\Candidature;
use App\Models\Departement;
use App\Models\Election;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_candidature()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'type_personnel' => 'PATS',
        ]);

        $candidat = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $election = Election::create([
            'titre' => 'Élection Test',
            'description' => 'Description de l\'élection test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'OUVERTE',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->subDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $admin->id,
        ]);

        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test pour la candidature',
            'statut' => 'EN_ATTENTE',
            'date_soumission' => Carbon::now(),
        ]);

        $this->assertDatabaseHas('candidatures', [
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'statut' => 'EN_ATTENTE',
        ]);
    }

    /** @test */
    public function it_has_election_relationship()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'type_personnel' => 'PATS',
        ]);

        $candidat = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $election = Election::create([
            'titre' => 'Élection Test',
            'description' => 'Description de l\'élection test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'OUVERTE',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->subDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $admin->id,
        ]);

        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test pour la candidature',
            'statut' => 'EN_ATTENTE',
            'date_soumission' => Carbon::now(),
        ]);

        $this->assertInstanceOf(Election::class, $candidature->election);
        $this->assertEquals('Élection Test', $candidature->election->titre);
    }

    /** @test */
    public function it_has_candidat_relationship()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'type_personnel' => 'PATS',
        ]);

        $candidat = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $election = Election::create([
            'titre' => 'Élection Test',
            'description' => 'Description de l\'élection test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'OUVERTE',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->subDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $admin->id,
        ]);

        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test pour la candidature',
            'statut' => 'EN_ATTENTE',
            'date_soumission' => Carbon::now(),
        ]);

        $this->assertInstanceOf(User::class, $candidature->candidat);
        $this->assertEquals('John Doe', $candidature->candidat->nom_complet);
    }

    /** @test */
    public function it_can_filter_by_status_scopes()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'type_personnel' => 'PATS',
        ]);

        $candidat = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $election = Election::create([
            'titre' => 'Élection Test',
            'description' => 'Description de l\'élection test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'OUVERTE',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->subDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $admin->id,
        ]);

        // Créer deux candidats supplémentaires pour éviter les violations de contrainte d'unicité
        $candidat2 = User::factory()->create([
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);
        
        $candidat3 = User::factory()->create([
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        // Candidature en attente
        Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test pour la candidature en attente',
            'statut' => 'EN_ATTENTE',
            'date_soumission' => Carbon::now(),
        ]);

        // Candidature validée
        Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat2->id,
            'programme' => 'Programme de test pour la candidature validée',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now(),
            'date_validation' => Carbon::now(),
            'validee_par' => $admin->id,
        ]);

        // Candidature rejetée
        Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat3->id,
            'programme' => 'Programme de test pour la candidature rejetée',
            'statut' => 'REJETEE',
            'date_soumission' => Carbon::now(),
            'date_validation' => Carbon::now(),
            'validee_par' => $admin->id,
        ]);

        $this->assertEquals(1, Candidature::enAttente()->count());
        $this->assertEquals(1, Candidature::validees()->count());
        $this->assertEquals(1, Candidature::rejetees()->count());
    }
}
