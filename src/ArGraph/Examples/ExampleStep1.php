<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Examples;

use AuroraWebSoftware\FilamentAstart\ArGraph\Chat\ChatState;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\State;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Step;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class ExampleStep1 implements Step
{
    private string $prompt;

    public function __construct(?Step $previousStep = null) {}

    public function getSupportedState(): string
    {
        return ChatState::class;
    }

    public function prompt(string $prompt)
    {
        $this->prompt = $prompt;
    }

    public function run(State $state): Step
    {
        $firstMessage = new UserMessage($this->prompt);
        $state->addMessage($firstMessage);

        $schema = new ObjectSchema(
            name: 'duygu',
            description: 'duygu analizi',
            properties: [
                new EnumSchema('duygu', 'userın duygusu', ['mutlu', 'sinirli']),
            ],
            requiredFields: ['duygu']
        );

        $response = Prism::structured()
            ->using(Provider::OpenAI, 'o4-mini')
            ->withSchema($schema)
            ->withPrompt($this->prompt)
            ->withSystemPrompt('sen bir duygu analistisin, user sana cümle yazacak sen de onu segmente edeceksin')
            ->asStructured();

        $state->addMessages($response->responseMessages);

        $data = $response->structured;

        if ($data['duygu'] == 'mutlu') {
            return new ExampleStep2($this);
        } else {
            return new ExampleStep1a($this);
        }
    }
}
