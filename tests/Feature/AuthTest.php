<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_register()
    {
        $departement = Departement::create([
            'nom' => 'Informatique Test',
            'code' => 'INFO-TEST',
        ]);

        $response = $this->postJson('/api/register', [
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'telephone' => '1234567890',
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => [
                    'id', 'nom', 'prenom', 'email', 'type_personnel', 'departement_id', 'created_at', 'updated_at'
                ],
                'access_token',
                'token_type',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@test.com',
            'nom' => 'Doe',
            'prenom' => 'John',
            'type_personnel' => 'PER',
            'departement_id' => $departement->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_login()
    {
        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john.doe@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id', 'nom', 'prenom', 'email', 'type_personnel', 'created_at', 'updated_at'
                ],
                'access_token',
                'token_type',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = User::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'email' => 'john.doe@test.com',
            'password' => bcrypt('password123'),
            'type_personnel' => 'PER',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john.doe@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Identifiants invalides',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_logout()
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
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Déconnexion réussie',
            ]);

        // Vérifier que le token a été supprimé
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_get_their_profile()
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
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id', 'nom', 'prenom', 'email', 'type_personnel', 'created_at', 'updated_at'
                ],
            ]);
    }
}
