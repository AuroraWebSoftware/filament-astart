<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Chat;

use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\State;
use Illuminate\Support\Collection;
use Prism\Prism\Contracts\Message;

class ChatState implements State
{
    /** @var array<int, Message> */
    private array $messages = [];

    private ?string $threadId;

    private ?string $nextFlowStep;

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        $this->messages[] = $message;

        return $this;
    }

    public function addMessages(Collection $messages): self
    {
        foreach ($messages as $message) {
            $this->addMessage($message);
        }

        return $this;
    }
}
