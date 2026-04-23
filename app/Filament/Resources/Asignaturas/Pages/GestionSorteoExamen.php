<?php

namespace App\Filament\Resources\Asignaturas\Pages;

use App\Filament\Resources\Asignaturas\AsignaturaResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class GestionSorteoExamen extends Page
{
    use InteractsWithRecord;

    protected static string $resource = AsignaturaResource::class;

    protected string $view = 'filament.resources.asignaturas.pages.gestion-sorteo-examen';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }
}
