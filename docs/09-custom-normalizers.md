# Custom normalizers
Apie uses the symfony serializer. The symfony serializer allows custom normalization with Normalizers and Denormalizers.

You can find the basics in the [Symfony documentation](https://symfony.com/doc/current/serializer/custom_normalizer.html)

It's highly recommended to write an Apie plugin instead if you want to reuse it outside Laravel. This works because
laravel-apie is internally using a IlluminatePlugin to link to the laravel application.

For example we could make a normalizer to convert a laravel paginator object to an array:
```php
<?php
use Illuminate\Pagination\AbstractPaginator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class LaravelPaginatorNormalizer implements NormalizerInterface
{
    /**
     * @param AbstractPaginator $object
     * @param string|null $format
     * @param array $context
     * 
     * @return array
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        return [
            'count' => $object->count(),
            'current' => $object->currentPage(),
            'options' => $object->getUrlRange(0, $object->count()),
            'items' => $object->items()
        ];
    }
    
    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof AbstractPaginator;
    }
}
```

In a service provide we need to register the normalizer and tag it so laravel-apie can find this class:

```php
<?php
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(LaravelPaginatorNormalizer::class);
        $this->app->tag([LaravelPaginatorNormalizer::class], NormalizerInterface::class);
    }
}
```
