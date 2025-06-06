<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('electeurs_autorises');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('electeurs_autorises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained();
            $table->foreignId('electeur_id')->constrained('users');
            $table->boolean('a_vote')->default(false);
            $table->dateTime('date_autorisation');
            $table->timestamps();

            $table->unique(['election_id', 'electeur_id']);
        });
    }
};