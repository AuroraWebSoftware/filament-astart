<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Examples;

use AuroraWebSoftware\FilamentAstart\ArGraph\Chat\ChatResult;
use AuroraWebSoftware\FilamentAstart\ArGraph\Chat\ChatState;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Result;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\State;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Step;

class ExampleResultStep implements Step
{
    public function __construct(?Step $previousStep = null) {}

    public function getSupportedState(): string
    {
        return ChatState::class;
    }

    public function run(State $state): Result
    {
        $array = $state->getMessages();

        dump($array);

        return new ChatResult(
            end($array)->content
        );
    }

    public function stop(string $message): Step
    {
        return $this;
    }

    public function requiresHumanInteraction(): false | string
    {
        return false;
    }
}
