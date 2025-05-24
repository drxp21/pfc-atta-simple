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
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained();
            $table->foreignId('electeur_id')->constrained('users');
            $table->foreignId('candidature_id')->nullable()->constrained();
            $table->boolean('vote_blanc')->default(false);
            $table->dateTime('date_vote');
            $table->timestamps();
            
            $table->unique(['election_id', 'electeur_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
