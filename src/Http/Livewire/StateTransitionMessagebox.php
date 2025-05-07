<?php

namespace AuroraWebSoftware\FilamentAstart\Http\Livewire;

use AuroraWebSoftware\ArFlow\Contacts\StateableModelContract;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Livewire\Attributes\On;
use Livewire\Component;

class StateTransitionMessagebox extends Component
{
    public Model & StateableModelContract $model;

    public ?string $heading = null;

    public ?string $message = null;

    public ?string $type = null;

    #[On(event: 'arflow.transition-message-created.{model.id}')]
    public function setMessage(?string $heading = null, ?string $message = null, ?string $type = null): void
    {
        $this->heading = $heading;
        $this->message = $message;
        $this->type = $type;
    }

    public function render(): View | Application | Factory | \Illuminate\Contracts\Foundation\Application
    {
        return view('filament-astart::livewire.state-transition-messagebox');
    }
}
