<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Chat;

use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\State;
use Illuminate\Support\Collection;
use Prism\Prism\Contracts\Message;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\ToolResultMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class ChatState implements State
{
    private ChatMemory $chatMemory;

    public function __construct(ChatMemory $chatMemory)
    {
        $this->chatMemory = $chatMemory;
        $this->messages = $this->chatMemory->getMessages();
    }

    /** @var array<int, Message> */
    private array $messages = [];

    private ?string $threadId;

    private ?string $nextStep;

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getChatMemory(): ChatMemory
    {
        return $this->chatMemory;
    }

    public function addMessage(Message $message, bool $store = true): self
    {

        if ($store) {
            if ($message instanceof UserMessage) {
                $this->chatMemory->storeUserMessage($message);
            } elseif ($message instanceof AssistantMessage) {
                $this->chatMemory->storeAssistantMessage($message);
            } elseif ($message instanceof ToolResultMessage) {
                $this->chatMemory->storeToolResultMessage($message);
            } elseif ($message instanceof SystemMessage) {
                $this->chatMemory->storeToolSystemMessage($message);
            }
        }

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
