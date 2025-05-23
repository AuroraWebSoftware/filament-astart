<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Examples;

use AuroraWebSoftware\FilamentAstart\ArGraph\Chat\ChatState;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Result;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\State;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Step;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Enums\StructuredMode;
use Prism\Prism\Enums\ToolChoice;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Prism;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\ValueObjects\Messages\ToolResultMessage;

class ExampleStep1b implements Step
{
    private string $stopMessage;

    public function getSupportedState(): string
    {
        return ChatState::class;
    }

    public function run(State | ChatState $state): Step | Result
    {
        $tool1 = Tool::as('discount')
            ->for('müşteriye aldığı ürün kategorisine göre indirim oranı ver, müşteriye ismiyle hitap et')
            ->withEnumParameter('category', 'müşterinin aldığı ürünün kategorisi', ['ev ürünleri', 'araba aksesuarı'])
            ->using(function (string $category): string {
                if ($category == 'ev ürünleri') {
                    return "$category için indirim oranı %10 ";
                } elseif ($category == 'araba aksesuarı') {
                    return "$category için indirim oranı %20 ";
                }

                return 'indirim oranı %50 ';
            });

        $tool2 = Tool::as('contact')
            ->for('müşteri eğer yetkili birisi ile görüşmek isterse aldığı ürün kategorisine göre iletişim bilgisi verir')
            ->withEnumParameter('category', 'müşterinin aldığı ürünün kategorisi', ['ev ürünleri', 'araba aksesuarı'])
            ->using(function (string $category): string {
                if ($category == 'ev ürünleri') {
                    return "$category için yetkil, Ahmet Yılmaz, 0555 555 55 55 ";
                } elseif ($category == 'araba aksesuarı') {
                    return "$category için yetkili Kemal Bey, 0555 666 777 999";
                }

                return 'yetkili yok ';
            });

        $schema = new ObjectSchema(
            name: 'name_known',
            description: 'users name is given before.',
            properties: [
                new BooleanSchema('name_known', 'true if users name is known, false otherwise'),
                new StringSchema('name', 'name of the user if known, otherwise empty string'),
            ],
            requiredFields: ['name_known']
        );

        $response = Prism::structured()
            ->using(Provider::OpenAI, 'gpt-4o')
            ->usingStructuredMode(StructuredMode::Auto)
            ->withSchema($schema)
            ->withMessages($state->getMessages())
            ->withSystemPrompt('user ile konuşma geçmişini inceleyip adını bilip bilmediğine göre dönüş yap')
            ->asStructured();

        $state->addMessages($response->responseMessages, 'ExampleStep1b', 'name_known');

        $r = $response->structured;

        if (! $r['name_known']) {
            return new ExampleStep1a;
        }

        $state->getChatMemory()->setParametricMemory('name', $r['name']);

        $response = Prism::text()
            ->using(Provider::OpenAI, 'gpt-4o')
            ->withSystemPrompt('müşterinin aldığı ürün kategorisine göre indirim hesaplayan bir asistansın, indirim için discount tool unu kullan. gerekiyorsa yetkililerin bilgisini de paylaş')
            ->withMessages($state->getMessages())
            ->withTools([$tool1, $tool2])
            ->withToolChoice(ToolChoice::Auto)
            ->asText();

        $toolResultMessage = new ToolResultMessage($response->toolResults);

        $state->addMessages($response->responseMessages, 'ExampleStep1b', 'discount');
        $state->addMessage($toolResultMessage, 'ExampleStep1b', 'discount result');

        return new ExampleStep3($this);
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
