<?php

namespace App\Filament\Resources\Asignaturas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AsignaturaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(150)
                    ->unique(ignoreRecord: true),
            ]);
    }
}
