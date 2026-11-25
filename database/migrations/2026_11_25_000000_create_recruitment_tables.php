<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category')->index();
            $table->json('aliases')->nullable();
            $table->timestamps();
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('location')->nullable();
            $table->string('portal_token', 64)->unique();
            $table->string('headline')->nullable();
            $table->longText('cv_text')->nullable();
            $table->json('profile_flags')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('candidate_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('claimed_years')->nullable();
            $table->text('claimed_evidence')->nullable();
            $table->unsignedTinyInteger('demonstrated_score')->nullable();
            $table->timestamp('demonstrated_at')->nullable();
            $table->timestamps();
            $table->unique(['candidate_id', 'skill_id']);
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->string('client_name')->nullable();
            $table->string('location')->nullable();
            $table->string('employment_type')->default('Full-time');
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->date('deadline')->nullable()->index();
            $table->string('status')->default('open')->index();
            $table->longText('description')->nullable();
            $table->longText('requirements_text')->nullable();
            $table->json('parsed_requirements')->nullable();
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('applied')->index();
            $table->unsignedTinyInteger('ai_score')->nullable();
            $table->json('match_snapshot')->nullable();
            $table->timestamps();
            $table->unique(['candidate_id', 'job_id']);
        });

        Schema::create('assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->json('options');
            $table->unsignedTinyInteger('correct_index');
            $table->unsignedTinyInteger('difficulty')->default(1);
            $table->timestamps();
        });

        Schema::create('assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->json('skill_scores')->nullable();
            $table->json('answers')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->text('reason')->nullable();
            $table->string('recruiter')->default('admin');
            $table->timestamps();
        });

        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('physical');
            $table->dateTime('scheduled_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('outcome')->nullable();
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('decisions');
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('assessment_questions');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('candidate_skills');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('skills');
    }
};
