<?php

namespace App\Services;

use App\Models\Job;

/**
 * Vacancy-specific practical tasks. Each vacancy gets a task built around the real work
 * of the role (debug code, reconcile accounts, answer a complaint...), scored against a
 * predefined rubric. Scores are preliminary until a recruiter validates them.
 */
class PracticalTaskLibrary
{
    /** keyword => template slug, checked against the vacancy title in order */
    private const ROLE_MAP = [
        'software' => 'dev-debug-login', 'developer' => 'dev-debug-login', 'programmer' => 'dev-debug-login',
        'data' => 'data-sales-growth', 'analyst' => 'data-sales-growth',
        'senior accountant' => 'accounts-reconciliation', 'account' => 'accounts-reconciliation', 'finance' => 'accounts-reconciliation',
        'customer' => 'cs-double-charge', 'care' => 'cs-double-charge', 'call' => 'cs-double-charge',
        'project' => 'project-activity-plan',
        'admin' => 'admin-prioritise', 'assistant' => 'admin-prioritise', 'office' => 'admin-prioritise', 'secretary' => 'admin-prioritise',
        'marketing' => 'marketing-launch', 'communication' => 'marketing-launch', 'brand' => 'marketing-launch',
        'driver' => 'logistics-route', 'delivery' => 'logistics-route', 'logistics' => 'logistics-route',
    ];

