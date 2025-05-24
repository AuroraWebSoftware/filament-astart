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

    private ChatReducer $chatReducer;

    public function __construct(ChatMemory $chatMemory)
    {
        $this->chatMemory = $chatMemory;
        $this->chatReducer = new ChatReducer($chatMemory);
        $this->messages = $this->chatMemory->getMessages();
    }

    public static function getParametricMemoryScheme(): array
    {
        return ['name'];
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

    public function addMessage(Message $message, ?string $step = null, ?string $tag = null, bool $store = true): self
    {

        if ($store) {
            if ($message instanceof UserMessage) {
                $this->chatMemory->storeUserMessage($message, $step, $tag);
            } elseif ($message instanceof AssistantMessage) {
                $this->chatMemory->storeAssistantMessage($message, $step, $tag);
            } elseif ($message instanceof ToolResultMessage) {
                $this->chatMemory->storeToolResultMessage($message, $step, $tag);
            } elseif ($message instanceof SystemMessage) {
                $this->chatMemory->storeToolSystemMessage($message, $step, $tag);
            }
        }

        $this->messages[] = $message;

        return $this;
    }

    public function addMessages(Collection $messages, ?string $step = null, ?string $tag = null): self
    {
        foreach ($messages as $message) {
            $this->addMessage($message, $step, $tag);
        }

        return $this;
    }
}
