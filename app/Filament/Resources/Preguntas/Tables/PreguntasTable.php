<?php

namespace App\Filament\Resources\Preguntas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PreguntasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('idpregunta')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('codificacion')
                    ->label('Codificación')
                    ->searchable(),
                TextColumn::make('asignatura.nombre')
                    ->label('Asignatura')
                    ->sortable(),
                TextColumn::make('capitulo')
                    ->label('Capítulo')
                    ->searchable(),
                TextColumn::make('tema')
                    ->label('Tema')
                    ->searchable(),
                TextColumn::make('sub_tema')
                    ->label('Sub tema')
                    ->searchable(),
                TextColumn::make('grado_dificultad')
                    ->label('Dificultad')
                    ->formatStateUsing(fn (?int $state): string => match ($state) {
                        1 => 'Facil',
                        2 => 'Normal',
                        3 => 'Dificil',
                        default => '-',
                    })
                    ->sortable(),
                TextColumn::make('ruta')
                    ->label('Ruta')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record): ?string => $record->ruta),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
