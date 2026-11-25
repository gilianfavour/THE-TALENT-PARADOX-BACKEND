@php
    $scores = app(\App\Services\MatchEngine::class)->scoreAllJobs($record);
    $skills = $record->skills()->with('skill')->get();
@endphp

<div class="space-y-6 text-sm">
    <div>
        <h4 class="font-semibold mb-2">Skill evidence</h4>
        <table class="w-full text-left border border-gray-200 dark:border-gray-700 rounded">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-3 py-1">Skill</th>
                    <th class="px-3 py-1">CV claim</th>
                    <th class="px-3 py-1">Demonstrated</th>
                </tr>
            </thead>
            <tbody>
            @forelse($skills as $cs)
                <tr class="border-t border-gray-100 dark:border-gray-700">
                    <td class="px-3 py-1">{{ $cs->skill?->name }}</td>
                    <td class="px-3 py-1 text-gray-500">
                        {{ $cs->claimed_years ? $cs->claimed_years . ' yrs' : ($cs->claimed_evidence ? 'claimed' : '-') }}
                    </td>
                    <td class="px-3 py-1">
                        @if($cs->demonstrated_score !== null)
                            <span class="font-semibold text-{{ $cs->demonstrated_score >= 70 ? 'green' : 'amber' }}-600">{{ $cs->demonstrated_score }}/100</span>
                        @else
                            <span class="text-gray-400">not assessed</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-3 py-2 text-gray-400">No skills parsed yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div>
        <h4 class="font-semibold mb-2">Fit across all open vacancies</h4>
        <table class="w-full text-left border border-gray-200 dark:border-gray-700 rounded">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-3 py-1">Vacancy</th>
                    <th class="px-3 py-1">Score</th>
                    <th class="px-3 py-1">Signals</th>
                </tr>
            </thead>
            <tbody>
            @foreach($scores as $row)
                <tr class="border-t border-gray-100 dark:border-gray-700">
                    <td class="px-3 py-1">{{ $row['title'] }} <span class="text-gray-400">- {{ $row['client'] }}</span></td>
                    <td class="px-3 py-1 font-semibold">{{ $row['score'] }}/100</td>
                    <td class="px-3 py-1 text-gray-500">{{ implode(', ', $row['signals']) ?: '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="text-xs text-gray-400 mt-2">Applied to one role but stronger elsewhere? The engine says so here, with reasons.</p>
    </div>
</div>
