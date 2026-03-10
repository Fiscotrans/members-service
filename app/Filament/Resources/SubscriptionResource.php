<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Assinatura')
                ->schema([
                    Forms\Components\Select::make('member_id')
                        ->label('Membro')
                        ->relationship('member', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('plan_id')
                        ->label('Plano')
                        ->relationship('plan', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'trial' => 'Trial',
                            'active' => 'Ativa',
                            'overdue' => 'Em atraso',
                            'canceled' => 'Cancelada',
                            'expired' => 'Expirada',
                            'suspended' => 'Suspensa',
                        ])
                        ->required(),

                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Início'),

                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Fim'),

                    Forms\Components\DateTimePicker::make('trial_ends_at')
                        ->label('Fim do trial'),

                    Forms\Components\DateTimePicker::make('next_billing_at')
                        ->label('Próxima cobrança'),

                    Forms\Components\TextInput::make('gateway')
                        ->label('Gateway')
                        ->maxLength(50),

                    Forms\Components\TextInput::make('gateway_subscription_id')
                        ->label('ID assinatura gateway')
                        ->maxLength(150),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('member.name')
                    ->label('Membro')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plano')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'trial',
                        'success' => 'active',
                        'danger' => 'overdue',
                        'gray' => 'canceled',
                        'secondary' => 'expired',
                        'danger' => 'suspended',
                    ]),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Início')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Fim')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('gateway')
                    ->label('Gateway')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}

