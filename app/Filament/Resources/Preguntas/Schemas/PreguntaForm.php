<?php

namespace App\Filament\Resources\Preguntas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PreguntaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codificacion')
                    ->label('Codificación')
                    ->maxLength(100),
                Select::make('asignatura_id')
                    ->label('Asignatura')
                    ->relationship('asignatura', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false),
                TextInput::make('capitulo')
                    ->label('Capítulo')
                    ->placeholder('Ej: 01, 02, 10, 25...')
                    ->regex('/^[0-9]{2,}$/')
                    ->required(),
                TextInput::make('tema')
                    ->label('Tema')
                    ->maxLength(150),
                TextInput::make('sub_tema')
                    ->label('Sub tema')
                    ->maxLength(150),
                Select::make('grado_dificultad')
                    ->label('Grado de dificultad')
                    ->options([
                        1 => 'Facil',
                        2 => 'Normal',
                        3 => 'Dificil',
                    ])
                    ->required()
                    ->native(false),
                Textarea::make('clave')
                    ->label('Clave')
                    ->columnSpanFull(),
                Textarea::make('proceso')
                    ->label('Proceso')
                    ->columnSpanFull(),
                Textarea::make('ruta')
                    ->label('Ruta de directorio de la pregunta')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
