<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartementTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_get_all_departements()
    {
        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        Departement::create([
            'nom' => 'Mathématiques Test',
            'code' => 'MATH-TEST',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/departements');

            
        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'nom',
                    'code', 
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_create_departement()
    {
        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/departements', [
                'nom' => 'Informatique Test',
                'code' => 'INFO-TEST',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'nom' => 'Informatique Test',
                'code' => 'INFO-TEST',
            ]);

        $this->assertDatabaseHas('departements', [
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_get_single_departement()
    {
        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/departements/' . $departement->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $departement->id,
                'nom' => 'Informatique Test',
                'code' => 'INFO-TEST',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_update_departement()
    {
        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/departements/' . $departement->id, [
                'nom' => 'Informatique Modifié',
                'code' => 'INFO-MOD',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $departement->id,
                'nom' => 'Informatique Modifié',
                'code' => 'INFO-MOD',
            ]);

        $this->assertDatabaseHas('departements', [
            'id' => $departement->id,
            'nom' => 'Informatique Modifié',
            'code' => 'INFO-MOD',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_delete_departement()
    {
        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/departements/' . $departement->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Département supprimé avec succès',
            ]);

        $this->assertDatabaseMissing('departements', [
            'id' => $departement->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_delete_departement_with_users()
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
            ->deleteJson('/api/departements/' . $departement->id);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Ce département ne peut pas être supprimé car il est utilisé par des utilisateurs ou des élections.',
            ]);

        $this->assertDatabaseHas('departements', [
            'id' => $departement->id,
        ]);
    }
}
