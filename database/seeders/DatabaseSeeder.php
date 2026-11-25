<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\Candidate;
use App\Models\Decision;
use App\Models\Interview;
use App\Models\Job;
use App\Models\Skill;
use App\Models\User;
use App\Services\CvParser;
use App\Services\JobParser;
use App\Services\MatchEngine;
use App\Services\SkillTaxonomy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@secondlook.test'],
            ['name' => 'Donswift Recruiter', 'password' => bcrypt('password')]
        );

        $skills = $this->seedSkills();
        $this->seedQuestions($skills);
        $jobs = $this->seedJobs();
        $candidates = $this->seedCandidates();
        $this->seedEvidence($candidates);
        $this->seedPipeline($candidates, $jobs);

        app(MatchEngine::class)->refreshAll();
        $this->seedTalentDiscovery($candidates);
    }

    /** Non-CV experiences, rediscovery recommendations and a first invitation for the demo story. */
    private function seedTalentDiscovery(array $candidates): void
    {
        $discovery = app(\App\Services\TalentDiscovery::class);
        $stories = [
            'baraka' => [
                ['type' => 'informal', 'title' => 'Stock and sales records, family shop', 'organisation' => 'Mushi General Store', 'description' => 'Kept daily sales and stock in Excel, reconciled cash every evening, built a simple report showing best-selling items each month for data analysis.'],
                ['type' => 'self_learning', 'title' => 'Self-taught spreadsheets and data analysis', 'description' => 'Completed free online courses in Excel pivot tables and data analysis. Built dashboards for two local businesses.', 'evidence_url' => 'https://example.com/baraka-dashboard'],
            ],
            'emmanuel' => [
                ['type' => 'entrepreneurship', 'title' => 'Delivery round bookkeeping', 'description' => 'Tracked payments, invoices and expenses for a 3-rider delivery business. Bookkeeping and customer service every day.'],
            ],
            'juma' => [
                ['type' => 'community', 'title' => 'Parents association coordinator', 'description' => 'Coordinated volunteers, organised events, handled complaints and communication with 200 families. Project coordination and record keeping.'],
            ],
        ];
        foreach ($stories as $key => $items) {
            if (! isset($candidates[$key])) {
                continue;
            }
            foreach ($items as $item) {
                $discovery->addExperience($candidates[$key], $item);
            }
        }

        app(MatchEngine::class)->refreshAll(0);
        $service = app(\App\Services\RediscoveryService::class);
        $service->scanAll();

        // one invitation already approved so the candidate side of the story is visible
        $first = \App\Models\RediscoveryRecommendation::where('status', 'pending_review')->orderByDesc('score')->first();
        if ($first) {
            $service->invite($first, 'donswift');
        }
    }

    private function seedSkills(): array
    {
        $map = [];
        foreach (SkillTaxonomy::all() as $s) {
            $map[$s['name']] = Skill::updateOrCreate(
                ['name' => $s['name']],
                ['category' => $s['category'], 'aliases' => $s['aliases']]
            )->id;
        }
        return $map;
    }

    private function q(string $skill, string $question, array $options, int $correct, int $difficulty = 1): array
    {
        return compact('skill', 'question', 'options', 'correct', 'difficulty');
    }

    private function seedQuestions(array $skills): void
    {
        $defs = [
            $this->q('Excel', 'Your sales figures look wrong: column C should be Qty x Unit Price, but totals show text. Fastest fix?', ['Re-type every formula by hand', 'Check the cell format and the formula in one row, then fill down', 'Delete the column', 'Ask IT to reinstall Excel'], 1, 2),
            $this->q('Excel', 'A manager asks you to summarise sales by region from a 2,000-row sheet. Best tool?', ['Sort A-Z and eyeball it', 'Pivot Table', 'Print it and highlight', 'Copy into Word'], 1, 1),
            $this->q('Excel', 'You need to pull a customer\'s phone number by their ID from another sheet. Which function?', ['VLOOKUP or XLOOKUP', 'SUM', 'COUNT', 'CONCAT'], 0, 2),
            $this->q('SQL', 'You need all customers in Dar es Salaam from the customers table. Which query?', ['SELECT * FROM customers WHERE city = \'Dar es Salaam\'', 'GET customers.city', 'SELECT city FROM customers', 'FILTER customers BY city'], 0, 1),
            $this->q('SQL', 'You need total sales per product. What do you use?', ['SELECT product, SUM(amount) FROM sales GROUP BY product', 'SELECT * FROM sales', 'COUNT(*) FROM sales', 'JOIN sales'], 0, 2),
            $this->q('Data Analysis', 'Revenue dropped last month. First step to find the cause?', ['Blame marketing', 'Break the data down by region, product and period to isolate where the drop is', 'Delete the outliers', 'Report the average'], 1, 2),
            $this->q('Data Analysis', 'What does an outlier in a dataset usually mean?', ['The data is broken', 'An unusual value worth investigating before averages are trusted', 'Always an error to delete', 'The highest value is always correct'], 1, 1),
            $this->q('PHP', 'A form submits but nothing saves. You check the database and logs. What do you look at first?', ['The CSS file', 'The controller handling the request and any validation errors', 'The image sizes', 'The font loading'], 1, 1),
            $this->q('PHP', 'What does a Laravel migration do?', ['Compresses images', 'Defines and versions the database schema', 'Sends emails', 'Minifies JavaScript'], 1, 1),
            $this->q('JavaScript', 'A button click does nothing. Where do you check first?', ['Browser developer console for errors', 'The server room', 'The printer queue', 'A different website'], 0, 1),
            $this->q('JavaScript', 'What does Array.map() return?', ['A new array with each element transformed', 'Nothing', 'A single number', 'A boolean'], 0, 1),
            $this->q('Communication', 'A client is angry that a delivery is late. Best first response?', ['Explain why it is not your fault', 'Acknowledge the frustration, apologise briefly, state what you will do', 'Hang up', 'Tell them to complain in writing'], 1, 2),
            $this->q('Communication', 'You must brief a colleague about a handover. Best approach?', ['Send a long essay', 'Clear bullet points: what happened, what is pending, what is needed', 'Wait until Friday', 'Say nothing'], 1, 1),
            $this->q('Customer Service', 'A caller asks something you do not know. What do you do?', ['Make something up', 'Promise to find out and follow up within a stated time', 'Transfer them randomly', 'Say it is impossible'], 1, 2),
            $this->q('Customer Service', 'What builds trust fastest with a frustrated customer?', ['Scripted politeness', 'Listening, repeating their issue back, then acting on it', 'Discounts', 'Silence'], 1, 1),
            $this->q('Accounting', 'A trial balance does not balance. Most likely cause?', ['A transaction posted to the wrong side or a missing entry', 'The office printer', 'Tax rates changed', 'Nothing to worry about'], 0, 2),
            $this->q('Accounting', 'What is bank reconciliation?', ['Matching the cash book to the bank statement and explaining differences', 'Closing the bank account', 'Paying salaries', 'Filing VAT'], 0, 1),
            $this->q('Bookkeeping', 'What is double-entry bookkeeping?', ['Writing every entry twice', 'Every transaction affects at least two accounts (debit and credit)', 'Two people check every entry', 'Keeping two ledgers'], 1, 2),
            $this->q('Attention to Detail', 'A form asks for a phone number. A customer writes "0712345678 or maybe 0712345679". What do you do?', ['Pick the first one', 'Confirm the correct number before proceeding', 'Enter both', 'Skip it'], 1, 1),
            $this->q('Attention to Detail', 'You spot that invoice INV-2045 was billed twice. Next step?', ['Ignore it', 'Flag it, verify, and correct it before payment runs', 'Delete the invoice', 'Tell no one'], 1, 1),
            $this->q('Problem Solving', 'Your delivery van breaks down with 12 stops left. Best plan?', ['Cancel the day', 'Re-prioritise urgent deliveries, call a backup vehicle, notify affected customers', 'Wait for the mechanic all day', 'Deliver nothing'], 1, 2),
            $this->q('Problem Solving', 'A process keeps failing at the same step. What do you do?', ['Keep repeating it', 'Isolate the step, find the root cause, fix or escalate it', 'Quit', 'Blame the previous shift'], 1, 1),
            $this->q('Social Media Marketing', 'A post gets negative comments. Best response?', ['Delete all comments', 'Respond professionally, address the concern, take sensitive issues offline', 'Argue back', 'Disable comments forever'], 1, 2),
            $this->q('Driving', 'Your route has a road closure 2 km ahead. What do you do first?', ['U-turn on the highway', 'Pull over safely, check alternatives, update dispatch', 'Continue and hope', 'Abandon the vehicle'], 1, 1),
            $this->q('Administration', 'An executive needs minutes from a meeting that ended 10 minutes ago. You were not there. Best action?', ['Refuse', 'Collect the attendees\' notes and recordings, draft minutes for review', 'Invent the minutes', 'Say it is impossible'], 1, 2),
            $this->q('Statistics', 'Average age of your customer base is 34 but the median is 26. What does that suggest?', ['A few older customers are skewing the mean', 'The data is useless', 'Median is wrong', 'Mean is wrong'], 0, 3),
            $this->q('Reporting', 'A manager wants "the report by Friday". Smartest first question?', ['What does it need to answer and who will read it?', 'How many pages should it be?', 'What font?', 'Can someone else do it?'], 0, 2),
            $this->q('Time Management', 'Three urgent tasks land at 4:30pm. What do you do first?', ['Start all three at once', 'Rank by deadline and impact, then tackle the highest-impact one fully', 'Start with the easiest', 'Ignore them until tomorrow'], 1, 2),
        ];

        foreach ($defs as $d) {
            AssessmentQuestion::updateOrCreate(
                ['question' => $d['question']],
                [
                    'skill_id' => $skills[$d['skill']],
                    'options' => $d['options'],
                    'correct_index' => $d['correct'],
                    'difficulty' => $d['difficulty'],
                ]
            );
        }
    }

    private function job(string $title, string $client, string $location, string $type, ?int $min, ?int $max, int $deadlineDays, string $desc): array
    {
        return compact('title', 'client', 'location', 'type', 'min', 'max', 'deadlineDays', 'desc');
    }

    private function seedJobs(): array
    {
        $defs = [
            'dev' => $this->job('Junior Software Developer', 'TechVentures Ltd', 'Dar es Salaam', 'Full-time', 800000, 1400000, 12,
                "We need a junior developer to join our product team building web applications.\n\nRequirements:\n- 1+ years experience with PHP or JavaScript\n- Familiar with SQL databases (MySQL)\n- Exposure to Git version control\n- HTML, CSS and a modern JS framework is a plus\n- Degree or diploma in Computing preferred\n\nNice to have: Laravel, React, REST APIs."),
            'analyst' => $this->job('Data Analyst', 'Kili Analytics', 'Dar es Salaam', 'Full-time', 1200000, 2000000, 8,
                "We are looking for a Data Analyst to support client reporting.\n\nMust have:\n- Strong Excel skills (pivot tables, lookups)\n- Working SQL knowledge\n- 2+ years analysing data and producing reports\n- Attention to detail and clear communication\n\nPreferred: Statistics background, Power BI or other visualisation tools."),
            'csr' => $this->job('Customer Service Officer', 'SokoHub Ltd', 'Arusha', 'Full-time', 700000, 1100000, 5,
                "E-commerce support role handling customer queries by phone, chat and email.\n\nRequirements:\n- Excellent communication skills\n- 1+ year customer service or call handling experience\n- Comfortable with CRM systems and Microsoft Office\n- Complaint resolution experience is a bonus\n- Diploma in business or related field preferred."),
            'accounts_asst' => $this->job('Accounts Assistant', 'Mbeya Foods Ltd', 'Mbeya', 'Full-time', 900000, 1400000, 9,
                "Supports the finance team with daily bookkeeping.\n\nRequirements:\n- Diploma in Accounting or equivalent\n- 2+ years bookkeeping or accounting support\n- Solid Excel (invoicing, reconciliation)\n- Knowledge of invoicing, tax records and petty cash\n- Attention to detail is essential."),
            'senior_acc' => $this->job('Senior Accountant', 'East Nile Bank', 'Dar es Salaam', 'Full-time', 2500000, 3500000, 18,
                "Senior finance role overseeing reporting and compliance.\n\nMust have:\n- Bachelor's degree in Accounting or Finance\n- 5+ years professional accounting\n- Tax compliance and payroll experience\n- Auditing exposure\n- Leadership: you will supervise two assistants\n\nPreferred: Budgeting experience, ERP systems."),
            'marketing' => $this->job('Marketing & Communications Officer', 'Savanna Beverages Ltd', 'Dodoma', 'Full-time', 1400000, 2200000, 14,
                "Own our brand voice across channels.\n\nRequirements:\n- Bachelor's in Marketing/Comms\n- 2+ years digital marketing or social media marketing\n- Strong content writing skills\n- Graphic design tools (Canva/Photoshop)\n- Project management for campaigns\n\nNice to have: SEO, email marketing, market research."),
            'driver' => $this->job('Delivery Driver', 'Highway Freight Ltd', 'Mwanza', 'Full-time', 600000, 900000, 6,
                "Reliable driver for regional deliveries.\n\nRequirements:\n- Valid Class C driving licence, 3+ years driving\n- Logistics and route planning experience\n- Customer service manner\n- Inventory management exposure is helpful\n- Time management is critical\n\nPreferred: Health & Safety, First Aid."),
            'admin' => $this->job('Administration Officer', 'Moshi Mission Hospital', 'Moshi', 'Full-time', 1000000, 1500000, 11,
                "Keeps the front office running.\n\nRequirements:\n- Diploma in Administration or related\n- 2+ years office administration or secretarial work\n- Record keeping and CRM experience\n- Microsoft Office proficient\n- Strong communication and organisation\n\nPreferred: HR exposure, training coordination."),
        ];

        $jobs = [];
        $parser = app(JobParser::class);
        foreach ($defs as $key => $d) {
            $job = Job::updateOrCreate(
                ['title' => $d['title'], 'client_name' => $d['client']],
                [
                    'location' => $d['location'],
                    'employment_type' => $d['type'],
                    'salary_min' => $d['min'],
                    'salary_max' => $d['max'],
                    'deadline' => now()->addDays($d['deadlineDays'])->toDateString(),
                    'status' => 'open',
                    'description' => $d['desc'],
                ]
            );
            $job->update(['parsed_requirements' => $parser->parse($job)]);
            $jobs[$key] = $job;
        }
        return $jobs;
    }

    private function cv(string $file): string
    {
        return trim($file);
    }

    private function seedCandidates(): array
    {
        $defs = [
            'baraka' => [
                'name' => 'Baraka Mushi', 'email' => 'baraka.mushi@gmail.com', 'phone' => '0712 345 678',
                'location' => 'Moshi', 'headline' => 'Motivated jobseeker looking for opportunity',
                'cv' => "BARAKA MUSHI\nP.O. Box 1234, Moshi\nPhone: 0712 345 678 | baraka.mushi@gmail.com\n\nPROFILE\nHardworking young man looking for any job that can change my life.\nI have helped my mother sell vegetables at the market since I was 15.\nI am quick to learn new things and never give up.\n\nWORK EXPERIENCE\n2019 - Now: Various jobs\n- Helped at family shop, counting money and serving customers\n- Asked to help with the computer at the shop, learned to type fast\n- Assisted my uncle with his phone repair business\n\n2017 - 2019: Farm work\n- Worked on our shamba, planting and harvesting\n\nEDUCATION\n2016: Completed O-Level\n\nSKILLS\n- Good with numbers, helped count shop money every day\n- Patient with customers\n- Can use a computer for basic things, typing and WhatsApp",
            ],
            'neema' => [
                'name' => 'Neema Kimaro', 'email' => 'neema.kimaro@outlook.com', 'phone' => '0744 111 222',
                'location' => 'Dar es Salaam', 'headline' => 'Full-stack developer',
                'cv' => "NEEMA KIMARO\nDar es Salaam | neema.kimaro@outlook.com\n\nSUMMARY\nFull-stack developer with 5+ years experience delivering web applications for fintech and e-commerce clients.\n\nEXPERIENCE\nSenior Developer, CodeBridge - 3 years\n- Built PHP/Laravel applications, JavaScript/React frontends, Node.js APIs\n- SQL (MySQL, Postgres) database design\n- Docker, AWS cloud, Git workflows\n\nDeveloper, FinEdge - 2 years\n- TypeScript, Next.js, REST APIs\n- Python scripting, cybersecurity awareness\n\nSKILLS\nPHP, Laravel, JavaScript, TypeScript, React, Next.js, Node.js, Python, SQL, MySQL, Git, REST APIs, Docker, Cloud, Cybersecurity, HTML, CSS, UI/UX Design, Excel, Data Analysis, Reporting, Communication, Teamwork, Leadership, Problem Solving, Time Management, Project Management\n\nEDUCATION\nBSc Computer Science, 2019",
            ],
            'juma' => [
                'name' => 'Juma Abdallah', 'email' => 'juma.abdallah@gmail.com', 'phone' => '0755 333 444',
                'location' => 'Arusha', 'headline' => 'Former teacher transitioning to customer service',
                'cv' => "JUMA ABDALLAH\nArusha | juma.abdallah@gmail.com\n\nPROFILE\nFormer primary school teacher making a career change into customer service.\n8 years teaching Mathematics and English to 60+ pupils daily.\n\nEXPERIENCE\nTeacher, St. Mary's Primary - 8 years\n- Communication: explaining complex ideas simply to children and parents\n- Conflict resolution in the classroom\n- Record keeping: attendance and grades in Microsoft Office\n- Public speaking: led school assemblies\n\nNow doing online courses in customer service and basic computer skills.\n\nEDUCATION\nTeaching Certificate, 2014\n\nSKILLS\nTeaching, Communication, Public Speaking, Microsoft Office, Record Keeping, Problem Solving, Patience, Conflict Resolution",
            ],
            'david' => [
                'name' => 'David Mollel', 'email' => 'david.mollel.dev@gmail.com', 'phone' => '0762 555 666',
                'location' => 'Dar es Salaam', 'headline' => 'Junior web developer',
                'cv' => "DAVID MOLLEL\nDar es Salaam | david.mollel.dev@gmail.com\n\nSUMMARY\nJunior developer, self-taught through freeCodeCamp and open-source contributions.\nBuilt 4 client websites while at university.\n\nEXPERIENCE\nFreelance Web Developer - 1 year\n- PHP sites with MySQL backends\n- JavaScript interactive frontends\n- HTML/CSS responsive layouts\n- Git for version control\n\nPROJECTS\n- School fee tracking system (PHP + MySQL)\n- Church website with events calendar\n\nSKILLS\nPHP, JavaScript, HTML, CSS, SQL, MySQL, Git, Problem Solving\n\nEDUCATION\nBSc Computer Science (in progress, year 3)\nSelf-taught: freeCodeCamp certifications",
            ],
            'grace' => [
                'name' => 'Grace Lyimo', 'email' => 'grace.lyimo@yahoo.com', 'phone' => '0789 777 888',
                'location' => 'Dar es Salaam', 'headline' => 'Accountant, CPA certified',
                'cv' => "GRACE LYIMO\nDar es Salaam | grace.lyimo@yahoo.com\n\nSUMMARY\nCPA-qualified accountant with 6 years across retail and manufacturing finance.\n\nEXPERIENCE\nAccountant, Kijani Manufacturing - 4 years\n- Full accounting: general ledger, tax returns, payroll\n- Budgeting and cash-flow forecasting\n- Auditing support for annual audits\n- Invoicing and reconciliation\n\nAccounts Clerk, RetailChain - 2 years\n- Bookkeeping, petty cash, bank reconciliation\n\nSKILLS\nAccounting, Bookkeeping, Tax, Payroll, Auditing, Budgeting, Invoicing, Excel, Attention to Detail, Leadership\n\nEDUCATION\nBachelor's Degree in Accounting, CPA",
            ],
            'emmanuel' => [
                'name' => 'Emmanuel Shirima', 'email' => 'emmanuel.shirima@gmail.com', 'phone' => '0713 999 000',
                'location' => 'Mwanza', 'headline' => 'Delivery driver',
                'cv' => "EMMANUEL SHIRIMA\nMwanza | emmanuel.shirima@gmail.com\n\nPROFILE\nReliable driver with a clean record and a knack for paperwork.\n\nEXPERIENCE\nDelivery Driver, Jumbo Logistics - 4 years\n- Class C deliveries across the lake zone\n- Kept delivery logs and stock records daily\n- Helped customers with orders and receipts\n- Planned routes to save fuel\n\nSKILLS\nDriving, Logistics, Customer Service, Time Management, Inventory Management, Record Keeping\n\nEDUCATION\nSecondary education, Class C licence",
            ],
            'asha' => [
                'name' => 'Asha Kileo', 'email' => 'asha.kileo@gmail.com', 'phone' => '0682 123 456',
                'location' => 'Dar es Salaam', 'headline' => 'Recent BCom graduate',
                'cv' => "ASHA KILEO\nDar es Salaam | asha.kileo@gmail.com\n\nPROFILE\nFresh BCom graduate eager to start a career in data or finance.\n\nEDUCATION\nBCom (Finance), 2024\n\nINTERNSHIP\nFinance intern, CityMart - 3 months\n- Excel for daily sales tracking\n- Record keeping and filing\n- Teamwork across departments\n\nSKILLS\nExcel, Microsoft Office, Record Keeping, Communication, Teamwork, Attention to Detail",
            ],
            'hassan' => [
                'name' => 'Hassan Mwangonde', 'email' => 'h.mwangonde@gmail.com', 'phone' => '0676 456 789',
                'location' => 'Moshi', 'headline' => 'Administrator returning after career break',
                'cv' => "HASSAN MWANGONDE\nMoshi | h.mwangonde@gmail.com\n\nPROFILE\nAdministrator with 5 years NGO experience. Took a 3-year career break to care for family, now returning to work.\n\nEXPERIENCE\nAdministrative Officer, HopeBridge NGO - 5 years\n- Office administration and secretarial duties\n- Record keeping and donor documentation\n- CRM contact management\n- Microsoft Office daily\n\nCAREER BREAK\n2021 - 2024: Family caregiving\n\nSKILLS\nAdministration, Secretarial, Record Keeping, CRM, Microsoft Office, Communication, HR",
            ],
            'rehema' => [
                'name' => 'Rehema Sanga', 'email' => 'rehema.sanga@gmail.com', 'phone' => '0758 888 999',
                'location' => 'Dodoma', 'headline' => 'Retail sales associate',
                'cv' => "REHEMA SANGA\nDodoma | rehema.sanga@gmail.com\n\nPROFILE\nRetail sales associate who loves talking to people and posting on social media.\n\nEXPERIENCE\nSales Associate, UrbanStyle - 3 years\n- Sales targets and customer service daily\n- Negotiation with walk-in customers\n- Ran the shop's Instagram page\n\nSKILLS\nSales, Negotiation, Customer Service, Communication, Social Media Marketing, Content Writing",
            ],
        ];

        $candidates = [];
        $cvParser = app(CvParser::class);
        foreach ($defs as $key => $d) {
            $candidate = Candidate::updateOrCreate(
                ['email' => $d['email']],
                [
                    'name' => $d['name'],
                    'phone' => $d['phone'],
                    'location' => $d['location'],
                    'portal_token' => Str::random(40),
                    'headline' => $d['headline'],
                    'cv_text' => $d['cv'],
                    'status' => 'active',
                ]
            );

            // parse the CV into claimed skills + profile flags
            $parsed = $cvParser->parse($d['cv']);
            foreach ($parsed['skills'] as $s) {
                $candidate->skills()->updateOrCreate(
                    ['skill_id' => $s['skill_id']],
                    [
                        'claimed_years' => $s['years'],
                        'claimed_evidence' => $s['evidence'],
                    ]
                );
            }
            $flags = array_merge($parsed['flags'] ?? [], [
                'years_experience' => $parsed['years_experience'],
                'education' => $parsed['education'],
            ]);
            $candidate->update(['profile_flags' => $flags]);

            $candidates[$key] = $candidate;
        }
        return $candidates;
    }

    /**
     * Demonstrated evidence: who took the skills check and what they proved.
     * Baraka is the hidden gem - bad CV, outstanding assessment.
     * Neema takes none - her claims stay unverified (the counter-example).
     */
    private function seedEvidence(array $candidates): void
    {
        $demonstrated = [
            'baraka' => ['Excel' => 92, 'Data Analysis' => 85, 'Reporting' => 88, 'Communication' => 80, 'Customer Service' => 74, 'Attention to Detail' => 90, 'Problem Solving' => 78, 'Statistics' => 70, 'Microsoft Office' => 76],
            'david' => ['PHP' => 85, 'JavaScript' => 78, 'SQL' => 80, 'HTML' => 75, 'CSS' => 72, 'Git' => 70, 'Problem Solving' => 82, 'Communication' => 68],
            'juma' => ['Communication' => 90, 'Customer Service' => 82, 'Problem Solving' => 85, 'Conflict Resolution' => 78, 'Public Speaking' => 72, 'Microsoft Office' => 66],
            'emmanuel' => ['Excel' => 88, 'Bookkeeping' => 80, 'Invoicing' => 82, 'Data Analysis' => 75, 'Record Keeping' => 84, 'Inventory Management' => 81, 'Driving' => 90, 'Logistics' => 78, 'Customer Service' => 76],
            'asha' => ['Excel' => 70, 'Data Analysis' => 65, 'Reporting' => 60, 'Communication' => 85, 'Record Keeping' => 72, 'Teamwork' => 80],
            'hassan' => ['Administration' => 84, 'Record Keeping' => 88, 'Microsoft Office' => 80, 'Communication' => 82, 'CRM' => 74, 'Secretarial' => 78],
            'rehema' => ['Communication' => 88, 'Customer Service' => 80, 'Sales' => 85, 'Negotiation' => 76, 'Social Media Marketing' => 60, 'Content Writing' => 55, 'Excel' => 58],
        ];

        $skillMap = Skill::pluck('id', 'name');
        foreach ($demonstrated as $key => $scores) {
            $c = $candidates[$key];
            foreach ($scores as $skill => $score) {
                $sid = $skillMap[$skill] ?? null;
                if (! $sid) continue;
                $c->skills()->updateOrCreate(
                    ['skill_id' => $sid],
                    [
                        'demonstrated_score' => $score,
                        'demonstrated_at' => now()->subDays(rand(2, 9)),
                        'claimed_years' => $c->skills()->where('skill_id', $sid)->value('claimed_years'),
                        'claimed_evidence' => $c->skills()->where('skill_id', $sid)->value('claimed_evidence'),
                    ]
                );
            }
            $overall = (int) round(array_sum($scores) / count($scores));
            $skillScores = [];
            foreach ($scores as $skill => $score) {
                if ($sid = $skillMap[$skill] ?? null) {
                    $skillScores[(string) $sid] = $score;
                }
            }
            AssessmentAttempt::create([
                'candidate_id' => $c->id,
                'overall_score' => $overall,
                'skill_scores' => $skillScores,
                'answers' => [],
                'completed_at' => now()->subDays(rand(2, 9)),
            ]);
        }
    }

    private function seedPipeline(array $candidates, array $jobs): void
    {
        $apps = [
            // candidate, job, status, recruiter notes
            [$candidates['baraka'], $jobs['dev'], 'applied'],
            [$candidates['neema'], $jobs['dev'], 'screening'],
            [$candidates['juma'], $jobs['csr'], 'interview'],
            [$candidates['david'], $jobs['dev'], 'shortlisted'],
            [$candidates['grace'], $jobs['accounts_asst'], 'interview'],
            [$candidates['emmanuel'], $jobs['driver'], 'applied'],
            [$candidates['asha'], $jobs['analyst'], 'applied'],
            [$candidates['hassan'], $jobs['admin'], 'shortlisted'],
            [$candidates['rehema'], $jobs['marketing'], 'screening'],
        ];

        foreach ($apps as [$candidate, $job, $status]) {
            $app = Application::updateOrCreate(
                ['candidate_id' => $candidate->id, 'job_id' => $job->id],
                ['status' => $status]
            );

            // a few realistic recruiter decisions + interviews to fill the audit trail
            match (true) {
                $candidate->id === $candidates['juma']->id => [
                    Decision::create(['application_id' => $app->id, 'candidate_id' => $candidate->id, 'job_id' => $job->id, 'action' => 'shortlist', 'reason' => 'Strong communication and conflict resolution - worth interviewing', 'recruiter' => 'donswift']),
                    Interview::create(['application_id' => $app->id, 'type' => 'phone', 'scheduled_at' => now()->addDay(), 'notes' => 'Phone screen']),
                ],
                $candidate->id === $candidates['grace']->id => [
                    Decision::create(['application_id' => $app->id, 'candidate_id' => $candidate->id, 'job_id' => $job->id, 'action' => 'shortlist', 'reason' => 'Possibly overqualified for assistant role - check Senior Accountant fit', 'recruiter' => 'donswift']),
                    Interview::create(['application_id' => $app->id, 'type' => 'physical', 'scheduled_at' => now()->addDays(3), 'notes' => 'Panel interview']),
                ],
                $candidate->id === $candidates['hassan']->id => Decision::create(['application_id' => $app->id, 'candidate_id' => $candidate->id, 'job_id' => $job->id, 'action' => 'shortlist', 'reason' => 'Great admin background - gap does not change our judgement', 'recruiter' => 'donswift']),
                $candidate->id === $candidates['neema']->id => Decision::create(['application_id' => $app->id, 'candidate_id' => $candidate->id, 'job_id' => $job->id, 'action' => 'assessment_sent', 'reason' => 'CV claims exceed evidence - sent skills check', 'recruiter' => 'donswift']),
                default => null,
            };
        }
    }
}
