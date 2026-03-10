<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Models\Member;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $modelLabel = 'Member';

    protected static ?string $pluralModelLabel = 'Members';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados do membro')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(150),

                    Forms\Components\TextInput::make('email')
                        ->label('E-mail')
                        ->email()
                        ->required()
                        ->maxLength(150),

                    Forms\Components\TextInput::make('phone')
                        ->label('Telefone')
                        ->maxLength(30),

                    Forms\Components\TextInput::make('cpf_cnpj')
                        ->label('CPF/CNPJ')
                        ->maxLength(20),

                    Forms\Components\TextInput::make('company_name')
                        ->label('Empresa')
                        ->maxLength(150),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'active' => 'Ativo',
                            'inactive' => 'Inativo',
                            'blocked' => 'Bloqueado',
                            'pending' => 'Pendente',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('origin')
                        ->label('Origem')
                        ->default('wordpress')
                        ->maxLength(50),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'gray' => 'inactive',
                        'danger' => 'blocked',
                        'warning' => 'pending',
                    ]),

                Tables\Columns\TextColumn::make('origin')
                    ->label('Origem')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
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
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
        ];
    }
}

