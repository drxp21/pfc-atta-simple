<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcesVerbal extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'proces_verbaux';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'election_id',
        'contenu_html',
        'nb_electeurs_inscrits',
        'nb_votes_exprimes',
        'nb_votes_blancs',
        'nb_abstentions',
        'date_generation',
        'genere_par',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'nb_electeurs_inscrits' => 'integer',
        'nb_votes_exprimes' => 'integer',
        'nb_votes_blancs' => 'integer',
        'nb_abstentions' => 'integer',
        'date_generation' => 'datetime',
    ];

    /**
     * Relation avec l'élection
     */
    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    /**
     * Relation avec l'utilisateur qui a généré le procès-verbal
     */
    public function generePar()
    {
        return $this->belongsTo(User::class, 'genere_par');
    }
}
