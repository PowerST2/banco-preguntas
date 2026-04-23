<?php

namespace App\Filament\Resources\Examens;

use App\Filament\Resources\Examens\Pages\CreateExamen;
use App\Filament\Resources\Examens\Pages\EditExamen;
use App\Filament\Resources\Examens\Pages\ListExamens;
use App\Filament\Resources\Examens\Schemas\ExamenForm;
use App\Filament\Resources\Examens\Tables\ExamensTable;
use App\Models\Examen;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ExamenResource extends Resource
{
    protected static ?string $model = Examen::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationLabel = 'Exámenes';

    protected static ?string $pluralModelLabel = 'Exámenes';

    protected static ?string $modelLabel = 'Examen';

    public static function form(Schema $schema): Schema
    {
        return ExamenForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExamensTable::configure($table);
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
            'index' => ListExamens::route('/'),
            'create' => CreateExamen::route('/create'),
            'edit' => EditExamen::route('/{record}/edit'),
        ];
    }
}
