<?php

namespace App\Services;

/**
 * Canonical skill taxonomy. Every CV and job description is parsed
 * against this list, so "claims" and "demonstrated" skills share
 * one vocabulary - the foundation of explainable matching.
 */
class SkillTaxonomy
{
    public const CATEGORIES = [
        'Technology', 'Data & Analytics', 'Finance', 'Business',
        'Marketing', 'Customer Service', 'Operations', 'Soft Skills', 'Languages',
    ];

    public static function all(): array
    {
        return [
            // Technology
            ['name' => 'PHP', 'category' => 'Technology', 'aliases' => ['php programming', 'php developer', 'php scripts']],
            ['name' => 'Laravel', 'category' => 'Technology', 'aliases' => ['laravel framework', 'laravel php']],
            ['name' => 'JavaScript', 'category' => 'Technology', 'aliases' => ['js', 'ecmascript', 'vanilla js']],
            ['name' => 'TypeScript', 'category' => 'Technology', 'aliases' => ['ts']],
            ['name' => 'React', 'category' => 'Technology', 'aliases' => ['reactjs', 'react.js', 'react js']],
            ['name' => 'Next.js', 'category' => 'Technology', 'aliases' => ['nextjs', 'next js']],
            ['name' => 'Node.js', 'category' => 'Technology', 'aliases' => ['nodejs', 'node js']],
            ['name' => 'Python', 'category' => 'Technology', 'aliases' => ['python programming', 'python3', 'python 3']],
            ['name' => 'HTML', 'category' => 'Technology', 'aliases' => ['html5', 'html css']],
            ['name' => 'CSS', 'category' => 'Technology', 'aliases' => ['css3', 'tailwind', 'bootstrap css']],
            ['name' => 'SQL', 'category' => 'Technology', 'aliases' => ['structured query language', 'mysql queries', 'sql database', 'plsql']],
            ['name' => 'MySQL', 'category' => 'Technology', 'aliases' => ['mysql database', 'mariadb']],
            ['name' => 'Git', 'category' => 'Technology', 'aliases' => ['git version control', 'github', 'gitlab', 'version control']],
            ['name' => 'REST APIs', 'category' => 'Technology', 'aliases' => ['rest api', 'api integration', 'apis', 'restful']],
            ['name' => 'Cloud', 'category' => 'Technology', 'aliases' => ['aws', 'azure', 'gcp', 'cloud computing', 'amazon web services']],
            ['name' => 'Docker', 'category' => 'Technology', 'aliases' => ['docker containers', 'containerization', 'kubernetes', 'k8s']],
            ['name' => 'Figma', 'category' => 'Technology', 'aliases' => ['figma design', 'adobe xd', 'sketch']],
            ['name' => 'UI/UX Design', 'category' => 'Technology', 'aliases' => ['ui design', 'ux design', 'user experience', 'user interface', 'wireframing', 'prototyping']],
            ['name' => 'Cybersecurity', 'category' => 'Technology', 'aliases' => ['information security', 'network security', 'it security']],
            ['name' => 'Excel', 'category' => 'Data & Analytics', 'aliases' => ['microsoft excel', 'ms excel', 'excel spreadsheet', 'spreadsheet', 'advanced excel', 'excel vlookup', 'pivot table', 'vlookup']],
            ['name' => 'Data Analysis', 'category' => 'Data & Analytics', 'aliases' => ['data analytics', 'analysing data', 'analyzing data', 'data analyst', 'business intelligence', 'bi reporting']],
            ['name' => 'Statistics', 'category' => 'Data & Analytics', 'aliases' => ['statistical analysis', 'stats', 'hypothesis testing']],
            ['name' => 'Reporting', 'category' => 'Data & Analytics', 'aliases' => ['report writing', 'management reports', 'dashboards', 'kpi reporting', 'data reporting']],
            ['name' => 'Power BI', 'category' => 'Data & Analytics', 'aliases' => ['powerbi', 'power bi', 'tableau', 'data visualization', 'visualization']],
            ['name' => 'Accounting', 'category' => 'Finance', 'aliases' => ['accountancy', 'financial accounting', 'book of accounts', 'general ledger', 'accrual accounting']],
            ['name' => 'Bookkeeping', 'category' => 'Finance', 'aliases' => ['book-keeping', 'books of account', 'double entry', 'bank reconciliation', 'reconciliation']],
            ['name' => 'Payroll', 'category' => 'Finance', 'aliases' => ['payroll processing', 'salary processing', 'wage processing', 'paye']],
            ['name' => 'Auditing', 'category' => 'Finance', 'aliases' => ['audit', 'internal audit', 'financial audit']],
            ['name' => 'Budgeting', 'category' => 'Finance', 'aliases' => ['budget management', 'budgeting and forecasting', 'financial planning']],
            ['name' => 'Invoicing', 'category' => 'Finance', 'aliases' => ['invoice processing', 'billing', 'accounts receivable', 'accounts payable', 'ar ap']],
            ['name' => 'Tax', 'category' => 'Finance', 'aliases' => ['taxation', 'vat', 'tax returns', 'tra returns']],
            ['name' => 'Project Management', 'category' => 'Business', 'aliases' => ['project manager', 'managing projects', 'pmp', 'agile', 'scrum', 'project planning', 'project coordination']],
            ['name' => 'Microsoft Office', 'category' => 'Business', 'aliases' => ['ms office', 'microsoft word', 'word processing', 'ms powerpoint', 'powerpoint', 'outlook', 'office suite']],
            ['name' => 'CRM', 'category' => 'Business', 'aliases' => ['customer relationship management', 'salesforce', 'hubspot', 'zoho crm', 'crm software']],
            ['name' => 'ERP', 'category' => 'Business', 'aliases' => ['enterprise resource planning', 'sap', 'odoo', 'business management software']],
            ['name' => 'Inventory Management', 'category' => 'Business', 'aliases' => ['stock management', 'inventory control', 'stock control', 'warehouse inventory']],
            ['name' => 'Procurement', 'category' => 'Business', 'aliases' => ['purchasing', 'supply chain', 'vendor management', 'tendering']],
            ['name' => 'Administration', 'category' => 'Business', 'aliases' => ['office administration', 'admin', 'clerical', 'general admin', 'administrative support', 'front office']],
            ['name' => 'Secretarial', 'category' => 'Business', 'aliases' => ['secretary', 'executive assistant', 'personal assistant', 'pa to', 'diary management', 'meeting minutes']],
            ['name' => 'Record Keeping', 'category' => 'Business', 'aliases' => ['records management', 'filing', 'documentation', 'data entry', 'data capturing']],
            ['name' => 'Digital Marketing', 'category' => 'Marketing', 'aliases' => ['online marketing', 'digital campaigns', 'performance marketing', 'google ads', 'facebook ads', 'meta ads']],
            ['name' => 'Social Media Marketing', 'category' => 'Marketing', 'aliases' => ['social media', 'instagram marketing', 'tiktok', 'social media management', 'content calendar']],
            ['name' => 'Content Writing', 'category' => 'Marketing', 'aliases' => ['copywriting', 'article writing', 'blog writing', 'content creation', 'creative writing']],
            ['name' => 'SEO', 'category' => 'Marketing', 'aliases' => ['search engine optimization', 'seo specialist', 'keyword research', 'organic search']],
            ['name' => 'Graphic Design', 'category' => 'Marketing', 'aliases' => ['graphic designer', 'canva', 'adobe photoshop', 'photoshop', 'illustrator', 'visual design', 'branding design']],
            ['name' => 'Brand Management', 'category' => 'Marketing', 'aliases' => ['brand strategy', 'brand development', 'branding']],
            ['name' => 'Market Research', 'category' => 'Marketing', 'aliases' => ['market analysis', 'consumer research', 'surveys', 'questionnaires', 'focus groups']],
            ['name' => 'Email Marketing', 'category' => 'Marketing', 'aliases' => ['email campaigns', 'newsletter', 'mailchimp', 'campaign management']],
            ['name' => 'Customer Service', 'category' => 'Customer Service', 'aliases' => ['customer care', 'client service', 'customer support', 'client relations', 'customer experience', 'helpdesk', 'help desk']],
            ['name' => 'Call Handling', 'category' => 'Customer Service', 'aliases' => ['call centre', 'call center', 'telephone support', 'phone support', 'inbound calls', 'customer calls']],
            ['name' => 'Complaint Resolution', 'category' => 'Customer Service', 'aliases' => ['complaints handling', 'dispute resolution', 'handling complaints', 'grievance handling']],
            ['name' => 'Sales', 'category' => 'Customer Service', 'aliases' => ['sales executive', 'business development', 'selling', 'sales targets', 'field sales', 'retail sales']],
            ['name' => 'Negotiation', 'category' => 'Customer Service', 'aliases' => ['negotiating', 'deal making', 'contract negotiation']],
            ['name' => 'Driving', 'category' => 'Operations', 'aliases' => ['driver', 'driving licence', 'driving license', 'class b', 'class c', 'commercial driving', 'delivery driving']],
            ['name' => 'Logistics', 'category' => 'Operations', 'aliases' => ['supply chain logistics', 'distribution', 'transport coordination', 'fleet logistics']],
            ['name' => 'Fleet Management', 'category' => 'Operations', 'aliases' => ['fleet operations', 'vehicle management', 'transport management']],
            ['name' => 'Warehouse', 'category' => 'Operations', 'aliases' => ['warehouse operations', 'stockroom', 'stores management', 'goods receiving']],
            ['name' => 'Forklift', 'category' => 'Operations', 'aliases' => ['fork lift', 'reach truck', 'pallet jack']],
            ['name' => 'Health & Safety', 'category' => 'Operations', 'aliases' => ['occupational health', 'safety compliance', 'osha', 'risk assessment', 'incident reporting']],
            ['name' => 'First Aid', 'category' => 'Operations', 'aliases' => ['first aid certificate', 'cpr', 'emergency response']],
            ['name' => 'Time Management', 'category' => 'Soft Skills', 'aliases' => ['time management skills', 'prioritization', 'deadline management', 'organizing time']],
            ['name' => 'Communication', 'category' => 'Soft Skills', 'aliases' => ['communication skills', 'verbal communication', 'written communication', 'interpersonal communication', 'clear communication']],
            ['name' => 'Teamwork', 'category' => 'Soft Skills', 'aliases' => ['team player', 'team player spirit', 'collaboration', 'working in teams', 'cross-functional']],
            ['name' => 'Problem Solving', 'category' => 'Soft Skills', 'aliases' => ['problem-solving', 'analytical thinking', 'critical thinking', 'troubleshooting', 'root cause analysis']],
            ['name' => 'Leadership', 'category' => 'Soft Skills', 'aliases' => ['leading teams', 'team leadership', 'supervision', 'line management', 'mentoring staff']],
            ['name' => 'Attention to Detail', 'category' => 'Soft Skills', 'aliases' => ['detail oriented', 'detail-oriented', 'accuracy', 'precision', 'meticulous']],
            ['name' => 'Public Speaking', 'category' => 'Soft Skills', 'aliases' => ['presentations', 'presenting', 'presentation skills', 'public address', 'training delivery', 'facilitation']],
            ['name' => 'Adaptability', 'category' => 'Soft Skills', 'aliases' => ['flexibility', 'adaptable', 'resilience', 'change management']],
            ['name' => 'Work Ethic', 'category' => 'Soft Skills', 'aliases' => ['reliable', 'dependable', 'punctual', 'hardworking', 'dedicated', 'committed']],
            ['name' => 'Organization', 'category' => 'Soft Skills', 'aliases' => ['organisational skills', 'organizational skills', 'planning', 'coordination', 'multitasking', 'prioritising']],
            ['name' => 'Conflict Resolution', 'category' => 'Soft Skills', 'aliases' => ['mediation', 'de-escalation', 'resolving conflicts', 'handling difficult situations']],
            ['name' => 'Mentoring', 'category' => 'Soft Skills', 'aliases' => ['coaching', 'training juniors', 'staff development', 'onboarding', 'knowledge transfer']],
            ['name' => 'Recruitment', 'category' => 'Business', 'aliases' => ['recruiting', 'talent acquisition', 'hiring', 'staff recruitment', 'headhunting', 'interviewing candidates']],
            ['name' => 'HR', 'category' => 'Business', 'aliases' => ['human resources', 'hr management', 'people operations', 'employee relations', 'hr policies']],
            ['name' => 'Training', 'category' => 'Business', 'aliases' => ['training delivery', 'l&d', 'learning and development', 'workshops', 'capacity building', 'training of trainers']],
            ['name' => 'Teaching', 'category' => 'Business', 'aliases' => ['teaching experience', 'lesson planning', 'classroom', 'education', 'instruction', 'tutoring', 'curriculum']],
            ['name' => 'Swahili', 'category' => 'Languages', 'aliases' => ['kiswahili']],
            ['name' => 'English', 'category' => 'Languages', 'aliases' => ['fluent english', 'english language', 'written english']],
            ['name' => 'French', 'category' => 'Languages', 'aliases' => ['français']],
        ];
    }

    /** Soft skills that jobs often require but CVs rarely prove. */
    public static function softSkillNames(): array
    {
        return [
            'Communication', 'Teamwork', 'Problem Solving', 'Leadership',
            'Attention to Detail', 'Public Speaking', 'Adaptability',
            'Work Ethic', 'Organization', 'Conflict Resolution', 'Mentoring', 'Time Management',
        ];
    }
}
