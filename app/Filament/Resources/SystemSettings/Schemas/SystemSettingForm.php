<?php

namespace App\Filament\Resources\SystemSettings\Schemas;

use Filament\Schemas\Schema;

class SystemSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('key')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                \Filament\Forms\Components\Textarea::make('value')
                    ->columnSpanFull(),
            ]);
    }
}
