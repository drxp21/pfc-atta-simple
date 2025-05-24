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
        Schema::create('candidatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained();
            $table->foreignId('candidat_id')->constrained('users');
            $table->text('programme');
            $table->enum('statut', ['EN_ATTENTE', 'VALIDEE', 'REJETEE'])->default('EN_ATTENTE');
            $table->text('commentaire_admin')->nullable();
            $table->dateTime('date_soumission');
            $table->dateTime('date_validation')->nullable();
            $table->foreignId('validee_par')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->unique(['election_id', 'candidat_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidatures');
    }
};
