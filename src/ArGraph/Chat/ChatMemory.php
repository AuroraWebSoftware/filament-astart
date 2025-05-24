<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Chat;

use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Memory;
use AuroraWebSoftware\FilamentAstart\ArGraph\Models\ChatflowState;
use AuroraWebSoftware\FilamentAstart\ArGraph\Models\ChatflowStateMessage;
use Prism\Prism\Contracts\Message;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\ToolResultMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\ValueObjects\ToolCall;
use Prism\Prism\ValueObjects\ToolResult;

class ChatMemory implements Memory
{
    private ?ChatflowState $state;

    private ?string $nextStep = null;

    /**
     * @var array<Message>
     */
    private array $messages = [];

    public function __construct($thread)
    {
        $this->state = ChatflowState::where('thread', $thread)->first();

        if (! $this->state) {
            $this->state = ChatflowState::create(
                [
                    'thread' => $thread,
                ]
            );
        }

        if ($this->state?->next_step) {
            $this->nextStep = $this->state->next_step;
        }

    }

    public function prepareMessages(
        array $steps = [],
        array $tags = [],
        int $limit = 100,
        int $offset = 0,
        ?string $fromTag = null,
    ): void {

        $latestTag = null;
        if ($fromTag) {
            $latestTag = ChatflowStateMessage::where('tag', $fromTag)->latest()->first();
        }

        // todo burda reducer logic leri olmalı
        $messages = ChatflowStateMessage::where('argraph_chatflow_state_id', $this->state->id)
            ->when($steps, function ($query, $steps) {
                return $query->whereIn('step', $steps);
            })
            ->when($tags, function ($query, $tags) {
                return $query->whereIn('tag', $tags);
            })
            ->when($latestTag, function ($query, $latestTag) {
                return $query->where('id', '>=', $latestTag->id);
            })
            ->orderBy('created_at', 'ASC')
            ->limit($limit)
            ->offset($offset)
            ->get();

        if (count($messages) > 0) {

            foreach ($messages as $message) {
                if ($message->argraph_prism_class_type == 'UserMessage') {
                    $instance = new UserMessage(
                        $message->content,
                        $message->additional_content ?? []
                    );

                    $this->messages[] = $instance;
                } elseif ($message->argraph_prism_class_type == 'AssistantMessage') {

                    $toolCalls = [];

                    if (count($message->tool_calls) > 0) {

                        foreach ($message->tool_calls as $t) {
                            $toolCalls[] = new ToolCall(
                                $t['id'],
                                $t['name'],
                                $t['arguments'] ?? []
                            );
                        }
                    }

                    $instance = new AssistantMessage(
                        $message->content,
                        $toolCalls,
                        $message->additional_contents ?? []
                    );
                    $this->messages[] = $instance;
                } elseif ($message->argraph_prism_class_type == 'ToolResultMessage') {

                    $toolResults = [];

                    if (count($message->tool_results) > 0) {
                        foreach ($message->tool_results as $t) {
                            $toolResults[] = new ToolResult(
                                $t['toolCallId'],
                                $t['toolName'],
                                $t['args'],
                                $t['result']
                            );
                        }
                    }

                    $instance = new ToolResultMessage(
                        $toolResults
                    );
                    $this->messages[] = $instance;

                } elseif ($message->argraph_prism_class_type == 'SystemMessage') {
                    $instance = new SystemMessage(
                        $message->content,
                    );
                    $this->messages[] = $instance;
                }
            }
        }

    }

    public function getNextStep(): ?string
    {
        return $this->nextStep;
    }

    public function storeNextStep(?string $nextStep = null): void
    {
        $this->nextStep = $nextStep;
        $this->state->next_step = $this->nextStep;
        $this->state->save();
    }

    public function getMessages(): array
    {
        $this->prepareMessages();

        return $this->messages;
    }

    public function getPreparedMessages(
        array $steps = [],
        array $tags = [],
        int $limit = 100,
        int $offset = 0,
        ?string $fromTag = null
    ): array {
        $this->prepareMessages(
            $steps,
            $tags,
            $limit,
            $offset,
            $fromTag
        );

        return $this->messages;
    }

    public function storeUserMessage(UserMessage $message, ?string $step = null, ?string $tag = null): void
    {
        // save to db
        ChatflowStateMessage::create([
            'argraph_chatflow_state_id' => $this->state->id,
            'argraph_prism_class_type' => 'UserMessage',
            'content' => $message->content,
            'step' => $step,
            'tag' => $tag,
        ]);

        $this->messages[] = $message;
    }

    public function storeAssistantMessage(AssistantMessage $message, ?string $step = null, ?string $tag = null): void
    {
        // save to db
        ChatflowStateMessage::create([
            'argraph_chatflow_state_id' => $this->state->id,
            'argraph_prism_class_type' => 'AssistantMessage',
            'content' => $message->content,
            'tool_calls' => $message->toolCalls,
            'additional_content' => $message->additionalContent,
            'step' => $step,
            'tag' => $tag,
        ]);
        $this->messages[] = $message;
    }

    public function storeToolResultMessage(ToolResultMessage $message, ?string $step = null, ?string $tag = null): void
    {
        // save to db
        ChatflowStateMessage::create([
            'argraph_chatflow_state_id' => $this->state->id,
            'argraph_prism_class_type' => 'ToolResultMessage',
            'tool_results' => $message->toolResults,
            'step' => $step,
            'tag' => $tag,
        ]);
        $this->messages[] = $message;
    }

    public function storeToolSystemMessage(SystemMessage $message, ?string $step = null, ?string $tag = null): void
    {
        // save to db
        ChatflowStateMessage::create([
            'argraph_chatflow_state_id' => $this->state->id,
            'argraph_prism_class_type' => 'SystemMessage',
            'content' => $message->content,
            'step' => $step,
            'tag' => $tag,
        ]);
        $this->messages[] = $message;
    }

    public function getParametricMemories(): array
    {
        return $this->state->parametric_memory;
    }

    public function getParametricMemory($key)
    {
        return $this->state->parametric_memory[$key];
    }

    public function setParametricMemory(string $key, $value): void
    {
        $this->state->update([
            'parametric_memory' => array_merge(
                $this->state->parametric_memory ?? [],
                [$key => $value]
            ),
        ]);
    }
}
