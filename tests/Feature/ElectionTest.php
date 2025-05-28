<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Election;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_get_all_elections()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        Election::create([
            'titre' => 'Élection Test 1',
            'description' => 'Description de l\'élection test 1',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'BROUILLON',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->addDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $user->id,
        ]);

        Election::create([
            'titre' => 'Élection Test 2',
            'description' => 'Description de l\'élection test 2',
            'type_election' => 'DIRECTEUR_UFR',
            'statut' => 'BROUILLON',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->addDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/elections');
        
            $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'titre',
                        'description',
                        'type_election',
                        'statut',
                        'departement_id',
                        'date_debut_candidature',
                        'date_fin_candidature',
                        'date_debut_vote',
                        'date_fin_vote',
                        'created_by' => [
                            'id',
                            'nom',
                            'email',
                            'email_verified_at',
                            'created_at',
                            'updated_at',
                            'prenom',
                            'telephone',
                            'type_personnel',
                            'departement_id'
                        ],
                        'created_at',
                        'updated_at',
                        'departement' => [
                            'id',
                            'nom',
                            'code',
                            'created_at',
                            'updated_at'
                        ],
                        'candidatures'
                    ]
                ]
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_create_election()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/elections', [
                'titre' => 'Élection Test',
                'description' => 'Description de l\'élection test',
                'type_election' => 'CHEF_DEPARTEMENT',
                'departement_id' => $departement->id,
                'date_debut_candidature' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
                'date_fin_candidature' => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
                'date_debut_vote' => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
                'date_fin_vote' => Carbon::now()->addDays(8)->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'titre' => 'Élection Test',
                'type_election' => 'CHEF_DEPARTEMENT',
                'statut' => 'BROUILLON',
                'departement_id' => $departement->id,
            ]);

        $this->assertDatabaseHas('elections', [
            'titre' => 'Élection Test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'BROUILLON',
            'departement_id' => $departement->id,
            'created_by' => $user->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_get_single_election()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Test',
            'description' => 'Description de l\'élection test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'BROUILLON',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->addDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/elections/' . $election->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $election->id,
                'titre' => 'Élection Test',
                'type_election' => 'CHEF_DEPARTEMENT',
                'statut' => 'BROUILLON',
                'departement_id' => $departement->id,
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_update_election_in_brouillon()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Test',
            'description' => 'Description de l\'élection test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'BROUILLON',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->addDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/elections/' . $election->id, [
                'titre' => 'Élection Test Modifiée',
                'description' => 'Description modifiée',
                'type_election' => 'CHEF_DEPARTEMENT',
                'departement_id' => $departement->id,
                'date_debut_candidature' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
                'date_fin_candidature' => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
                'date_debut_vote' => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
                'date_fin_vote' => Carbon::now()->addDays(8)->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $election->id,
                'titre' => 'Élection Test Modifiée',
                'description' => 'Description modifiée',
            ]);

        $this->assertDatabaseHas('elections', [
            'id' => $election->id,
            'titre' => 'Élection Test Modifiée',
            'description' => 'Description modifiée',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_update_election_not_in_brouillon()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Test',
            'description' => 'Description de l\'élection test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'OUVERTE',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->addDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/elections/' . $election->id, [
                'titre' => 'Élection Test Modifiée',
                'description' => 'Description modifiée',
                'type_election' => 'CHEF_DEPARTEMENT',
                'departement_id' => $departement->id,
                'date_debut_candidature' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
                'date_fin_candidature' => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
                'date_debut_vote' => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
                'date_fin_vote' => Carbon::now()->addDays(8)->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Seules les élections en brouillon peuvent être modifiées',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_open_election()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $election = Election::create([
            'titre' => 'Élection Test',
            'description' => 'Description de l\'élection test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'BROUILLON',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->addDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/elections/' . $election->id . '/ouvrir');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Élection ouverte avec succès',
                'election' => [
                    'id' => $election->id,
                    'statut' => 'OUVERTE',
                ],
            ]);

        $this->assertDatabaseHas('elections', [
            'id' => $election->id,
            'statut' => 'OUVERTE',
        ]);
    }
}
