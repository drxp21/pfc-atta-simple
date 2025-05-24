<?php

namespace Tests\Unit;

use App\Models\Departement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_departement()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $this->assertDatabaseHas('departements', [
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);
    }

    /** @test */
    public function it_has_users_relationship()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password'),
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $this->assertInstanceOf(User::class, $departement->users->first());
        $this->assertEquals(1, $departement->users->count());
    }

    /** @test */
    public function it_enforces_unique_nom_constraint()
    {
        Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST2',
        ]);
    }

    /** @test */
    public function it_enforces_unique_code_constraint()
    {
        Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Departement::create([
            'nom' => 'Informatique Test 2',
            'code' => 'INFO-TEST',
        ]);
    }
}
