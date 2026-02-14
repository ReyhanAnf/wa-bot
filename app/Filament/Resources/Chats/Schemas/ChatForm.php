<?php

namespace App\Filament\Resources\Chats\Schemas;

use Filament\Schemas\Schema;

class ChatForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('wa_number')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                \Filament\Forms\Components\Select::make('source')
                    ->options([
                        'user' => 'User',
                        'bot_rule' => 'Bot (Rule)',
                        'bot_ai' => 'Bot (AI)',
                    ])
                    ->required(),
            ]);
    }
}
