<div class="tb-banner">
    <div class="tb-banner-top">
        <div>
            <p class="tb-banner-date">{{ $date }}</p>
            <h2 class="tb-banner-title">{{ $greeting }}, {{ $name }}</h2>
            <p class="tb-banner-sub">AI ranks, explains and rediscovers. You make every decision.</p>
        </div>
        <div class="tb-banner-actions">
            <a href="{{ $newVacancyUrl }}" class="tb-btn tb-btn-red">New vacancy</a>
            <a href="{{ $tasks[0]['url'] }}" class="tb-btn tb-btn-ghost">Review applications</a>
        </div>
    </div>

    <div class="tb-tasks">
        @foreach ($tasks as $task)
            <a href="{{ $task['url'] }}" class="tb-task">
                <span class="tb-task-count {{ $task['count'] > 0 ? 'tb-' . $task['tone'] : 'tb-zero' }}">{{ $task['count'] }}</span>
                <span class="tb-task-label">{{ $task['label'] }}</span>
            </a>
        @endforeach
    </div>

    @if ($total > 0)
        <div class="tb-pipeline">
            <p class="tb-pipeline-title">Pipeline by stage <span>{{ $total }} applications</span></p>
            <div class="tb-pipeline-bar">
                @foreach ($pipeline as $i => $stage)
                    <span style="flex: {{ $stage['count'] }}; opacity: {{ max(0.35, 1 - $i * 0.08) }}" title="{{ $stage['label'] }}: {{ $stage['count'] }}"></span>
                @endforeach
            </div>
            <div class="tb-pipeline-legend">
                @foreach ($pipeline as $stage)
                    <span class="tb-pill">{{ $stage['label'] }} <b>{{ $stage['count'] }}</b></span>
                @endforeach
            </div>
        </div>
    @endif
</div>
