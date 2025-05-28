<?php

namespace Tests\Unit;

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
    public function it_can_create_an_election()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $user = User::create([
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
            'statut' => 'BROUILLON',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->addDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('elections', [
            'titre' => 'Élection Test',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'BROUILLON',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_departement_relationship()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $user = User::create([
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
            'statut' => 'BROUILLON',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->addDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(Departement::class, $election->departement);
        $this->assertEquals('Informatique Test', $election->departement->nom);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_created_by_relationship()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $user = User::create([
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
            'statut' => 'BROUILLON',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->addDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $election->createdBy);
        $this->assertEquals('John Doe', $election->createdBy->nom_complet);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_by_status_scopes()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        // Élection en brouillon
        Election::create([
            'titre' => 'Élection Brouillon',
            'description' => 'Description de l\'élection brouillon',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'BROUILLON',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->addDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $user->id,
        ]);

        // Élection ouverte
        Election::create([
            'titre' => 'Élection Ouverte',
            'description' => 'Description de l\'élection ouverte',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'OUVERTE',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->addDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $user->id,
        ]);

        // Élection en cours
        Election::create([
            'titre' => 'Élection En Cours',
            'description' => 'Description de l\'élection en cours',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'EN_COURS',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->addDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $user->id,
        ]);

        // Élection fermée
        Election::create([
            'titre' => 'Élection Fermée',
            'description' => 'Description de l\'élection fermée',
            'type_election' => 'CHEF_DEPARTEMENT',
            'statut' => 'FERMEE',
            'departement_id' => $departement->id,
            'date_debut_candidature' => Carbon::now()->addDay(),
            'date_fin_candidature' => Carbon::now()->addDays(5),
            'date_debut_vote' => Carbon::now()->addDays(6),
            'date_fin_vote' => Carbon::now()->addDays(8),
            'created_by' => $user->id,
        ]);

        $this->assertEquals(1, Election::enCours()->count());
        $this->assertEquals(1, Election::ouvertes()->count());
        $this->assertEquals(1, Election::fermees()->count());
    }
}