    public function all(): array
    {
        return [
            'dev-debug-login' => [
                'title' => 'Debug a broken login check',
                'role' => 'Software development',
                'minutes' => 20,
                'skills' => ['JavaScript', 'PHP', 'Problem Solving', 'SQL'],
                'brief' => 'A teammate wrote this login check and users report that any password works. Find every problem, write a corrected version, and explain how you would test it.',
                'scenario' => "function login(user, input) {\n  const row = db.query(\"SELECT * FROM users WHERE email = '\" + user + \"'\");\n  if (row.password = input) {\n    return true;\n  }\n  return false;\n}",
                'rubric' => [
                    ['key' => 'bug', 'label' => 'Finds the assignment bug', 'weight' => 30, 'need' => 1, 'signals' => ['assignment', '===', '==', 'single =', 'assigns', 'comparison', 'compare'], 'tip' => 'Point out that = assigns instead of comparing.'],
                    ['key' => 'security', 'label' => 'Spots the security risks', 'weight' => 30, 'need' => 2, 'signals' => ['sql injection', 'injection', 'prepared', 'parameter', 'placeholder', 'hash', 'bcrypt', 'password_verify', 'plain text', 'plaintext'], 'tip' => 'Mention SQL injection and that passwords must be hashed, not compared in plain text.'],
                    ['key' => 'fix', 'label' => 'Writes a working fix', 'weight' => 25, 'need' => 2, 'signals' => ['function', 'return', 'if (', 'verify', '?', 'await', 'null', '!row', 'if (!'], 'tip' => 'Include corrected code, not only a description.'],
                    ['key' => 'testing', 'label' => 'Explains how to test it', 'weight' => 15, 'need' => 1, 'signals' => ['test', 'wrong password', 'empty', 'edge case', 'unit', 'assert'], 'tip' => 'Describe at least one test, for example a wrong password must fail.'],
                ],
            ],
            'data-sales-growth' => [
                'title' => 'Analyse a small sales dataset',
                'role' => 'Data analysis',
                'minutes' => 15,
                'skills' => ['Data Analysis', 'Excel', 'Reporting', 'Attention to Detail'],
                'brief' => 'Using the quarterly sales below, say which region grew fastest (with the percentage), what the total growth was, and one recommendation for management.',
                'scenario' => "Region   | Q1 sales (UGX m) | Q2 sales (UGX m)\nKampala  | 120              | 150\nJinja    | 80               | 112\nGulu     | 60               | 63\nTotal    | 260              | 325",
                'rubric' => [
                    ['key' => 'answer', 'label' => 'Correct fastest-growing region', 'weight' => 35, 'need' => 2, 'signals' => ['jinja', '40%', '40 %', '40 percent'], 'tip' => 'Jinja grew from 80 to 112, which is 40%.'],
                    ['key' => 'method', 'label' => 'Shows the calculation', 'weight' => 25, 'need' => 1, 'signals' => ['(112', '112-80', '112 - 80', '/80', '/ 80', 'percentage', 'growth rate', '25%', '5%'], 'tip' => 'Show how you calculated growth: (Q2 - Q1) / Q1.'],
                    ['key' => 'total', 'label' => 'Total growth', 'weight' => 15, 'need' => 1, 'signals' => ['25%', '65', '325'], 'tip' => 'Total went from 260 to 325, a 25% increase.'],
                    ['key' => 'insight', 'label' => 'Useful recommendation', 'weight' => 25, 'need' => 1, 'signals' => ['recommend', 'should', 'invest', 'investigate', 'gulu', 'focus', 'suggest'], 'tip' => 'Give management one concrete action, for example investigate Gulu.'],
                ],
            ],
            'accounts-reconciliation' => [
                'title' => 'Bank reconciliation exercise',
                'role' => 'Accounting',
                'minutes' => 20,
                'skills' => ['Accounting', 'Bookkeeping', 'Excel', 'Attention to Detail'],
                'brief' => 'Prepare a bank reconciliation from the figures below and state the correct reconciled balance. List each adjustment.',
                'scenario' => "Cash book balance: UGX 2,450,000\nBank statement balance: UGX 2,295,000\nCheque issued, not yet presented: UGX 260,000\nDeposit recorded in cash book, not yet credited by bank: UGX 400,000\nBank charges on statement, not in cash book: UGX 15,000",
                'rubric' => [
                    ['key' => 'balance', 'label' => 'Correct reconciled balance', 'weight' => 40, 'need' => 1, 'signals' => ['2,435,000', '2435000', '2 435 000', '2.435'], 'tip' => 'Both sides reconcile to UGX 2,435,000.'],
                    ['key' => 'bank_side', 'label' => 'Adjusts the bank side', 'weight' => 20, 'need' => 2, 'signals' => ['unpresented', 'not yet presented', 'outstanding cheque', 'deposit', 'in transit', 'uncredited'], 'tip' => 'Add the uncredited deposit and subtract the unpresented cheque from the bank balance.'],
                    ['key' => 'book_side', 'label' => 'Adjusts the cash book', 'weight' => 20, 'need' => 1, 'signals' => ['bank charges', 'charges', '15,000', '15000'], 'tip' => 'Deduct the bank charges from the cash book.'],
                    ['key' => 'layout', 'label' => 'Clear, ordered working', 'weight' => 20, 'need' => 2, 'signals' => ['add', 'less', 'subtract', 'adjusted', 'balance per', '+', '-'], 'tip' => 'Lay out the working line by line.'],
                ],
            ],
            'cs-double-charge' => [
                'title' => 'Reply to an upset customer',
                'role' => 'Customer service',
                'minutes' => 10,
                'skills' => ['Customer Service', 'Complaint Resolution', 'Communication'],
                'brief' => 'Write the reply you would send to this customer. Follow our refund procedure: confirm the order reference, explain the next step and give a timeframe.',
                'scenario' => "\"I was charged TWICE for order #48213 and nobody answers the phone. This is ridiculous. I want my money back today or I am never buying from you again.\" - Sarah",
                'rubric' => [
                    ['key' => 'empathy', 'label' => 'Acknowledges and apologises', 'weight' => 25, 'need' => 1, 'signals' => ['sorry', 'apolog', 'understand', 'frustrat', 'inconvenience'], 'tip' => 'Open by acknowledging her frustration and apologising.'],
                    ['key' => 'resolution', 'label' => 'Resolves the problem', 'weight' => 30, 'need' => 2, 'signals' => ['refund', 'reverse', 'duplicate', 'second charge', 'credited', 'return the'], 'tip' => 'Say clearly that the duplicate charge will be refunded.'],
                    ['key' => 'procedure', 'label' => 'Follows procedure', 'weight' => 25, 'need' => 2, 'signals' => ['48213', 'order', 'reference', 'confirm', 'within', 'days', 'hours', 'working day'], 'tip' => 'Quote the order reference and give a realistic timeframe.'],
                    ['key' => 'professional', 'label' => 'Professional tone', 'weight' => 20, 'need' => 2, 'signals' => ['dear', 'hi sarah', 'hello', 'regards', 'thank you', 'contact', 'directly'], 'tip' => 'Greet, thank and sign off politely, and offer a direct contact.'],
                ],
            ],
            'project-activity-plan' => [
                'title' => 'Plan a community project activity',
                'role' => 'Project support',
                'minutes' => 20,
                'skills' => ['Project Management', 'Organization', 'Communication', 'Reporting'],
                'brief' => 'Draft a simple activity plan for a one-day community health outreach in 4 weeks. Include activities, who does what, a timeline, the budget items you would track, and one risk with how you would handle it.',
                'scenario' => "Team: 1 nurse, 3 volunteers, 1 driver\nBudget: UGX 1,500,000\nGoal: screen 200 people and record every visit",
                'rubric' => [
                    ['key' => 'activities', 'label' => 'Clear activities and timeline', 'weight' => 30, 'need' => 2, 'signals' => ['week 1', 'week 2', 'week', 'day', 'timeline', 'schedule', 'deadline'], 'tip' => 'Break the 4 weeks into steps with dates.'],
                    ['key' => 'roles', 'label' => 'Assigns responsibilities', 'weight' => 20, 'need' => 2, 'signals' => ['nurse', 'volunteer', 'driver', 'responsible', 'owner', 'lead'], 'tip' => 'Say who does each task.'],
                    ['key' => 'budget', 'label' => 'Tracks budget and records', 'weight' => 25, 'need' => 2, 'signals' => ['budget', 'transport', 'cost', 'record', 'register', 'spreadsheet', 'receipts', 'report'], 'tip' => 'List budget lines and how visits will be recorded.'],
                    ['key' => 'risk', 'label' => 'Identifies a risk', 'weight' => 25, 'need' => 1, 'signals' => ['risk', 'rain', 'weather', 'backup', 'contingency', 'if ', 'delay'], 'tip' => 'Name a risk and a backup plan.'],
                ],
            ],
            'admin-prioritise' => [
                'title' => 'Organise records and prioritise tasks',
                'role' => 'Administration',
                'minutes' => 15,
                'skills' => ['Administration', 'Record Keeping', 'Organization', 'Time Management'],
                'brief' => 'It is 8:00 on Monday. Put these tasks in order, say why, and explain how you would file and track them so nothing is lost.',
                'scenario' => "1. Director needs board pack printed for 10:00 meeting\n2. 40 unfiled patient registration forms from Friday\n3. Supplier invoice due today\n4. Reply to 12 routine emails\n5. Book transport for Wednesday training\n6. Update the staff leave register",
                'rubric' => [
                    ['key' => 'priority', 'label' => 'Sensible priority order', 'weight' => 35, 'need' => 2, 'signals' => ['board pack', 'first', '10:00', 'urgent', 'invoice', 'deadline', 'today'], 'tip' => 'Deadline-driven items (board pack, invoice) come first.'],
                    ['key' => 'reasoning', 'label' => 'Explains the reasoning', 'weight' => 20, 'need' => 1, 'signals' => ['because', 'since', 'so that', 'deadline', 'impact'], 'tip' => 'Say why each item sits where it does.'],
                    ['key' => 'records', 'label' => 'Record keeping method', 'weight' => 30, 'need' => 2, 'signals' => ['file', 'folder', 'label', 'register', 'log', 'spreadsheet', 'alphabetical', 'date order', 'scan'], 'tip' => 'Describe how forms are filed and logged.'],
                    ['key' => 'follow', 'label' => 'Follow-up and communication', 'weight' => 15, 'need' => 1, 'signals' => ['remind', 'confirm', 'inform', 'update', 'calendar', 'checklist'], 'tip' => 'Mention confirmations, reminders or a checklist.'],
                ],
            ],
            'marketing-launch' => [
                'title' => 'Brief for a product launch post',
                'role' => 'Marketing',
                'minutes' => 15,
                'skills' => ['Content Writing', 'Social Media Marketing', 'Digital Marketing', 'Market Research'],
                'brief' => 'Savanna Beverages is launching a new passion-fruit juice for students. Write the launch post and say who it targets, which channels you would use and how you would measure success.',
                'scenario' => "Budget: UGX 2,000,000 for 2 weeks\nAudience: university students in Kampala",
                'rubric' => [
                    ['key' => 'copy', 'label' => 'Engaging post copy', 'weight' => 30, 'need' => 2, 'signals' => ['#', 'new', 'try', 'taste', 'fresh', '!', 'now'], 'tip' => 'Write the actual post with a clear hook and call to action.'],
                    ['key' => 'audience', 'label' => 'Defines the audience', 'weight' => 20, 'need' => 1, 'signals' => ['student', 'campus', 'university', '18', 'young'], 'tip' => 'Name the audience precisely.'],
                    ['key' => 'channels', 'label' => 'Chooses channels', 'weight' => 25, 'need' => 2, 'signals' => ['tiktok', 'instagram', 'x ', 'twitter', 'whatsapp', 'influencer', 'campus activation', 'radio'], 'tip' => 'Pick channels students actually use.'],
                    ['key' => 'measure', 'label' => 'Measures success', 'weight' => 25, 'need' => 1, 'signals' => ['kpi', 'reach', 'engagement', 'sales', 'conversion', 'clicks', 'measure', 'track'], 'tip' => 'Say which numbers would show success.'],
                ],
            ],
            'logistics-route' => [
                'title' => 'Plan a delivery route',
                'role' => 'Logistics',
                'minutes' => 10,
                'skills' => ['Logistics', 'Time Management', 'Health & Safety', 'Driving'],
                'brief' => 'Plan the order of these deliveries, explain your checks before leaving, and what you would do if you were running late.',
                'scenario' => "Depot: Mwanza town\nA: Hospital (must arrive before 09:00)\nB: Supermarket (after 11:00)\nC: School (any time)\nD: Pharmacy (before 10:00)",
                'rubric' => [
                    ['key' => 'order', 'label' => 'Respects time windows', 'weight' => 35, 'need' => 2, 'signals' => ['hospital first', 'a first', 'a, d', 'a then d', 'pharmacy', 'supermarket last', 'b last', '09:00', '9:00'], 'tip' => 'Hospital, then pharmacy, then school, then supermarket.'],
                    ['key' => 'checks', 'label' => 'Pre-trip safety checks', 'weight' => 30, 'need' => 2, 'signals' => ['tyre', 'fuel', 'brake', 'oil', 'lights', 'load', 'secure', 'documents'], 'tip' => 'List vehicle and load checks.'],
                    ['key' => 'late', 'label' => 'Handles delays', 'weight' => 20, 'need' => 1, 'signals' => ['call', 'inform', 'notify', 'customer', 'dispatcher', 'supervisor'], 'tip' => 'Tell the customer and dispatcher early.'],
                    ['key' => 'safety', 'label' => 'Safe driving mindset', 'weight' => 15, 'need' => 1, 'signals' => ['speed', 'safe', 'rest', 'seat belt', 'never rush'], 'tip' => 'Never trade safety for time.'],
                ],
            ],
        ];
    }

    public function find(string $slug): ?array
    {
        $t = $this->all()[$slug] ?? null;

        return $t ? ['slug' => $slug] + $t : null;
    }

    public function slugForJob(Job $job): string
    {
        $title = mb_strtolower($job->title);
        foreach (self::ROLE_MAP as $keyword => $slug) {
            if (str_contains($title, $keyword)) {
                return $slug;
            }
        }

        return 'admin-prioritise';
    }

    public function forJob(Job $job): array
    {
        return $this->find($this->slugForJob($job));
    }

    /** Public view of a task: the rubric criteria are shown, the answer signals are not. */
    public function publicView(array $task): array
    {
        return [
            'slug' => $task['slug'],
            'title' => $task['title'],
            'role' => $task['role'],
            'minutes' => $task['minutes'],
            'skills' => $task['skills'],
            'brief' => $task['brief'],
            'scenario' => $task['scenario'],
            'criteria' => array_map(fn ($c) => ['label' => $c['label'], 'weight' => $c['weight']], $task['rubric']),
        ];
    }
}
