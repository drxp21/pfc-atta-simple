<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cette migration est désactivée car nous n'utilisons plus la table electeurs_autorises
// Les autorisations de vote sont maintenant gérées directement dans le contrôleur VoteController
// en fonction du type d'élection et du type d'utilisateur
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migration désactivée
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migration désactivée
    }
};
