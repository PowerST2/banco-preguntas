<?php

namespace App\Filament\Resources\Asignaturas;

use App\Filament\Resources\Asignaturas\Pages\CreateAsignatura;
use App\Filament\Resources\Asignaturas\Pages\EditAsignatura;
use App\Filament\Resources\Asignaturas\Pages\ListAsignaturas;
use App\Filament\Resources\Asignaturas\Schemas\AsignaturaForm;
use App\Filament\Resources\Asignaturas\Tables\AsignaturasTable;
use App\Models\Asignatura;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AsignaturaResource extends Resource
{
    protected static ?string $model = Asignatura::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationLabel = 'Asignaturas';

    protected static ?string $pluralModelLabel = 'Asignaturas';

    protected static ?string $modelLabel = 'Asignatura';

    public static function form(Schema $schema): Schema
    {
        return AsignaturaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AsignaturasTable::configure($table);
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
            'index' => ListAsignaturas::route('/'),
            'create' => CreateAsignatura::route('/create'),
            'edit' => EditAsignatura::route('/{record}/edit'),
        ];
    }
}
