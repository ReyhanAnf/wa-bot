<?php

namespace App\Filament\Resources\Schedules\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Time & Date')
                    ->schema([
                        Select::make('day')
                            ->options([
                                'Senin' => 'Senin',
                                'Selasa' => 'Selasa',
                                'Rabu' => 'Rabu',
                                'Kamis' => 'Kamis',
                                'Jumat' => 'Jumat',
                                'Sabtu' => 'Sabtu',
                                'Minggu' => 'Minggu',
                            ])
                            ->required(),
                        Grid::make(2)
                            ->schema([
                                TimePicker::make('start_time')
                                    ->required(),
                                TimePicker::make('end_time')
                                    ->required(),
                            ]),
                    ]),
                Section::make('Details')
                    ->schema([
                        TextInput::make('student_name')
                            ->maxLength(255),
                        TextInput::make('subject')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('lecturer')
                            ->maxLength(255),
                        TextInput::make('room')
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ]),
            ]);
    }
}
