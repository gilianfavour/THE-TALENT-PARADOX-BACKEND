<?php

namespace App\Filament\Resources\SkillEvidence;

use App\Filament\Resources\SkillEvidence\Pages\ListSkillEvidence;
use App\Models\CandidateSkill;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/** Read-only view of data captured on the candidate website. */
class SkillEvidenceResource extends Resource
{
    protected static ?string $model = CandidateSkill::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Skills evidence';

    protected static ?string $modelLabel = 'skill evidence';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return 'Talent';
    }

    public static function getNavigationBadge(): ?string
    {
        $n = CandidateSkill::whereNotNull('demonstrated_score')->whereNull('validated_at')->count();

        return $n ? (string) $n : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['candidate', 'skill'])->latest('updated_at'))
            ->columns([
                TextColumn::make('candidate.name')->label('Candidate')->searchable()->weight('semibold'),
                TextColumn::make('skill.name')->label('Skill')->searchable(),
                TextColumn::make('level')->label('Evidence level')->badge()
                    ->getStateUsing(fn (CandidateSkill $record) => $record->evidenceLevel())
                    ->formatStateUsing(fn ($state) => CandidateSkill::LEVELS[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'human_validated' => 'success',
                        'assessed' => 'primary',
                        'evidence_supported' => 'warning',
                        'ai_inferred' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('demonstrated_score')->label('Assessed score')->badge()->placeholder('-')
                    ->color(fn ($state) => $state >= 70 ? 'success' : ($state >= 45 ? 'warning' : 'danger'))
                    ->formatStateUsing(fn ($state) => $state . '/100')->sortable(),
                TextColumn::make('claimed_years')->label('Claimed years')->placeholder('-')->toggleable(),
                TextColumn::make('source')->placeholder('CV')->toggleable(),
                TextColumn::make('validated_by')->label('Validated by')->placeholder('-')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('evidence_status')->label('Evidence level')->options(CandidateSkill::LEVELS),
            ])
            ->recordActions([
                Action::make('validate')
                    ->label('Validate')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Confirms you reviewed the evidence for this skill. It becomes Human-validated on the candidate profile.')
                    ->visible(fn (CandidateSkill $record) => $record->validated_at === null)
                    ->action(function (CandidateSkill $record) {
                        $record->update([
                            'validated_at' => now(),
                            'validated_by' => auth()->user()?->name ?? 'admin',
                            'evidence_status' => 'human_validated',
                        ]);
                        Notification::make()->title('Skill validated')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSkillEvidence::route('/'),
        ];
    }
}
