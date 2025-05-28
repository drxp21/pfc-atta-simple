<?php

namespace Tests\Unit;

use App\Models\Departement;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use App\Models\Candidature;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoteRulesTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function per_can_vote_for_chef_departement_of_their_department()
    {
        // Créer un département
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        // Créer un administrateur
        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
        ]);

        // Créer un candidat PER du département
        $candidat = User::create([
            'nom' => 'Candidat',
            'prenom' => 'Test',
            'email' => 'candidat@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        // Créer un électeur PER du même département
        $electeur = User::create([
            'nom' => 'Electeur',
            'prenom' => 'PER',
            'email' => 'electeur.per@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        // Créer une élection de type CHEF_DEPARTEMENT
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

        // Créer une candidature validée
        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // Simuler l'authentification de l'électeur
        $this->actingAs($electeur);

        // Créer un vote
        $vote = Vote::create([
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'candidature_id' => $candidature->id,
            'vote_blanc' => false,
            'date_vote' => Carbon::now(),
        ]);

        // Vérifier que le vote a été créé
        $this->assertDatabaseHas('votes', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'candidature_id' => $candidature->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pats_cannot_vote_for_chef_departement()
    {
        // Créer un département
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        // Créer un administrateur
        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
        ]);

        // Créer un candidat PER du département
        $candidat = User::create([
            'nom' => 'Candidat',
            'prenom' => 'Test',
            'email' => 'candidat@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        // Créer un électeur PATS du même département
        $electeur = User::create([
            'nom' => 'Electeur',
            'prenom' => 'PATS',
            'email' => 'electeur.pats@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
            'departement_id' => $departement->id,
        ]);

        // Créer une élection de type CHEF_DEPARTEMENT
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

        // Créer une candidature validée
        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // Simuler l'authentification de l'électeur
        $this->actingAs($electeur);

        // Tenter de créer un vote via l'API
        $response = $this->postJson('/api/votes', [
            'election_id' => $election->id,
            'candidature_id' => $candidature->id,
            'vote_blanc' => false,
        ]);

        // Vérifier que la requête a échoué avec un code 403 (Forbidden)
        $response->assertStatus(403);

        // Vérifier qu'aucun vote n'a été créé
        $this->assertDatabaseMissing('votes', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function per_from_different_department_cannot_vote_for_chef_departement()
    {
        // Créer deux départements
        $departement1 = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $departement2 = Departement::create([
            'nom' => 'Mathématiques Test',
            'code' => 'MATH-TEST',
        ]);

        // Créer un administrateur
        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
        ]);

        // Créer un candidat PER du département 1
        $candidat = User::create([
            'nom' => 'Candidat',
            'prenom' => 'Test',
            'email' => 'candidat@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement1->id,
        ]);

        // Créer un électeur PER du département 2
        $electeur = User::create([
            'nom' => 'Electeur',
            'prenom' => 'PER',
            'email' => 'electeur.per@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement2->id,
        ]);

        // Créer une élection de type CHEF_DEPARTEMENT pour le département 1
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

        // Créer une candidature validée
        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // Simuler l'authentification de l'électeur
        $this->actingAs($electeur);

        // Tenter de créer un vote via l'API
        $response = $this->postJson('/api/votes', [
            'election_id' => $election->id,
            'candidature_id' => $candidature->id,
            'vote_blanc' => false,
        ]);

        // Vérifier que la requête a échoué avec un code 403 (Forbidden)
        $response->assertStatus(403);

        // Vérifier qu'aucun vote n'a été créé
        $this->assertDatabaseMissing('votes', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function per_can_vote_for_directeur_ufr_of_their_ufr()
    {
        // Créer un département (représentant une UFR dans ce contexte)
        $departement = Departement::create([
            'nom' => 'UFR Sciences Test',
            'code' => 'UFR-SCI-TEST',
        ]);

        // Créer un administrateur
        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
        ]);

        // Créer un candidat PER de l'UFR
        $candidat = User::create([
            'nom' => 'Candidat',
            'prenom' => 'Test',
            'email' => 'candidat@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        // Créer un électeur PER de la même UFR
        $electeur = User::create([
            'nom' => 'Electeur',
            'prenom' => 'PER',
            'email' => 'electeur.per@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        // Créer une élection de type DIRECTEUR_UFR
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

        // Créer une candidature validée
        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // Simuler l'authentification de l'électeur
        $this->actingAs($electeur);

        // Créer un vote
        $vote = Vote::create([
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'candidature_id' => $candidature->id,
            'vote_blanc' => false,
            'date_vote' => Carbon::now(),
        ]);

        // Vérifier que le vote a été créé
        $this->assertDatabaseHas('votes', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'candidature_id' => $candidature->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pats_cannot_vote_for_directeur_ufr()
    {
        // Créer un département (représentant une UFR dans ce contexte)
        $departement = Departement::create([
            'nom' => 'UFR Sciences Test',
            'code' => 'UFR-SCI-TEST',
        ]);

        // Créer un administrateur
        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
        ]);

        // Créer un candidat PER de l'UFR
        $candidat = User::create([
            'nom' => 'Candidat',
            'prenom' => 'Test',
            'email' => 'candidat@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        // Créer un électeur PATS de la même UFR
        $electeur = User::create([
            'nom' => 'Electeur',
            'prenom' => 'PATS',
            'email' => 'electeur.pats@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
            'departement_id' => $departement->id,
        ]);

        // Créer une élection de type DIRECTEUR_UFR
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

        // Créer une candidature validée
        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // Simuler l'authentification de l'électeur
        $this->actingAs($electeur);

        // Tenter de créer un vote via l'API
        $response = $this->postJson('/api/votes', [
            'election_id' => $election->id,
            'candidature_id' => $candidature->id,
            'vote_blanc' => false,
        ]);

        // Vérifier que la requête a échoué avec un code 403 (Forbidden)
        $response->assertStatus(403);

        // Vérifier qu'aucun vote n'a été créé
        $this->assertDatabaseMissing('votes', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function per_can_vote_for_vice_recteur()
    {
        // Créer un département
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        // Créer un administrateur
        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
        ]);

        // Créer un candidat PER
        $candidat = User::create([
            'nom' => 'Candidat',
            'prenom' => 'Test',
            'email' => 'candidat@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        // Créer un électeur PER
        $electeur = User::create([
            'nom' => 'Electeur',
            'prenom' => 'PER',
            'email' => 'electeur.per@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        // Créer une élection de type VICE_RECTEUR
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

        // Créer une candidature validée
        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // Simuler l'authentification de l'électeur
        $this->actingAs($electeur);

        // Créer un vote
        $vote = Vote::create([
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'candidature_id' => $candidature->id,
            'vote_blanc' => false,
            'date_vote' => Carbon::now(),
        ]);

        // Vérifier que le vote a été créé
        $this->assertDatabaseHas('votes', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'candidature_id' => $candidature->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pats_can_vote_for_vice_recteur()
    {
        // Créer un département
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        // Créer un administrateur
        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
        ]);

        // Créer un candidat PER
        $candidat = User::create([
            'nom' => 'Candidat',
            'prenom' => 'Test',
            'email' => 'candidat@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        // Créer un électeur PATS
        $electeur = User::create([
            'nom' => 'Electeur',
            'prenom' => 'PATS',
            'email' => 'electeur.pats@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PATS',
            'departement_id' => $departement->id,
        ]);

        // Créer une élection de type VICE_RECTEUR
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

        // Créer une candidature validée
        $candidature = Candidature::create([
            'election_id' => $election->id,
            'candidat_id' => $candidat->id,
            'programme' => 'Programme de test',
            'statut' => 'VALIDEE',
            'date_soumission' => Carbon::now()->subDays(8),
            'date_validation' => Carbon::now()->subDays(7),
            'validee_par' => $admin->id,
        ]);

        // Simuler l'authentification de l'électeur
        $this->actingAs($electeur);

        // Créer un vote
        $vote = Vote::create([
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'candidature_id' => $candidature->id,
            'vote_blanc' => false,
            'date_vote' => Carbon::now(),
        ]);

        // Vérifier que le vote a été créé
        $this->assertDatabaseHas('votes', [
            'election_id' => $election->id,
            'electeur_id' => $electeur->id,
            'candidature_id' => $candidature->id,
        ]);
    }
}