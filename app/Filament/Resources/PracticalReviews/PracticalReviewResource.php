<?php

namespace App\Filament\Resources\PracticalReviews;

use App\Filament\Resources\PracticalReviews\Pages\ListPracticalReviews;
use App\Models\AssessmentAttempt;
use App\Services\PracticalAssessmentService;
use App\Services\PracticalTaskLibrary;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/** Recruiter review queue for practical task submissions (human validation of assessments). */
class PracticalReviewResource extends Resource
{
    protected static ?string $model = AssessmentAttempt::class;

    protected static ?string $slug = 'practical-reviews';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Practical task review';

    protected static ?string $modelLabel = 'practical submission';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['candidate', 'job'])->where('answers->type', 'practical');
    }

    public static function getNavigationBadge(): ?string
    {
        $n = AssessmentAttempt::where('answers->type', 'practical')->where('answers->status', 'needs_review')->count();

        return $n ? (string) $n : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function submissionHtml(AssessmentAttempt $record): HtmlString
    {
        $a = $record->answers ?? [];
        $task = app(PracticalTaskLibrary::class)->find($a['task'] ?? '') ?? [];
        $e = fn ($v) => e((string) $v);
        $rows = collect($a['criteria'] ?? [])->map(function ($c) use ($e) {
            $colour = ['shown' => '#15803d', 'partly' => '#b45309', 'not_shown' => '#b91c1c'][$c['status']] ?? '#52525b';
            $label = ['shown' => 'Shown', 'partly' => 'Partly', 'not_shown' => 'Not shown'][$c['status']] ?? $c['status'];

            return "<tr><td style='padding:6px 8px'>{$e($c['label'])}</td><td style='padding:6px 8px;color:{$colour};font-weight:600'>{$label}</td><td style='padding:6px 8px;text-align:right'>{$e($c['points'])} / {$e($c['weight'])}</td></tr>";
        })->implode('');
        $link = ! empty($a['link']) ? "<p style='margin-top:8px'><a href='{$e($a['link'])}' target='_blank' style='color:#c8102e;font-weight:600'>Open evidence link</a></p>" : '';

        return new HtmlString("
            <div style='display:grid;gap:14px;font-size:14px;line-height:1.55'>
              <div><p style='font-weight:700;margin-bottom:4px'>Task brief</p><p>{$e($task['brief'] ?? '')}</p>
              <pre style='white-space:pre-wrap;background:#f4f4f5;border-radius:10px;padding:10px;margin-top:6px;font-size:12px'>{$e($task['scenario'] ?? '')}</pre></div>
              <div><p style='font-weight:700;margin-bottom:4px'>Candidate answer ({$e($a['words'] ?? 0)} words)</p>
              <div style='white-space:pre-wrap;border:1px solid #e4e4e7;border-radius:10px;padding:10px'>{$e($a['response'] ?? '')}</div>{$link}</div>
              <div><p style='font-weight:700;margin-bottom:4px'>Rubric (preliminary, machine-scored)</p>
              <table style='width:100%;border-collapse:collapse;border:1px solid #e4e4e7;border-radius:10px'>{$rows}</table>
              <p style='margin-top:6px;color:#71717a'>Why review: {$e($a['review_reason'] ?? '')}</p></div>
            </div>");
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('candidate.name')->label('Candidate')->searchable()->weight('semibold'),
                TextColumn::make('task')->label('Practical task')->getStateUsing(fn ($record) => $record->answers['task_title'] ?? '-'),
                TextColumn::make('job.title')->label('For vacancy')->placeholder('General'),
                TextColumn::make('overall_score')->label('Score')->badge()
                    ->color(fn ($state) => $state >= 70 ? 'success' : ($state >= 45 ? 'warning' : 'danger'))
                    ->formatStateUsing(fn ($state, $record) => $state . (($record->answers['status'] ?? '') === 'validated' ? ' (validated)' : ' (preliminary)')),
                TextColumn::make('review_status')->label('Status')->badge()
                    ->getStateUsing(fn ($record) => ($record->answers['status'] ?? 'needs_review') === 'validated' ? 'Human-validated' : 'Needs review')
                    ->color(fn ($state) => $state === 'Human-validated' ? 'success' : 'warning'),
                TextColumn::make('reviewer')->label('Reviewed by')->getStateUsing(fn ($record) => $record->answers['reviewer'] ?? '-')->toggleable(),
                TextColumn::make('completed_at')->label('Submitted')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['needs_review' => 'Needs review', 'validated' => 'Human-validated'])
                    ->query(fn (Builder $q, array $data) => $data['value'] ? $q->where('answers->status', $data['value']) : $q),
            ])
            ->recordActions([
                Action::make('review')
                    ->label(fn ($record) => ($record->answers['status'] ?? '') === 'validated' ? 'View' : 'Review and validate')
                    ->icon('heroicon-o-check-badge')
                    ->color(fn ($record) => ($record->answers['status'] ?? '') === 'validated' ? 'gray' : 'success')
                    ->modalHeading(fn ($record) => ($record->candidate?->name ?? 'Candidate') . ': ' . ($record->answers['task_title'] ?? 'Practical task'))
                    ->modalDescription('The score is a preliminary rubric check. Your judgement is final and is recorded.')
                    ->modalContent(fn ($record) => static::submissionHtml($record))
                    ->schema(fn ($record) => ($record->answers['status'] ?? '') === 'validated' ? [] : [
                        TextInput::make('final_score')->label('Final score (0-100)')->numeric()->minValue(0)->maxValue(100)->required()->default($record->overall_score),
                        Textarea::make('note')->label('Feedback to record')->rows(2),
                    ])
                    ->modalSubmitActionLabel('Validate result')
                    ->modalSubmitAction(fn ($action, $record) => ($record->answers['status'] ?? '') === 'validated' ? false : $action)
                    ->action(function ($record, array $data) {
                        if (($record->answers['status'] ?? '') === 'validated') {
                            return;
                        }
                        app(PracticalAssessmentService::class)->validate($record, (int) $data['final_score'], auth()->user()?->name ?? 'recruiter', $data['note'] ?? null);
                        Notification::make()->title('Result validated. Skills are now human-validated.')->success()->send();
                    }),
            ])
            ->defaultSort('completed_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPracticalReviews::route('/'),
        ];
    }
}
