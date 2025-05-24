<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidature extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'election_id',
        'candidat_id',
        'programme',
        'statut',
        'commentaire_admin',
        'date_soumission',
        'date_validation',
        'validee_par',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_soumission' => 'datetime',
        'date_validation' => 'datetime',
    ];

    /**
     * Relation avec l'élection
     */
    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    /**
     * Relation avec le candidat
     */
    public function candidat()
    {
        return $this->belongsTo(User::class, 'candidat_id');
    }

    /**
     * Relation avec l'utilisateur qui a validé la candidature
     */
    public function valideePar()
    {
        return $this->belongsTo(User::class, 'validee_par');
    }

    /**
     * Relation avec les votes
     */
    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * Relation avec les résultats
     */
    public function resultats()
    {
        return $this->hasMany(Resultat::class);
    }

    /**
     * Scope pour les candidatures validées
     */
    public function scopeValidees($query)
    {
        return $query->where('statut', 'VALIDEE');
    }

    /**
     * Scope pour les candidatures en attente
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'EN_ATTENTE');
    }

    /**
     * Scope pour les candidatures rejetées
     */
    public function scopeRejetees($query)
    {
        return $query->where('statut', 'REJETEE');
    }
}
