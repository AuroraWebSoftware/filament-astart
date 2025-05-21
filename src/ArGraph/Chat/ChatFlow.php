<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Chat;

use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Flow;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Result;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\State;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Step;

class ChatFlow implements Flow
{
    private State $state;

    private Step $nextStep;

    private int $timeout = -1;

    private int $maxSteps = -1;

    public function __construct(Step $initialStep, int $timeout = -1, int $maxSteps = -1)
    {
        $this->nextStep = $initialStep;
        $this->timeout = $timeout;
        $this->maxSteps = $maxSteps;
        return $this;
    }

    public function run(State|ChatState $state): Result
    {
        $this->state = $state;

        if (!$this->state->getChatMemory()->getNextStep() == null) {
            $nextStepClassName = $this->state->getChatMemory()->getNextStep();
            $this->nextStep = new $nextStepClassName();
        }

        echo $this->nextStep::class . "(0) <br>";
        $nextStep = $this->nextStep->run($this->state);
        echo $nextStep::class . " (1) <br>  ";
        $this->state->getChatMemory()->storeNextStep($nextStep::class);

        while ($nextStep instanceof Step) {
            $nextStep = $nextStep->run($this->state);
            // echo $nextStep::class . " (2) <br>";
            $this->state->getChatMemory()->storeNextStep($nextStep::class);

            if ($nextStep instanceof Result) {
                $this->state->getChatMemory()->storeNextStep(null);
            } else {
                if ($nextStep->requiresHumanInteraction()) {
                    return new ChatResult(
                        $nextStep->requiresHumanInteraction()
                    );
                    break;
                }
            }
        }
        return $nextStep;
    }
}
