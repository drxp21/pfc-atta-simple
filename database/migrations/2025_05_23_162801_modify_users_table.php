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
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'nom');
            $table->string('prenom');
            $table->string('telephone')->nullable();
            $table->enum('type_personnel', ['PER', 'PATS','ADMIN']);
            $table->foreignId('departement_id')->nullable()->constrained('departements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('nom', 'name');
            $table->dropColumn(['prenom', 'telephone', 'type_personnel']);
            $table->dropConstrainedForeignId('departement_id');
        });
    }
};
