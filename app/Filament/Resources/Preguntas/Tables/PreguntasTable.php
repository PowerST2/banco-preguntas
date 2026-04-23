<?php

namespace App\Filament\Resources\Preguntas\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                    ->formatStateUsing(fn (?string $state): string => $state ?: '-')
                    ->sortable(),
                TextColumn::make('esta_sorteada')
                    ->label('Estado sorteo')
                    ->badge()
                    ->color(fn ($state): string => ((int) $state) === 1 ? 'warning' : 'gray')
                    ->formatStateUsing(fn ($state): string => ((int) $state) === 1 ? 'Sorteada' : 'No sorteada'),
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
                SelectFilter::make('asignatura_id')
                    ->label('Asignatura')
                    ->relationship('asignatura', 'nombre')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('grado_dificultad')
                    ->label('Dificultad')
                    ->options([
                        'Facil' => 'Facil',
                        'Normal' => 'Normal',
                        'Dificil' => 'Dificil',
                    ]),

                SelectFilter::make('estado_sorteo')
                    ->label('Estado de sorteo')
                    ->options([
                        'si' => 'Sorteadas',
                        'no' => 'No sorteadas',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $valor = $data['value'] ?? null;

                        return match ($valor) {
                            'si' => $query->whereIn('idpregunta', DB::table('preguntas_sorteadas')->select('id_pregunta')),
                            'no' => $query->whereNotIn('idpregunta', DB::table('preguntas_sorteadas')->select('id_pregunta')),
                            default => $query,
                        };
                    }),
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
