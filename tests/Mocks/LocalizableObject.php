<?php


namespace W2w\Laravel\Apie\Tests\Mocks;


use W2w\Laravel\Apie\Plugins\IlluminateTranslation\ValueObjects\LocaleAwareString;

class LocalizableObject
{
    private $data;

    public function __construct()
    {
        $this->data = new LocaleAwareString();
    }

    public function setDescription(LocaleAwareString $localeAwareString)
    {
        $this->data = $this->data->merge($localeAwareString);
    }

    public function getDescription(): LocaleAwareString
    {
        return $this->data;
    }
}
