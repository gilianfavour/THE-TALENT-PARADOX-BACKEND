<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Job;
use App\Services\CvParser;
use App\Services\JobParser;
use App\Services\MatchEngine;
use App\Services\PracticalAssessmentService;
use App\Services\RediscoveryService;
use App\Services\TalentDiscovery;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The spec's demo story, safe to run on top of existing data (idempotent):
 *  - Candidate B (fresh graduate): no job history, a university management-system project, a practical coding task.
 *  - Candidate C (informal sector): family shop + community work, applies for Administration Officer,
 *    is a weak match there and is rediscovered for Project Support Officer.
 * Run: php artisan db:seed --class=DemoStorySeeder
 */
class DemoStorySeeder extends Seeder
{
    public function run(): void
    {
        $project = Job::updateOrCreate(
            ['title' => 'Project Support Officer', 'client_name' => 'Kampala Community Health Trust'],
            [
                'location' => 'Kampala',
                'employment_type' => 'Full-time',
                'salary_min' => 900000,
                'salary_max' => 1400000,
                'deadline' => now()->addDays(10)->toDateString(),
                'status' => 'open',
                'description' => "Support our community outreach projects from planning to reporting.\n\nRequirements:\n- Project coordination or project management experience (community, school or volunteer projects count)\n- Strong record keeping and organisation\n- Clear communication with volunteers and partners\n- Basic reporting in Excel or Microsoft Office\n\nNice to have: Budgeting, training coordination.",
            ]
        );
        $project->update(['parsed_requirements' => app(JobParser::class)->parse($project), 'requirements_approved_at' => now()]);

        $admin = Job::where('title', 'like', '%Administration%')->orWhere('title', 'like', '%Administrative%')->first();
        $dev = Job::where('title', 'like', '%Software Developer%')->first();

        // rebuild the two demo candidates from scratch so the story is the same on every run
        Candidate::whereIn('email', ['amina.nakato@gmail.com', 'brian.ssempala@gmail.com'])->get()->each->delete();

        $discovery = app(TalentDiscovery::class);
        $practical = app(PracticalAssessmentService::class);
        $engine = app(MatchEngine::class);

        // Candidate C: informal sector
        $amina = $this->candidate('Amina Nakato', 'amina.nakato@gmail.com', 'Kisenyi, Kampala',
            'Family shop worker and community volunteer',
            "Amina Nakato. Kisenyi, Kampala.\nWorked 4 years in my family's shop. Served customers every day, kept the stock book and the daily sales record, ordered goods from suppliers.\nOrganised a community clean-up with 30 volunteers.\nSenior Four certificate.");
        if ($amina->experiences()->count() === 0) {
            $discovery->addExperience($amina, ['type' => 'informal', 'title' => 'Stock, records and daily sales, family shop', 'organisation' => 'Nakato General Store, Kisenyi', 'description' => 'Kept the stock book and daily sales records, did inventory management and record keeping, served customers, reconciled cash every evening.', 'started_on' => '2021', 'ended_on' => 'Present']);
            $discovery->addExperience($amina, ['type' => 'community', 'title' => 'Community clean-up coordinator', 'organisation' => 'Kisenyi Youth Group', 'description' => 'Project management of a monthly clean-up: organised 30 volunteers, planned the schedule, kept attendance records, reported to the LC chairperson. Communication and teamwork every week.', 'started_on' => '2023', 'ended_on' => 'Present']);
        }
        if ($admin) {
            $app = Application::firstOrCreate(['candidate_id' => $amina->id, 'job_id' => $admin->id], ['status' => 'screening']);
            // she took the suggested project task: evidence of the competencies the admin CV filter missed
            $practical->submit($amina, 'project-activity-plan', $project->id,
                "Week 1: confirm the venue with the LC chairperson, the nurse leads the screening plan and I make the volunteer schedule. Week 2: mobilise the community through churches and the youth group, volunteers responsible for posters. Week 3: book transport with the driver, buy supplies and keep every receipt in the budget spreadsheet (transport, refreshments, printing, airtime). Week 4: outreach day, a register for every visit so we can report how many of the 200 people were screened. Risk: rain on the day. Backup: a covered venue at the church and a reserve date. After the day I write the report with numbers and costs.");
            $engine->scoreApplication($app->fresh());
            app(RediscoveryService::class)->forApplication($app->fresh());
        }

        // Candidate B: fresh graduate
        $brian = $this->candidate('Brian Ssempala', 'brian.ssempala@gmail.com', 'Mukono',
            'Fresh Computer Science diploma graduate',
            "Brian Ssempala. Diploma in Computer Science, 2026.\nFinal year project: hospital management system built with PHP, Laravel and MySQL. Patient registration, appointments and billing modules. Used Git for version control.\nNo formal employment yet.");
        if ($brian->experiences()->count() === 0) {
            $discovery->addExperience($brian, ['type' => 'academic_project', 'title' => 'Hospital management system (final year project)', 'organisation' => 'Diploma in Computer Science', 'description' => 'Built a hospital management system with PHP, Laravel, MySQL and JavaScript: requirements analysis with nurses, database design, patient registration and billing, documentation and testing. Git for version control.', 'evidence_url' => 'https://github.com/example/hospital-management-system', 'started_on' => '2025', 'ended_on' => '2026']);
        }
        if ($dev) {
            $app = Application::firstOrCreate(['candidate_id' => $brian->id, 'job_id' => $dev->id], ['status' => 'applied']);
            if (true) {
                $practical->submit($brian, 'dev-debug-login', $dev->id,
                    "Bug 1: `row.password = input` is an assignment, not a comparison, so the if is always truthy and any password works. Use a comparison.\nBug 2: the query concatenates user input, which allows SQL injection. Use a prepared statement with a placeholder.\nBug 3: passwords are compared in plain text. Store a bcrypt hash and use password_verify / bcrypt.compare.\n\nasync function login(email, input) {\n  const row = await db.query('SELECT * FROM users WHERE email = ?', [email]);\n  if (!row) return false;\n  return await bcrypt.compare(input, row.password_hash);\n}\n\nTests: correct password returns true, wrong password returns false, empty input fails, unknown email fails, and an injection string like ' OR 1=1 -- is rejected.");
            }
            $engine->scoreApplication($app->fresh());
        }

        $engine->refreshAll(0);
        app(RediscoveryService::class)->scanAll();
    }

    private function candidate(string $name, string $email, string $location, string $headline, string $cv): Candidate
    {
        $candidate = Candidate::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'location' => $location, 'headline' => $headline, 'cv_text' => $cv, 'portal_token' => Str::random(40), 'status' => 'active']
        );

        if ($candidate->skills()->count() === 0) {
            $parsed = app(CvParser::class)->parse($cv);
            foreach ($parsed['skills'] as $s) {
                $candidate->skills()->updateOrCreate(['skill_id' => $s['skill_id']], [
                    'claimed_years' => $s['years'], 'claimed_evidence' => $s['evidence'], 'source' => 'CV',
                ]);
            }
            $candidate->update(['profile_flags' => array_merge($parsed['flags'] ?? [], [
                'years_experience' => $parsed['years_experience'], 'education' => $parsed['education'],
            ])]);
        }

        return $candidate;
    }
}
