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
        Schema::create('resultats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained();
            $table->foreignId('candidature_id')->constrained();
            $table->integer('nb_voix')->default(0);
            $table->decimal('pourcentage', 5, 2)->default(0);
            $table->integer('rang')->nullable();
            $table->timestamps();
            
            $table->unique(['election_id', 'candidature_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resultats');
    }
};
