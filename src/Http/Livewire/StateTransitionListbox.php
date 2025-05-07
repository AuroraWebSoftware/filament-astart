<?php

namespace AuroraWebSoftware\FilamentAstart\Http\Livewire;

use AuroraWebSoftware\AAuth\Facades\AAuth;
use AuroraWebSoftware\ArFlow\Contacts\StateableModelContract;
use AuroraWebSoftware\ArFlow\Exceptions\StateNotFoundException;
use AuroraWebSoftware\ArFlow\Exceptions\TransitionActionException;
use AuroraWebSoftware\ArFlow\Exceptions\TransitionNotFoundException;
use AuroraWebSoftware\ArFlow\Exceptions\WorkflowNotAppliedException;
use AuroraWebSoftware\ArFlow\Exceptions\WorkflowNotFoundException;
use AuroraWebSoftware\ArFlow\Exceptions\WorkflowNotSupportedException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Livewire\Attributes\On;
use Livewire\Component;


class StateTransitionListbox extends Component
{
    public Model&StateableModelContract $model;

    public bool $onlyAllowedTransitionStates = true;

    /**
     * translation key prefix or translatable
     **/
    public bool|string $translatable = true;

    public string $textSize = 'base';

    public string $buttonPaddingClasses = 'px-4 py-2';

    #[On(event: 'arflow.refresh.state-listbox')]
    public function refresh(): void
    {
        $this->dispatch(event: 'arflow.transition-message-created');
        $this->model->refresh();
    }

    public function transitionTo(string $state): void
    {
        $this->dispatch('arflow.transition-message-created.'.$this->model->getId().$this->model->getId());

        try {
            if (Auth::check()) {
                $this->model->transitionTo($state, null, null, null, ['userId' => Auth::user()->id, 'roleId' => AAuth::currentRole()->id]);
            } else {
                $this->model->transitionTo($state);
            }
            $this->dispatch(
                event: 'arflow.transition-message-created.'.$this->model->getId(),
                heading: __('arflow.state-transition-listbox.transition-success-heading'),
                message: __('arflow.state-transition-listbox.transition-success-message'),
                type: 'success'
            );
        } catch (StateNotFoundException|TransitionActionException|TransitionNotFoundException|WorkflowNotAppliedException|WorkflowNotFoundException|WorkflowNotSupportedException $e) {
            $this->dispatch(
                event: 'arflow.transition-message-created.'.$this->model->getId(),
                heading: __('arflow.state-transition-listbox.exception'),
                message: $e->getMessage(),
                type: 'error'
            );
        }
    }

    public function placeholder(): View|Application|Factory|\Illuminate\Contracts\Foundation\Application
    {
        return view('filament-astart::livewire.state-transition-listbox-placeholder');
    }

    public function getLocalizedState(string $state): string
    {
//        $moduleData = Module::allEnabled();
//        $module = count($moduleData) > 0 ? strtolower(array_values($moduleData)[0]->getName()) : null;
//
//        if ($module && Lang::has("$module::arflow.state.$state")) {
//            return __("$module::arflow.state.$state");
//        }

        if (Lang::has("arflow.state.$state")) {
            return __("arflow.state.$state");
        }

        return $state;
    }

    public function getLocalizedStateListbox(string $state): string
    {
//        $moduleData = Module::allEnabled();
//        $module = count($moduleData) > 0 ? strtolower(array_values($moduleData)[0]->getName()) : null;
//
//        if ($module && Lang::has("$module::arflow.state.listbox.$state")) {
//            return __("$module::arflow.state.listbox.$state");
//        }

        if (Lang::has("arflow.state.listbox.$state")) {
            return __("arflow.state.listbox.$state");
        }

        return $state;
    }

    public function render(): View|Application|Factory|\Illuminate\Contracts\Foundation\Application
    {
        $bgColor = config('arflow.colors.'.$this->model->currentState()) ?? 'indigo';

        return view('filament-astart::livewire.state-transition-listbox', ['bgColor' => $bgColor]);
    }
}
