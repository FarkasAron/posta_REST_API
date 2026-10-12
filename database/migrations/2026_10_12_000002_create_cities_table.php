<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_county')->constrained('counties');
            $table->string('name', 50)->index();
            $table->unsignedInteger('zip_code')->index();
            $table->unsignedInteger('population')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
