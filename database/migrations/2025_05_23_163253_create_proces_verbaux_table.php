<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proces_verbaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained();
            $table->longText('contenu_html');
            $table->integer('nb_electeurs_inscrits')->default(0);
            $table->integer('nb_votes_exprimes')->default(0);
            $table->integer('nb_votes_blancs')->default(0);
            $table->dateTime('date_generation');
            $table->foreignId('genere_par')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proces_verbaux');
    }
};
