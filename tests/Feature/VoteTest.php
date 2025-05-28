<?php

namespace Tests\Feature;

use App\Models\Candidature;
use App\Models\Departement;
use App\Models\Election;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoteTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
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
            'vote_blanc' => false,
        ]);

        // Vérifier que l'utilisateur a bien voté
        $this->assertDatabaseHas('votes', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
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

        // L'électeur est un PER du département, donc autorisé à voter pour une élection de type CHEF_DEPARTEMENT

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

        // Vérifier que l'utilisateur a bien voté
        $this->assertDatabaseHas('votes', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
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

        // L'électeur est un PER du département, donc autorisé à voter pour une élection de type CHEF_DEPARTEMENT
        
        // Créer un vote pour simuler que l'électeur a déjà voté
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/votes', [
                'election_id' => $election->id,
                'candidature_id' => $candidature->id,
                'vote_blanc' => false,
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

    #[\PHPUnit\Framework\Attributes\Test]
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

        // Créer un utilisateur d'un autre département
        $autreDepartement = Departement::create([
            'nom' => 'Autre Département',
            'code' => 'AUTRE-DEP',
        ]);
        
        $electeur->departement_id = $autreDepartement->id;
        $electeur->save();
        
        // L'électeur n'est pas du même département, donc non autorisé à voter pour une élection de type CHEF_DEPARTEMENT

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/votes', [
                'election_id' => $election->id,
                'candidature_id' => $candidature->id,
                'vote_blanc' => false,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Seuls les PER de ce département peuvent voter pour l\'élection du Chef de Département.',
            ]);
    }
}
