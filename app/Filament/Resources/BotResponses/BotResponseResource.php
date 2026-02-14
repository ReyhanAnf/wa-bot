<?php

namespace App\Filament\Resources\BotResponses;

use App\Filament\Resources\BotResponses\Pages\CreateBotResponse;
use App\Filament\Resources\BotResponses\Pages\EditBotResponse;
use App\Filament\Resources\BotResponses\Pages\ListBotResponses;
use App\Filament\Resources\BotResponses\Schemas\BotResponseForm;
use App\Filament\Resources\BotResponses\Tables\BotResponsesTable;
use App\Models\BotResponse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BotResponseResource extends Resource
{
    protected static ?string $model = BotResponse::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'keyword';

    public static function form(Schema $schema): Schema
    {
        return BotResponseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BotResponsesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBotResponses::route('/'),
            'create' => CreateBotResponse::route('/create'),
            'edit' => EditBotResponse::route('/{record}/edit'),
        ];
    }
}
