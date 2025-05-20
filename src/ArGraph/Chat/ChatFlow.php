<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Chat;

use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Flow;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Result;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\State;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Step;

class ChatFlow implements Flow
{
    private State $state;

    private Step $currentStep;

    private int $timeout = -1;

    private int $maxSteps = -1;

    public function __construct(Step $initialStep, int $timeout = -1, int $maxSteps = -1)
    {
        $this->currentStep = $initialStep;
        $this->timeout = $timeout;
        $this->maxSteps = $maxSteps;

        return $this;
    }

    public function run(State $state): Result
    {
        $this->state = $state;

        $nextStep = $this->currentStep->run($this->state);

        while ($nextStep instanceof Step) {
            $nextStep = $nextStep->run($this->state);
        }

        return $nextStep;
    }
}
