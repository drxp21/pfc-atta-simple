<?php

namespace Tests\Unit;

use App\Models\Departement;
use App\Models\User;
use App\Models\Election;
use App\Models\Candidature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_a_user()
    {
        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password'),
            'telephone' => '1234567890',
            'type_personnel' => 'PER',
        ]);

        $this->assertDatabaseHas('users', [
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'type_personnel' => 'PER',
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

        $this->assertInstanceOf(Departement::class, $user->departement);
        $this->assertEquals('Informatique Test', $user->departement->nom);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_nom_complet_accessor()
    {
        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password'),
            'type_personnel' => 'PER',
        ]);

        $this->assertEquals('John Doe', $user->nom_complet);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_is_per_accessor()
    {
        $perUser = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password'),
            'type_personnel' => 'PER',
        ]);

        $patsUser = User::create([
            'nom' => 'Smith',
            'prenom' => 'Jane',
            'email' => 'jane.smith@test.com',
            'password' => bcrypt('password'),
            'type_personnel' => 'PATS',
        ]);

        $this->assertTrue($perUser->is_PER);
        $this->assertFalse($patsUser->is_PER);
    }
}
