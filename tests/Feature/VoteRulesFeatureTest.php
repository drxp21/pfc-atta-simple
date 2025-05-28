<?php

namespace Tests\Feature;

use App\Models\Candidature;
use App\Models\Departement;
use App\Models\Election;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoteRulesFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function per_can_vote_for_chef_departement_of_their_department()
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
            'nom' => 'Candidat',
            'prenom' => 'Test',
            'email' => 'candidat@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $electeur = User::create([
            'nom' => 'Electeur',
            'prenom' => 'PER',
            'email' => 'electeur.per@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $token = $electeur->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Chef Département',
            'description' => 'Description de l\'élection',
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
            'programme' => 'Programme de test',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // L'électeur est un PER du département, donc autorisé à voter pour une élection de type CHEF_DEPARTEMENT
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
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pats_cannot_vote_for_chef_departement()
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
            'nom' => 'Candidat',
            'prenom' => 'Test',
            'email' => 'candidat@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $electeur = User::create([
            'nom' => 'Electeur',
            'prenom' => 'PATS',
            'email' => 'electeur.pats@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
            'departement_id' => $departement->id,
        ]);

        $token = $electeur->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Chef Département',
            'description' => 'Description de l\'élection',
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
            'programme' => 'Programme de test',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // L'électeur est un PATS, donc non autorisé à voter pour une élection de type CHEF_DEPARTEMENT
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/votes', [
                'election_id' => $election->id,
                'candidature_id' => $candidature->id,
                'vote_blanc' => false,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('votes', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function per_from_different_department_cannot_vote_for_chef_departement()
    {
        $departement1 = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $departement2 = Departement::create([
            'nom' => 'Mathématiques Test',
            'code' => 'MATH-TEST',
        ]);

        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
        ]);

        $candidat = User::create([
            'nom' => 'Candidat',
            'prenom' => 'Test',
            'email' => 'candidat@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement1->id,
        ]);

        $electeur = User::create([
            'nom' => 'Electeur',
            'prenom' => 'PER',
            'email' => 'electeur.per@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement2->id,
        ]);

        $token = $electeur->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Chef Département',
            'description' => 'Description de l\'élection',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'EN_COURS',
            'departement_id' => $departement1->id,
            'date_debut_candidature' => Carbon::now()->subDays(10),
            'date_fin_candidature' => Carbon::now()->subDays(5),
            'date_debut_vote' => Carbon::now()->subDay(),
            'date_fin_vote' => Carbon::now()->addDays(5),
            'created_by' => $admin->id,
        ]);

        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // L'électeur est un PER mais d'un autre département, donc non autorisé à voter pour cette élection
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/votes', [
                'election_id' => $election->id,
                'candidature_id' => $candidature->id,
                'vote_blanc' => false,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('votes', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function per_can_vote_for_directeur_ufr_of_their_ufr()
    {
        $departement = Departement::create([
            'nom' => 'UFR Sciences Test',
            'code' => 'UFR-SCI-TEST',
        ]);

        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
        ]);

        $candidat = User::create([
            'nom' => 'Candidat',
            'prenom' => 'Test',
            'email' => 'candidat@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $electeur = User::create([
            'nom' => 'Electeur',
            'prenom' => 'PER',
            'email' => 'electeur.per@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $token = $electeur->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Directeur UFR',
            'description' => 'Description de l\'élection',
            'type_election' => 'DIRECTEUR_UFR',
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
            'programme' => 'Programme de test',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // L'électeur est un PER de l'UFR, donc autorisé à voter pour une élection de type DIRECTEUR_UFR
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
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pats_cannot_vote_for_directeur_ufr()
    {
        $departement = Departement::create([
            'nom' => 'UFR Sciences Test',
            'code' => 'UFR-SCI-TEST',
        ]);

        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
        ]);

        $candidat = User::create([
            'nom' => 'Candidat',
            'prenom' => 'Test',
            'email' => 'candidat@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $electeur = User::create([
            'nom' => 'Electeur',
            'prenom' => 'PATS',
            'email' => 'electeur.pats@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
            'departement_id' => $departement->id,
        ]);

        $token = $electeur->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Directeur UFR',
            'description' => 'Description de l\'élection',
            'type_election' => 'DIRECTEUR_UFR',
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
            'programme' => 'Programme de test',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // L'électeur est un PATS, donc non autorisé à voter pour une élection de type DIRECTEUR_UFR
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/votes', [
                'election_id' => $election->id,
                'candidature_id' => $candidature->id,
                'vote_blanc' => false,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('votes', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function per_can_vote_for_vice_recteur()
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
            'nom' => 'Candidat',
            'prenom' => 'Test',
            'email' => 'candidat@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $electeur = User::create([
            'nom' => 'Electeur',
            'prenom' => 'PER',
            'email' => 'electeur.per@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $token = $electeur->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Vice-Recteur',
            'description' => 'Description de l\'élection',
            'type_election' => 'VICE_RECTEUR',
            'statut' => 'EN_COURS',
            'departement_id' => null, // Pas de département spécifique pour cette élection
            'date_debut_candidature' => Carbon::now()->subDays(10),
            'date_fin_candidature' => Carbon::now()->subDays(5),
            'date_debut_vote' => Carbon::now()->subDay(),
            'date_fin_vote' => Carbon::now()->addDays(5),
            'created_by' => $admin->id,
        ]);

        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // L'électeur est un PER, donc autorisé à voter pour une élection de type VICE_RECTEUR
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
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pats_can_vote_for_vice_recteur()
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
            'nom' => 'Candidat',
            'prenom' => 'Test',
            'email' => 'candidat@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $electeur = User::create([
            'nom' => 'Electeur',
            'prenom' => 'PATS',
            'email' => 'electeur.pats@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
            'departement_id' => $departement->id,
        ]);

        $token = $electeur->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Vice-Recteur',
            'description' => 'Description de l\'élection',
            'type_election' => 'VICE_RECTEUR',
            'statut' => 'EN_COURS',
            'departement_id' => null, // Pas de département spécifique pour cette élection
            'date_debut_candidature' => Carbon::now()->subDays(10),
            'date_fin_candidature' => Carbon::now()->subDays(5),
            'date_debut_vote' => Carbon::now()->subDay(),
            'date_fin_vote' => Carbon::now()->addDays(5),
            'created_by' => $admin->id,
        ]);

        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // L'électeur est un PATS, donc autorisé à voter pour une élection de type VICE_RECTEUR
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
        ]);
    }
}