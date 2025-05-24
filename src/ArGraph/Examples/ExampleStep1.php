<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Examples;

use AuroraWebSoftware\FilamentAstart\ArGraph\Chat\ChatState;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\State;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Step;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;

class ExampleStep1 implements Step
{
    private string $stopMessage;

    public function getSupportedState(): string
    {
        return ChatState::class;
    }

    public function run(State $state): Step
    {
        $schema = new ObjectSchema(
            name: 'duygu',
            description: 'duygu analizi',
            properties: [
                new EnumSchema('duygu', 'userın duygusu', ['mutlu', 'sinirli']),
            ],
            requiredFields: ['duygu']
        );

        $response = Prism::structured()
            ->using(Provider::OpenAI, 'gpt-4o')
            ->withSchema($schema)
            ->withMessages($state->getMessages())
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

    public function stop(string $message): Step
    {
        $this->stopMessage = $message;

        return $this;
    }

    public function requiresHumanInteraction(): false | string
    {
        if ($this->stopMessage) {
            return $this->stopMessage;
        }

        return false;
    }
}
