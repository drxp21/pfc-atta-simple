<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resultat extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'election_id',
        'candidature_id',
        'nb_voix',
        'pourcentage',
        'rang',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'nb_voix' => 'integer',
        'pourcentage' => 'decimal:2',
        'rang' => 'integer',
    ];

    /**
     * Relation avec l'élection
     */
    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    /**
     * Relation avec la candidature
     */
    public function candidature()
    {
        return $this->belongsTo(Candidature::class);
    }
}
