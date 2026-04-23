<?php

namespace App\Filament\Resources\Examens\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExamenForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre del examen')
                    ->required()
                    ->maxLength(150)
                    ->unique(ignoreRecord: true),
                TextInput::make('proceso')
                    ->label('Nombre del proceso')
                    ->required()
                    ->maxLength(150),
            ]);
    }
}
