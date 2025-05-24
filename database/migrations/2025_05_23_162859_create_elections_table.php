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
        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description');
            $table->enum('type_election', ['CHEF_DEPARTEMENT', 'DIRECTEUR_UFR', 'VICE_RECTEUR']);
            $table->enum('statut', ['BROUILLON', 'OUVERTE', 'EN_COURS', 'FERMEE'])->default('BROUILLON');
            $table->foreignId('departement_id')->nullable()->constrained('departements');
            $table->dateTime('date_debut_candidature');
            $table->dateTime('date_fin_candidature');
            $table->dateTime('date_debut_vote');
            $table->dateTime('date_fin_vote');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elections');
    }
};
