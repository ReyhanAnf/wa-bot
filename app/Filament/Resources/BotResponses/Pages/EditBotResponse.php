<?php

namespace App\Filament\Resources\BotResponses\Pages;

use App\Filament\Resources\BotResponses\BotResponseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBotResponse extends EditRecord
{
    protected static string $resource = BotResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
