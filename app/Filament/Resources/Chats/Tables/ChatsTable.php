<?php

namespace App\Filament\Resources\Chats\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class ChatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('2s')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Time'),
                \Filament\Tables\Columns\TextColumn::make('wa_number')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('message')
                    ->limit(50)
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->colors([
                        'primary' => 'user',
                        'success' => 'bot_rule',
                        'info' => 'bot_ai',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
