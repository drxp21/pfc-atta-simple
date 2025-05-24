<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'election_id',
        'electeur_id',
        'candidature_id',
        'vote_blanc',
        'date_vote',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'vote_blanc' => 'boolean',
        'date_vote' => 'datetime',
    ];

    /**
     * Relation avec l'élection
     */
    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    /**
     * Relation avec l'électeur
     */
    public function electeur()
    {
        return $this->belongsTo(User::class, 'electeur_id');
    }

    /**
     * Relation avec la candidature
     */
    public function candidature()
    {
        return $this->belongsTo(Candidature::class);
    }
}
