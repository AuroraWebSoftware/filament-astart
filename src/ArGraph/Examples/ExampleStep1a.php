<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Examples;

use AuroraWebSoftware\FilamentAstart\ArGraph\Chat\ChatState;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Result;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\State;
use AuroraWebSoftware\FilamentAstart\ArGraph\Contracts\Step;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Enums\ToolChoice;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Prism;
use Prism\Prism\ValueObjects\Messages\ToolResultMessage;

class ExampleStep1a implements Step
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
        $tool1 = Tool::as('discount')
            ->for('müşteriye aldığı ürün kategorisine göre indirim oranı ver')
            ->withEnumParameter('category', 'müşterinin aldığı ürünün kategorisi', ['ev ürünleri', 'araba aksesuarı'])
            ->using(function (string $category): string {
                if ($category == 'ev ürünleri') {
                    return "$category için indirim oranı %10 ";
                } elseif ($category == 'araba aksesuarı') {
                    return "$category için indirim oranı %20 ";
                }
                return "indirim oranı %50 ";
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
                return "yetkili yok ";
            });

        $response = Prism::text()
            ->using(Provider::OpenAI, 'gpt-4o')
            ->withSystemPrompt('müşterinin aldığı ürün kategorisine göre indirim hesaplayan bir asistansın, indirim için discount tool unu kullan. gerekiyorsa yetkililerin bilgisini de paylaş')
            ->withMessages($state->getMessages())
            ->withTools([$tool1, $tool2])
            ->withToolChoice(ToolChoice::Auto)
            ->asText();


        $toolResultMessage = new ToolResultMessage($response->toolResults);

        $state->addMessages($response->responseMessages);
        $state->addMessage($toolResultMessage);

        return (new ExampleStep3($this));
    }


}
