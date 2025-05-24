<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Chat;

use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Flow;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Result;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\State;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Step;

class ChatFlow implements Flow
{
    private ChatState $state;
    private Step $nextStep;

    // todo eklenecek
    //private int $timeout = -1;
    //private int $maxSteps = -1;

    public function __construct(Step $initialStep, ChatState $state)
    {
        $this->nextStep = $initialStep;
        $this->state = $state;

        return $this;
    }

    public function run(): Result
    {
        if ($this->state->getChatMemory()->getNextStep() != null) {
            $nextStepClassName = $this->state->getChatMemory()->getNextStep();
            $this->nextStep = new $nextStepClassName;
        }

        $nextStep = $this->nextStep->run($this->state);
        $this->state->getChatMemory()->storeNextStep($nextStep::class);

        while ($nextStep instanceof Step) {

            if ($nextStep instanceof Result) {
                $this->state->getChatMemory()->storeNextStep(null);
            } else {
                if ($nextStep->requiresHumanInteraction()) {
                    return new ChatResult(
                        $nextStep->requiresHumanInteraction()
                    );
                } else {
                    $nextStep = $nextStep->run($this->state);

                    if ($nextStep instanceof Result) {
                        $this->state->getChatMemory()->storeNextStep(null);
                    } else {
                        $this->state->getChatMemory()->storeNextStep($nextStep::class);
                    }
                }
            }
        }
        return $nextStep;
    }
}
