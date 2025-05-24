<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'telephone',
        'type_personnel',
        'departement_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    /**
     * Relation avec le département
     */
    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }
    
    /**
     * Relation avec les candidatures
     */
    public function candidatures()
    {
        return $this->hasMany(Candidature::class, 'candidat_id');
    }
    
    /**
     * Relation avec les votes
     */
    public function votes()
    {
        return $this->hasMany(Vote::class, 'electeur_id');
    }
    
    /**
     * Relation avec les élections créées
     */
    public function electionsCreees()
    {
        return $this->hasMany(Election::class, 'created_by');
    }
    
    /**
     * Relation avec les élections autorisées
     */
    public function electionsAutorisees()
    {
        return $this->belongsToMany(Election::class, 'electeurs_autorises', 'electeur_id', 'election_id')
                    ->withPivot('a_vote', 'date_autorisation')
                    ->withTimestamps();
    }
    
    /**
     * Accesseur pour obtenir le nom complet
     */
    public function getNomCompletAttribute()
    {
        return $this->prenom . ' ' . $this->nom;
    }
    
    /**
     * Accesseur pour vérifier si l'utilisateur est un PER
     */
    public function getIsPERAttribute()
    {
        return $this->type_personnel === 'PER';
    }
}
