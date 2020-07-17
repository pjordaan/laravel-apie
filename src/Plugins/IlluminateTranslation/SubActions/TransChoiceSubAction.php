<?php

namespace W2w\Laravel\Apie\Plugins\IlluminateTranslation\SubActions;

use Illuminate\Contracts\Translation\Translator;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\ApiResources\Translation;

class TransChoiceSubAction
{
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function handle(Translation $translation, array $replace = [], int $amount = 1): string
    {
        return $this->translator->transChoice($translation->getId(), $amount, $replace);
    }
}
