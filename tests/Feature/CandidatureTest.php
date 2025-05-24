<?php

namespace Tests\Feature;

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
    public function per_user_can_submit_candidature()
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

        $token = $candidat->createToken('test-token')->plainTextToken;

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

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/candidatures', [
                'election_id' => $election->id,
                'programme' => 'Programme de test pour la candidature',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'election_id' => $election->id,
                'candidat_id' => $candidat->id,
                'programme' => 'Programme de test pour la candidature',
                'statut' => 'EN_ATTENTE',
            ]);

        $this->assertDatabaseHas('candidatures', [
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test pour la candidature',
            'statut' => 'EN_ATTENTE',
        ]);
    }

    /** @test */
    public function pats_user_cannot_submit_candidature()
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

        $token = $admin->createToken('test-token')->plainTextToken;

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

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/candidatures', [
                'election_id' => $election->id,
                'programme' => 'Programme de test pour la candidature',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Seuls les personnels enseignants-chercheurs (PER) peuvent soumettre une candidature',
            ]);
    }

    /** @test */
    public function cannot_submit_candidature_to_closed_election()
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

        $token = $candidat->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Test',
            'description' => 'Description de l\'élection test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'BROUILLON',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->subDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $admin->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/candidatures', [
                'election_id' => $election->id,
                'programme' => 'Programme de test pour la candidature',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cette élection n\'est pas ouverte aux candidatures',
            ]);
    }

    /** @test */
    public function admin_can_validate_candidature()
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

        $adminToken = $admin->createToken('test-token')->plainTextToken;

        $candidat = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
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

        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->postJson('/api/candidatures/' . $candidature->id . '/valider', [
                'statut' => 'VALIDEE',
                'commentaire_admin' => 'Candidature validée par l\'administrateur',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Candidature validée avec succès',
                'candidature' => [
                    'id' => $candidature->id,
                    'statut' => 'VALIDEE',
                    'commentaire_admin' => 'Candidature validée par l\'administrateur',
                ],
            ]);

        $this->assertDatabaseHas('candidatures', [
            'id' => $candidature->id,
            'statut' => 'VALIDEE',
            'commentaire_admin' => 'Candidature validée par l\'administrateur',
            'validee_par' => $admin->id,
        ]);
    }

    /** @test */
    public function admin_can_reject_candidature()
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

        $adminToken = $admin->createToken('test-token')->plainTextToken;

        $candidat = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
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

        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->postJson('/api/candidatures/' . $candidature->id . '/valider', [
                'statut' => 'REJETEE',
                'commentaire_admin' => 'Candidature rejetée par l\'administrateur',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Candidature rejetée avec succès',
                'candidature' => [
                    'id' => $candidature->id,
                    'statut' => 'REJETEE',
                    'commentaire_admin' => 'Candidature rejetée par l\'administrateur',
                ],
            ]);

        $this->assertDatabaseHas('candidatures', [
            'id' => $candidature->id,
            'statut' => 'REJETEE',
            'commentaire_admin' => 'Candidature rejetée par l\'administrateur',
            'validee_par' => $admin->id,
        ]);
    }
}
