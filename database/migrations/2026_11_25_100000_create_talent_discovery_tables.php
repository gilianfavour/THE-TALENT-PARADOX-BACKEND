<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Talent Discovery: every kind of experience counts, not just formal jobs.
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index(); // employment, informal, volunteering, internship, academic_project, personal_project, freelance, self_learning, entrepreneurship, community
            $table->string('title');
            $table->string('organisation')->nullable();
            $table->text('description')->nullable();
            $table->string('evidence_url')->nullable();
            $table->string('started_on')->nullable();
            $table->string('ended_on')->nullable();
            $table->json('inferred_skills')->nullable();
            $table->timestamps();
        });

        // Evidence ladder: self_reported -> ai_inferred -> evidence_supported -> assessed -> human_validated
        Schema::table('candidate_skills', function (Blueprint $table) {
            $table->string('evidence_status')->default('self_reported')->after('claimed_evidence');
            $table->string('source')->nullable()->after('evidence_status');
            $table->timestamp('validated_at')->nullable()->after('demonstrated_at');
            $table->string('validated_by')->nullable()->after('validated_at');
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->boolean('talent_pool_visible')->default(true)->after('status');
            $table->boolean('consent_rediscovery')->default(true)->after('talent_pool_visible');
            $table->string('career_interests')->nullable()->after('headline');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->string('level')->default('mid')->after('employment_type'); // entry, mid, senior
            $table->timestamp('requirements_approved_at')->nullable()->after('parsed_requirements');
        });

        // Talent Rediscovery: an unsuccessful application is not a rejection from the talent pool.
        Schema::create('rediscovery_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->unsignedTinyInteger('original_score')->nullable();
            $table->json('reasons')->nullable();
            $table->json('gaps')->nullable();
            $table->string('status')->default('pending_review')->index(); // pending_review, invited, dismissed, accepted, declined
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->unique(['candidate_id', 'job_id']);
        });

        Schema::create('candidate_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('link')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_notifications');
        Schema::dropIfExists('rediscovery_recommendations');
        Schema::table('jobs', fn (Blueprint $t) => $t->dropColumn(['level', 'requirements_approved_at']));
        Schema::table('candidates', fn (Blueprint $t) => $t->dropColumn(['talent_pool_visible', 'consent_rediscovery', 'career_interests']));
        Schema::table('candidate_skills', fn (Blueprint $t) => $t->dropColumn(['evidence_status', 'source', 'validated_at', 'validated_by']));
        Schema::dropIfExists('experiences');
    }
};
