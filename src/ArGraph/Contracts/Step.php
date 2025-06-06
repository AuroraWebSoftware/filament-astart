<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Contracts;

interface Step
{
    /**
     *  bu step hangi state'i almak zorunda kalacak?
     *
     * @return class-string
     */
    public function getSupportedState(): string;

    public function run(State $state): Step | Result;

    public function stop(string $message): self;

    public function requiresHumanInteraction(): false | string;
}
