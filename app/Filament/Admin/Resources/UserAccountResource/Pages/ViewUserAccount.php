<?php

namespace App\Filament\Admin\Resources\UserAccountResource\Pages;

use App\Filament\Admin\Resources\UserAccountResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewUserAccount extends ViewRecord
{
    protected static string $resource = UserAccountResource::class;

    protected static ?string $title = 'Verificação KYC';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informações do Usuário')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Nome do Usuário'),
                        TextEntry::make('user.email')
                            ->label('Email'),
                    ])
                    ->columns(2),

                Section::make('Dados Pessoais')
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Nome Completo'),
                        TextEntry::make('document_number')
                            ->label('NIF'),
                        TextEntry::make('birth_date')
                            ->label('Data de Nascimento')
                            ->date('d/m/Y'),
                        TextEntry::make('phone')
                            ->label('Telemóvel'),
                        TextEntry::make('country')
                            ->label('País')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'PT' => 'Portugal',
                                'BR' => 'Brasil',
                                default => $state,
                            }),
                    ])
                    ->columns(2),

                Section::make('Verificação KYC')
                    ->description('Status geral da verificação de identidade e documentos')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status da Verificação')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'Pendente',
                                'approved' => 'Aprovado',
                                'rejected' => 'Rejeitado',
                                default => $state,
                            }),

                        TextEntry::make('user.userDocument.document_type')
                            ->label('Tipo de Documento')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'cc' => 'Cartão de Cidadão',
                                'passport' => 'Passaporte',
                                'carta_conducao' => 'Carta de Condução',
                                default => $state ?? 'Não informado',
                            })
                            ->visible(fn ($record) => $record->user->userDocument),

                        TextEntry::make('rejection_reason')
                            ->label('Motivo da Rejeição')
                            ->visible(fn ($record) => $record->status === 'rejected'),

                        TextEntry::make('verified_at')
                            ->label('Data de Verificação')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Não verificado'),

                        TextEntry::make('user.userDocument.created_at')
                            ->label('Data de Envio')
                            ->dateTime('d/m/Y H:i')
                            ->visible(fn ($record) => $record->user->userDocument),
                    ])
                    ->columns(2),

                Section::make('Documentos de Verificação')
                    ->description('Imagens dos documentos enviados pelo usuário')
                    ->schema([
                        ViewEntry::make('user.userDocument.document_front')
                            ->label('Documento (Frente)')
                            ->view('filament.components.r2-document-viewer')
                            ->viewData([
                                'urlAccessor' => 'document_front',
                                'label' => 'Frente do Documento',
                            ]),

                        ViewEntry::make('user.userDocument.document_back')
                            ->label('Documento (Verso)')
                            ->view('filament.components.r2-document-viewer')
                            ->viewData([
                                'urlAccessor' => 'document_back',
                                'label' => 'Verso do Documento',
                            ]),

                        ViewEntry::make('user.userDocument.selfie')
                            ->label('Selfie com Documento')
                            ->view('filament.components.r2-document-viewer')
                            ->viewData([
                                'urlAccessor' => 'selfie',
                                'label' => 'Selfie com Documento',
                            ]),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record->user->userDocument),

                Section::make('Controle de Reenvio')
                    ->description('Informações sobre tentativas e permissões de reenvio')
                    ->schema([
                        TextEntry::make('user.userDocument.submission_attempts')
                            ->label('Tentativas de Envio')
                            ->formatStateUsing(function ($state, $record) {
                                $maxAttempts = config('kyc.max_submission_attempts', 3);

                                return ($state ?? 0).' de '.$maxAttempts;
                            })
                            ->badge()
                            ->color(function ($state, $record) {
                                $maxAttempts = config('kyc.max_submission_attempts', 3);
                                $attempts = $state ?? 0;

                                if ($attempts >= $maxAttempts) {
                                    return 'danger';
                                }
                                if ($attempts >= ($maxAttempts * 0.7)) {
                                    return 'warning';
                                }

                                return 'success';
                            }),

                        TextEntry::make('user.userDocument.last_submission_at')
                            ->label('Último Envio')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Nunca enviado'),

                        TextEntry::make('can_resubmit_unified')
                            ->label('Pode Reenviar')
                            ->formatStateUsing(function ($state, $record) {
                                // Usar a mesma lógica da API
                                if ($record->status !== 'rejected') {
                                    return 'N/A';
                                }

                                if (! $record->can_resubmit) {
                                    return 'Não (Bloqueado pelo admin)';
                                }

                                $userDocument = $record->user->userDocument;
                                if (! $userDocument) {
                                    return 'Não (Sem documentos)';
                                }

                                // Verificar limite de tentativas
                                $maxAttempts = config('kyc.max_submission_attempts', 3);
                                if ($maxAttempts > 0 && $userDocument->submission_attempts >= $maxAttempts) {
                                    return 'Não (Limite excedido)';
                                }

                                // Verificar cooldown
                                $cooldownHours = config('kyc.resubmission_cooldown_hours', 24);
                                if ($cooldownHours > 0 && $userDocument->last_submission_at) {
                                    $cooldownEnds = $userDocument->last_submission_at->addHours($cooldownHours);
                                    if (now()->lt($cooldownEnds)) {
                                        return 'Não (Em cooldown)';
                                    }
                                }

                                return 'Sim';
                            })
                            ->badge()
                            ->color(function ($state, $record) {
                                if ($record->status !== 'rejected') {
                                    return 'gray';
                                }

                                return str_starts_with($state, 'Sim') ? 'success' : 'danger';
                            }),

                        TextEntry::make('cooldown_status')
                            ->label('Status do Cooldown')
                            ->formatStateUsing(function ($record) {
                                if (! $record->user->userDocument) {
                                    return 'N/A';
                                }

                                $cooldownHours = $record->user->userDocument->getResubmissionCooldownHours();
                                if ($cooldownHours === null) {
                                    return 'Sem cooldown';
                                }

                                return $cooldownHours > 0 ? "Aguardar {$cooldownHours}h" : 'Cooldown expirado';
                            })
                            ->badge()
                            ->color(function ($record) {
                                if (! $record->user->userDocument) {
                                    return 'gray';
                                }

                                $cooldownHours = $record->user->userDocument->getResubmissionCooldownHours();
                                if ($cooldownHours === null) {
                                    return 'success';
                                }

                                return $cooldownHours > 0 ? 'warning' : 'success';
                            }),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->user->userDocument && $record->status === 'rejected'),
            ]);
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Aprovar Verificação')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Aprovar Verificação KYC')
                ->modalDescription('Isso irá aprovar tanto os dados pessoais quanto os documentos do usuário.')
                ->action(function () {
                    // Aprovar dados pessoais
                    $this->record->update([
                        'status' => 'approved',
                        'verified_at' => now(),
                        'rejection_reason' => null,
                    ]);

                    // Aprovar documentos se existirem
                    if ($this->record->user->userDocument) {
                        $this->record->user->userDocument->update([
                            'verification_status' => 'approved',
                            'verified_at' => now(),
                            'rejection_reason' => null,
                            'can_resubmit' => true, // Reset para true quando aprovado
                        ]);
                    }

                    Notification::make()
                        ->title('Verificação KYC aprovada com sucesso!')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Action::make('reject')
                ->label('Rejeitar Verificação')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'pending')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Motivo da Rejeição')
                        ->required()
                        ->rows(3)
                        ->placeholder('Descreva o motivo da rejeição...'),
                ])
                ->action(function (array $data) {
                    // Rejeitar dados pessoais
                    $this->record->update([
                        'status' => 'rejected',
                        'verified_at' => now(),
                        'rejection_reason' => $data['rejection_reason'],
                    ]);

                    // Rejeitar documentos se existirem
                    if ($this->record->user->userDocument) {
                        $this->record->user->userDocument->update([
                            'verification_status' => 'rejected',
                            'verified_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                            'can_resubmit' => true, // Permitir reenvio por padrão
                        ]);
                    }

                    Notification::make()
                        ->title('Verificação KYC rejeitada!')
                        ->warning()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Action::make('reset_attempts')
                ->label('Resetar Tentativas')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'rejected' &&
                    $this->record->user->userDocument &&
                    $this->record->user->userDocument->submission_attempts > 0
                )
                ->requiresConfirmation()
                ->modalHeading('Resetar Tentativas de Envio')
                ->modalDescription('Isso irá zerar o contador de tentativas e remover o cooldown, permitindo que o usuário reenvie imediatamente.')
                ->action(function () {
                    $this->record->user->userDocument->update([
                        'submission_attempts' => 0,
                        'last_submission_at' => null,
                        'can_resubmit' => true,
                    ]);

                    Notification::make()
                        ->title('Tentativas resetadas com sucesso!')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}
