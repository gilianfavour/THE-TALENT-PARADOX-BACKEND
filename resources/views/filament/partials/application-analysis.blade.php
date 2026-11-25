@php
    $snap = $record->match_snapshot ?? [];
    $breakdown = $snap['breakdown'] ?? [];
    $signals = $snap['signals'] ?? [];
    $explanations = $snap['explanations'] ?? [];
    $skillEvidence = $snap['skill_evidence'] ?? [];
    $betterFit = $snap['better_fit'] ?? null;
@endphp

<div class="space-y-5 text-sm">
    <div class="flex items-center gap-3">
        <div class="text-3xl font-bold">{{ $record->ai_score ?? '-' }}<span class="text-lg text-gray-400">/100</span></div>
        <div class="text-gray-500">AI advisory score - your judgement is final.</div>
    </div>

    <div>
        <h4 class="font-semibold mb-1">Score breakdown</h4>
        <div class="grid grid-cols-4 gap-2 text-center">
            <div class="rounded bg-gray-50 dark:bg-gray-800 p-2"><div class="font-bold">{{ $breakdown['skills'] ?? 0 }}</div><div class="text-xs text-gray-400">Skills</div></div>
            <div class="rounded bg-gray-50 dark:bg-gray-800 p-2"><div class="font-bold">{{ $breakdown['experience'] ?? 0 }}</div><div class="text-xs text-gray-400">Experience</div></div>
            <div class="rounded bg-gray-50 dark:bg-gray-800 p-2"><div class="font-bold">{{ $breakdown['education'] ?? 0 }}</div><div class="text-xs text-gray-400">Education</div></div>
            <div class="rounded bg-gray-50 dark:bg-gray-800 p-2"><div class="font-bold">{{ $breakdown['potential'] ?? 0 }}</div><div class="text-xs text-gray-400">Potential</div></div>
        </div>
    </div>

    @if($signals)
    <div>
        <h4 class="font-semibold mb-1">Signals</h4>
        <div class="flex flex-wrap gap-1">
            @foreach($signals as $s)
                <span class="rounded bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-200 px-2 py-0.5 text-xs">{{ str_replace('_', ' ', $s) }}</span>
            @endforeach
        </div>
    </div>
    @endif

    @if($betterFit)
    <div class="rounded border border-amber-300 bg-amber-50 dark:bg-amber-950 p-3">
        <strong>Stronger fit elsewhere:</strong> {{ $betterFit['title'] }} ({{ $betterFit['score'] }}/100) at {{ $betterFit['client'] }} - consider suggesting this role to the candidate.
    </div>
    @endif

    <div>
        <h4 class="font-semibold mb-1">Why this score</h4>
        <ul class="list-disc pl-5 space-y-0.5">
            @foreach($explanations as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>

    @if($skillEvidence)
    <div>
        <h4 class="font-semibold mb-1">Skill evidence vs requirements</h4>
        <table class="w-full text-left border border-gray-200 dark:border-gray-700 rounded">
            <thead class="bg-gray-50 dark:bg-gray-800"><tr><th class="px-3 py-1">Skill</th><th class="px-3 py-1">Importance</th><th class="px-3 py-1">Evidence</th></tr></thead>
            <tbody>
            @foreach($skillEvidence as $s)
                <tr class="border-t border-gray-100 dark:border-gray-700">
                    <td class="px-3 py-1">{{ $s['name'] }}</td>
                    <td class="px-3 py-1">{{ $s['importance'] }}</td>
                    <td class="px-3 py-1 text-gray-500">{{ $s['note'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
