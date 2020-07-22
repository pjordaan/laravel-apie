<?php


namespace W2w\Laravel\Apie\Plugins\IlluminateTranslation\Normalizers;


use Illuminate\Contracts\Translation\Translator;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\LocalizationInfo;

trait SharedTranslatorTrait
{
    /**
     * @var Translator
     */
    private $translator;

    private function translate(LocalizationInfo $localizationInfo): string
    {
        $replacements = [];
        foreach ($localizationInfo->getReplacements() as $key => $value) {
            if (is_array($value)) {
                $replacements[$key] = json_encode($value);
            } else {
                $replacements[$key] = $value;
            }
        }
        $messageString = $localizationInfo->getMessageString();
        if (strpos($messageString, '::') === false) {
            $messageString = 'apie::' . $messageString;
        }
        return $this->translator->choice(
            $messageString,
            $localizationInfo->getAmount(),
            $replacements
        );
    }
}
