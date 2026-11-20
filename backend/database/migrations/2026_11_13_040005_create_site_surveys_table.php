<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('cable_meters', 8, 2)->default(0);
            $table->string('conduit_type', 60)->nullable();
            $table->decimal('max_height_meters', 5, 2)->default(0);
            $table->string('power_requirements', 255)->nullable();
            $table->text('observations')->nullable();
            $table->dateTime('surveyed_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('site_survey_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_survey_id')->constrained('site_surveys')->cascadeOnDelete();
            $table->string('file_path', 255);
            $table->string('caption', 200)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_survey_photos');
        Schema::dropIfExists('site_surveys');
    }
};
