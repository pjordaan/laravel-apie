## Localization
It is possible to set up Apie to become localization aware. This is integrated with the Laravel localization methods.

### Setting up localization
All you have to do is add locales in the translations config option:
```php
<?php
// config/apie.php
return [
    'translations' => ['en', 'de'],
];
```
When this is enabled several changes will happen
- the OpenApi spec will add a language option with the languages selected in the config.
- A wrong accept-language will throw a 406 not accepted error.
- If no accept-language is set the default of Laravel is being used.
- A content-language will be added to the response to tell the locale of the response.
- Any exception that is thrown and implements LocalizationableException can have a translated message.

### Add a Translation endpoint
This is very simple:
In the apie config just add the Translation entity in the resources and you will have a translation end point and an
endpoint that just calls transChoice with placeholders and some amount.

```php
<?php
// config/apie.php
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\ApiResources\Translation;
return [
    'resources' => [Translation::class],
];
```

### Changing/adding translations
First call artisan vendor:publish and publish the translations to resources/lang/vendor/apie
Feel free to add translations here. By default only english and dutch tranlations are available, but feel free to make
a PR to add an other language.

### Making exceptions with localization
Make sure you published the translations.
Localization can be done by making custom exception classes and let them implement LocalizationableException:

```php
<?php
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\LocalizationableException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\LocalizationInfo;

class ValueShouldBeEvenException extends RuntimeException implements LocalizationableException
{
    private $value;
    
    public function __construct(int $value)
    {
        $this->value = $value;
        parent::__construct('"' . $value . '" is not even');
    }

    public function getI18n(): LocalizationInfo
    {
        return new LocalizationInfo('validation.placeholder', ['value' => $this->value], $this->value);
    }
}
```

Now we will need to add the translation to resources/lang/vendor/<language>/validation.php

```php
<?php
// resources/lang/vendor/nl/validation.php
return [
    'placeholder' => 'De waarde :value is niet een even getal.'
];
```

```php
<?php
// resources/lang/vendor/en/validation.php
return [
    'placeholder' => 'The value :value is not an even number.'
];
```
### Translation namespaces
Normally all translations are taken from the apie namespace. If you want to use a different language namespace, you need
to provide it in the getI18n method:

```php
<?php
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\LocalizationableException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\LocalizationInfo;

class SomeException extends RuntimeException implements LocalizationableException
{
    public function getI18n() : LocalizationInfo
    {
        return new LocalizationInfo('other::test.blah');
    }
}
```
SomeException will have a translation in the 'other' namespace.

### Adding translations for vendor exceptions
For vendor exceptions it is not possible to use the LocalizationableException interface as you can not modify them.

For that the only solution is writing your own Normalizer:

```php
<?php
use Illuminate\Contracts\Translation\Translator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use W2w\Lib\Apie\Plugins\Core\Normalizers\ExceptionNormalizer;

class VendorExceptionNormalizer implements NormalizerInterface
{
    private $translator;
    
    private $exceptionNormalizer;

    public function __construct(Translator $translator, ExceptionNormalizer $exceptionNormalizer)
    {
        $this->translator = $translator;
        $this->exceptionNormalizer = $exceptionNormalizer;
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof VendorException;
    }
    
    public function normalize($object,  string $format = null,  array $context = [])    
    {
        $data = $this->exceptionNormalizer->normalize($object, $format, $context);
        $data['message'] = $this->translator->get('validation.example');
        return $data;
    }
}
```
In a Service provider register do not forget to tag the class to auto-register this normalizer.

```php
<?php
// app/Providers/AppServiceProvider.php

use Illuminate\Support\ServiceProvider;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AppServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $this->app->tag([VendorExceptionNormalizer::class], [NormalizerInterface::class]);
    }
}
```
