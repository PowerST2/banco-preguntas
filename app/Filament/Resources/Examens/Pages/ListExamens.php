<?php

namespace App\Filament\Resources\Examens\Pages;

use App\Filament\Resources\Examens\ExamenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExamens extends ListRecords
{
    protected static string $resource = ExamenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
