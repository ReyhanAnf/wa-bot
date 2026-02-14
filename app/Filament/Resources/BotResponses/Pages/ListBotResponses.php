<?php

namespace App\Filament\Resources\BotResponses\Pages;

use App\Filament\Resources\BotResponses\BotResponseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBotResponses extends ListRecords
{
    protected static string $resource = BotResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
