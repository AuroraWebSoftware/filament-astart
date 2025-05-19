<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Examples;

use AuroraWebSoftware\FilamentAstart\ArGraph\Chat\ChatState;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Result;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\State;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Step;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;

class ExampleStep3 implements Step
{

    public function __construct(Step $previousStep = null)
    {
    }

    public function getSupportedState(): string
    {
       return ChatState::class;
    }

    public function run(State $state): Step|Result
    {
        $response = Prism::text()
            ->using(Provider::OpenAI, 'gpt-4o')
            ->withSystemPrompt(
                'Sen müşterileri temsilcisisin.
                 Müşteri ile konuşma geçmisi sana verilecek, sen de onu yatıştırmak için özür mahiyetinde bir cevap vereceksin.'
            )
            ->withMessages($state->getMessages())
            ->asText();

        $state->addMessages($response->responseMessages);

        return (new ExampleResultStep($this));
    }



}
