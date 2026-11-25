# SecondLook - where technology finds the talent that paper hides

> **Team Quantum Coders** - WITU Career Expo Hackathon
> Challenge: **The Talent Paradox** by Donswift Company Ltd

**The problem:** Donswift receives 500+ applications a week and ~45 phone inquiries a day. The best candidate often has the worst CV, keyword filters quietly delete great people, and recruiters drown in administration while the judgement that makes recruitment valuable goes unused.

**Our answer:** SecondLook is an intelligent recruitment ecosystem where **AI does everything except decide**. It reads CVs for claims, proves skill through short skills checks, explains every match, and surfaces the people a CV filter would delete - while a human recruiter always makes the final call.

---

## How it answers the challenge

| Dimension | How SecondLook answers it |
|---|---|
| **Talent discovery** | Candidates take short scenario-based **skills checks** that produce *demonstrated* evidence alongside *claimed* (CV) evidence. Baraka Mushi - whose CV says "various jobs" - scores 92 on Excel and 85 on Data Analysis, so the engine flags him as a **hidden gem** worth a conversation. |
| **Competency assessment** | Demonstrated skill always outweighs a claim. A candidate who says "5 years React" but takes no assessment is flagged `claims_unverified` - recommend a skills check *before* interview, not a rejection, and never just trust keywords. |
| **Intelligent matching** | Every candidate is scored against **every** open vacancy, not just the one applied for. Baraka applied as a developer (10/100); the engine surfaces him for Data Analyst (68/100) and tells the recruiter exactly why - `better_fit_elsewhere`. |
| **Human intelligence** | Scores are *advisory*, never decisions. Filament shows "Why this score?" breakdowns (skills/experience/education/potential) and every recruiter action - advance, reject, note - is recorded in a decisions log. Recruiter choices nudge the engine's weights through an inspectable learning loop. |
| **Candidate experience** | A private portal shows live status, honest "why you match" feedback with gaps, suggested roles, and instant answers to routine questions - the 45 daily phone calls become self-service. Candidates feel **discovered**, not processed. |
| **Fairness & inclusion** | Career gaps, unconventional paths and CV formatting never count against a candidate. `career_gap` is recorded but never penalised; `hidden_gem` and `transferable_skills` are actively surfaced. |

## The demo story (90 seconds)

1. **Recruiter view (Filament, `/admin`):** pipeline shows David (87) vs Neema (54, `claims_unverified`) - same role, different evidence.
2. **Baraka's application:** scores 10/100 for Junior Developer... click **"Why this score?"** → the engine found he is a **hidden gem** for Data Analyst (68).
3. **Candidate portal:** Baraka sees his demonstrated skills, honest gap feedback, and other roles he fits - he feels seen, not filtered out.
4. **Skills check:** take a live assessment and watch the matches recalculate in real time.

## Architecture

```
backend/   Laravel 13 + Filament 5 admin + matching engine + REST API (MySQL)
frontend/  Next.js 16 candidate portal (job board, apply, skills check, dashboard)
```

**Engine:** deterministic + explainable (app/Services/):
- `SkillTaxonomy` - ~80 skills across 9 categories with aliases
- `CvParser` - extracts *claimed* skills, years, education, profile signals (career changer, self-taught, employment gap)
- `JobParser` - turns a job description into weighted required/preferred competencies
- `MatchEngine` - scored breakdown (skills 55 / experience 15 / education 10 / potential 20), signals, human-readable explanations, cross-vacancy matching, and a learning loop from recruiter decisions
- `AssessmentService` - skills check grading → demonstrated evidence

## Run it

```bash
# Backend (Laravel + Filament)
cd backend
composer install
cp .env.example .env   # set DB_DATABASE=secondlook (mysql)
php artisan migrate:fresh --seed
php artisan serve --port=8500

# Frontend (Next.js)
cd frontend
npm install
echo "NEXT_PUBLIC_API_URL=http://127.0.0.1:8500/api" > .env.local
npm run dev
```

- **Recruiter admin:** http://localhost:8500/admin - `admin@secondlook.test` / `password`
- **Candidate portal:** http://localhost:3000
- **API:** http://localhost:8500/api/jobs

## Demo cast

| Candidate | The story they tell |
|---|---|
| **Baraka Mushi** | Hidden gem: bad CV, 92/100 Excel. Applied for dev, surfaced for Data Analyst. |
| **Neema Kimaro** | Polished keyword CV, zero proof: flagged claims-unverified. |
| **Juma Abdallah** | Teacher to customer service: transferable skills, career-changer signal. |
| **Emmanuel Shirima** | Delivery driver who demonstrated bookkeeping - cross-matched to Accounts. |
| **Hassan Mwangonde** | Career break, still an 82 match - gaps never penalised. |
| **Grace Lyimo** | Overqualified for assistant role - engine nudges her to Senior Accountant. |

## What is deliberately NOT automated

Character, culture fit, contextual judgement, interviews, final decisions. Technology removes the *administration* (500 CVs → ranked, explained shortlists; routine calls → instant answers) so recruiters spend their time on the judgement only humans can make.
