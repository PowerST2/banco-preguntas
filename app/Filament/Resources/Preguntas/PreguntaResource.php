<?php

namespace App\Filament\Resources\Preguntas;

use App\Filament\Resources\Preguntas\Pages\CreatePregunta;
use App\Filament\Resources\Preguntas\Pages\EditPregunta;
use App\Filament\Resources\Preguntas\Pages\ListPreguntas;
use App\Filament\Resources\Preguntas\Schemas\PreguntaForm;
use App\Filament\Resources\Preguntas\Tables\PreguntasTable;
use App\Models\Pregunta;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PreguntaResource extends Resource
{
    protected static ?string $model = Pregunta::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'codificacion';

    protected static ?string $navigationLabel = 'Preguntas';

    protected static ?string $pluralModelLabel = 'Preguntas';

    protected static ?string $modelLabel = 'Pregunta';

    public static function form(Schema $schema): Schema
    {
        return PreguntaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PreguntasTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withExists(['preguntaSorteada as esta_sorteada']);
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
            'index' => ListPreguntas::route('/'),
            'create' => CreatePregunta::route('/create'),
            'edit' => EditPregunta::route('/{record}/edit'),
        ];
    }
}
