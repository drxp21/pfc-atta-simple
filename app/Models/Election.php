<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Election extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'titre',
        'description',
        'type_election',
        'statut',
        'departement_id',
        'date_debut_candidature',
        'date_fin_candidature',
        'date_debut_vote',
        'date_fin_vote',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_debut_candidature' => 'datetime',
        'date_fin_candidature' => 'datetime',
        'date_debut_vote' => 'datetime',
        'date_fin_vote' => 'datetime',
    ];

    /**
     * Relation avec le département
     */
    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    /**
     * Relation avec l'utilisateur qui a créé l'élection
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec les candidatures
     */
    public function candidatures()
    {
        return $this->hasMany(Candidature::class);
    }

    /**
     * Relation avec les votes
     */
    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * Relation avec les électeurs qui ont voté
     */
    public function electeurs()
    {
        return $this->belongsToMany(User::class, 'votes', 'election_id', 'electeur_id')
                    ->withPivot('vote_blanc', 'date_vote')
                    ->withTimestamps();
    }

    /**
     * Relation avec les résultats
     */
    public function resultats()
    {
        return $this->hasMany(Resultat::class);
    }

    /**
     * Relation avec les procès-verbaux
     */
    public function procesVerbaux()
    {
        return $this->hasMany(ProcesVerbal::class);
    }

    /**
     * Scope pour les élections en cours
     */
    public function scopeEnCours($query)
    {
        return $query->where('statut', 'EN_COURS');
    }

    /**
     * Scope pour les élections ouvertes
     */
    public function scopeOuvertes($query)
    {
        return $query->where('statut', 'OUVERTE');
    }

    /**
     * Scope pour les élections fermées
     */
    public function scopeFermees($query)
    {
        return $query->where('statut', 'FERMEE');
    }
}
