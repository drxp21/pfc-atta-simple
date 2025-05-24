<?php

namespace Tests\Feature;

use App\Models\Candidature;
use App\Models\Departement;
use App\Models\Election;
use App\Models\ElecteurAutorise;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoteTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authorized_user_can_vote()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
        ]);

        $candidat = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $electeur = User::create([
            'nom' => 'Smith',
            'prenom' => 'Jane',
            'email' => 'jane.smith@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $token = $electeur->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Test',
            'description' => 'Description de l\'élection test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'EN_COURS',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->subDays(10),
            'date_fin_candidature' => Carbon::now()->subDays(5),
            'date_debut_vote' => Carbon::now()->subDay(),
            'date_fin_vote' => Carbon::now()->addDays(5),
            'created_by' => $admin->id,
        ]);

        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test pour la candidature',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        ElecteurAutorise::create([
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'a_vote' => false,
            'date_autorisation' => Carbon::now()->subDays(6),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/votes', [
                'election_id' => $election->id,
                'candidature_id' => $candidature->id,
                'vote_blanc' => false,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Vote enregistré avec succès',
                'vote' => [
                    'election_id' => $election->id,
                    'electeur_id' => $electeur->id,
                    'candidature_id' => $candidature->id,
                    'vote_blanc' => false,
                ],
            ]);

        $this->assertDatabaseHas('votes', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'candidature_id' => $candidature->id,
            'vote_blanc' => false,
        ]);

        $this->assertDatabaseHas('electeurs_autorises', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'a_vote' => true,
        ]);
    }

    /** @test */
    public function authorized_user_can_vote_blanc()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
        ]);

        $electeur = User::create([
            'nom' => 'Smith',
            'prenom' => 'Jane',
            'email' => 'jane.smith@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $token = $electeur->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Test',
            'description' => 'Description de l\'élection test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'EN_COURS',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->subDays(10),
            'date_fin_candidature' => Carbon::now()->subDays(5),
            'date_debut_vote' => Carbon::now()->subDay(),
            'date_fin_vote' => Carbon::now()->addDays(5),
            'created_by' => $admin->id,
        ]);

        ElecteurAutorise::create([
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'a_vote' => false,
            'date_autorisation' => Carbon::now()->subDays(6),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/votes', [
                'election_id' => $election->id,
                'candidature_id' => null,
                'vote_blanc' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Vote enregistré avec succès',
                'vote' => [
                    'election_id' => $election->id,
                    'electeur_id' => $electeur->id,
                    'candidature_id' => null,
                    'vote_blanc' => true,
                ],
            ]);

        $this->assertDatabaseHas('votes', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'candidature_id' => null,
            'vote_blanc' => true,
        ]);

        $this->assertDatabaseHas('electeurs_autorises', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'a_vote' => true,
        ]);
    }

    /** @test */
    public function user_cannot_vote_twice()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
        ]);

        $candidat = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $electeur = User::create([
            'nom' => 'Smith',
            'prenom' => 'Jane',
            'email' => 'jane.smith@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $token = $electeur->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Test',
            'description' => 'Description de l\'élection test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'EN_COURS',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->subDays(10),
            'date_fin_candidature' => Carbon::now()->subDays(5),
            'date_debut_vote' => Carbon::now()->subDay(),
            'date_fin_vote' => Carbon::now()->addDays(5),
            'created_by' => $admin->id,
        ]);

        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test pour la candidature',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        ElecteurAutorise::create([
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'a_vote' => true, // Déjà voté
            'date_autorisation' => Carbon::now()->subDays(6),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/votes', [
                'election_id' => $election->id,
                'candidature_id' => $candidature->id,
                'vote_blanc' => false,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Vous avez déjà voté pour cette élection',
            ]);
    }

    /** @test */
    public function unauthorized_user_cannot_vote()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
        ]);

        $candidat = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $electeur = User::create([
            'nom' => 'Smith',
            'prenom' => 'Jane',
            'email' => 'jane.smith@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $token = $electeur->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Test',
            'description' => 'Description de l\'élection test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'EN_COURS',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->subDays(10),
            'date_fin_candidature' => Carbon::now()->subDays(5),
            'date_debut_vote' => Carbon::now()->subDay(),
            'date_fin_vote' => Carbon::now()->addDays(5),
            'created_by' => $admin->id,
        ]);

        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test pour la candidature',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // Pas d'autorisation pour cet électeur

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/votes', [
                'election_id' => $election->id,
                'candidature_id' => $candidature->id,
                'vote_blanc' => false,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Vous n\'êtes pas autorisé à voter pour cette élection',
            ]);
    }
}
