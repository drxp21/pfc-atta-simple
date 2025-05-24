<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElecteurAutorise extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'electeurs_autorises';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'election_id',
        'electeur_id',
        'a_vote',
        'date_autorisation',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'a_vote' => 'boolean',
        'date_autorisation' => 'datetime',
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
}
