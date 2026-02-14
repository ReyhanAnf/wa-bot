<?php

namespace App\Filament\Resources\BotResponses\Schemas;

use Filament\Schemas\Schema;

class BotResponseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('keyword')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Textarea::make('response')
                    ->required()
                    ->columnSpanFull(),
                \Filament\Forms\Components\Select::make('match_type')
                    ->options([
                        'exact' => 'Exact Match',
                        'contains' => 'Contains',
                    ])
                    ->required()
                    ->default('exact'),
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true),
            ]);
    }
}
