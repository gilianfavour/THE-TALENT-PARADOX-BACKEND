# SecondLook - Donswift (Backend)

Recruitment platform backend that looks past the CV: candidates prove skills
through short assessments, and the matching engine surfaces "hidden gems" a
CV screen would miss. Built with Laravel 13 + Filament 5.

## Requirements

- PHP 8.4+, Composer
- MySQL (e.g. XAMPP)

## Setup

```bash
composer install
cp .env.example .env   # set DB_* values
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Admin panel: `http://localhost:8000/admin` (root `/` redirects to the login page).

## Seeders

A single `DatabaseSeeder` (`php artisan db:seed`, or `migrate:fresh --seed`)
builds the full demo dataset. Everything is `updateOrCreate`, so re-seeding
is safe.

| Data | Details |
| --- | --- |
| Admin user | `admin@secondlook.test` / `password` (Filament login) |
| Skills | Full `SkillTaxonomy` map |
| Assessment questions | 30 situational questions across skills |
| Jobs | 8 demo vacancies across Tanzanian clients, parsed by `JobParser` |
| Candidates | 9 realistic CVs, parsed by `CvParser` into claimed skills + profile flags |
| Assessment attempts | Demonstrated scores for 7 candidates |
| Pipeline | 9 applications across stages, with recruiter `Decision`s and scheduled `Interview`s |
| Match scores | `MatchEngine::refreshAll()` computes `ai_score` + `match_snapshot` |

### Demo story baked into the data

- **Baraka Mushi** — the hidden gem: weak CV, outstanding assessment scores.
- **Neema Kimaro** — the counter-example: polished CV, zero demonstrated evidence.
- Other candidates cover career changers, career gaps, and fresh graduates so
  the dashboard widgets (Hidden Gems, Deadline Watch, Stats Overview) are populated.

## API

Public candidate-portal endpoints live in `routes/api.php` (jobs list, apply,
assessment, help).
