<?php


namespace W2w\Laravel\Apie\Services;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use W2w\Laravel\Apie\Exceptions\ApieContextMissingException;
use W2w\Laravel\Apie\Plugins\Illuminate\IlluminatePlugin;
use W2w\Lib\Apie\Apie;
use W2w\Lib\Apie\Exceptions\BadConfigurationException;
use W2w\Lib\Apie\Plugins\FakeAnnotations\FakeAnnotationsPlugin;

final class ApieContext
{
    private $contextKey = [];

    private $container;

    private $apie;

    private $config;

    private $contexts;

    private $instantiatedContexts;

    public function __construct(Container $container, Apie $apie, array $config, array& $contexts)
    {var_dump(__METHOD__);
        $this->container = $container;
        $this->apie = $apie;
        $this->config = $config;
        $this->contexts = &$contexts;
    }

    public function getApie(): Apie
    {
        return $this->apie;
    }

    public function getActiveContext(): ApieContext
    {
        if ($this->container->bound(Request::class)) {
            /** @var Request $request */
            $request = $this->container->get(Request::class);
            $contexts = $request->route('context');
            if (is_string($contexts) && $contexts) {
                $contexts = explode('.', $contexts);
            }
            if (is_array($contexts)) {
                $context = $this;
                while ($contexts && $context) {
                    $key = array_shift($contexts);
                    $context = $context->getContext($key);
                }
                if ($context) {
                    return $context;
                }
            }
        }
        return $this;
    }

    public function getContext(string $context): ApieContext
    {
        // done for potential file read exploits
        if (!preg_match('/^[a-z0-9-]+$/i', $context)) {
            throw new BadConfigurationException('Context "' . $context . '" is not a valid context name!');
        }
        if (!isset($this->instantiatedContexts[$context])) {
            $this->instantiatedContexts[$context] = $this->createContext($context);
        }
        return $this->instantiatedContexts[$context];
    }

    private function createContext(string $context): ApieContext
    {
        if (!isset($this->contexts[$context])) {
            throw new ApieContextMissingException($context);
        }
        $plugins = [];
        foreach ($this->contexts[$context]['plugins'] as $plugin) {
            $plugins[] = $this->container->make($plugin);
        }
        if (!empty($this->contexts[$context]['resource-config'])) {
            $plugins[] = new FakeAnnotationsPlugin($this->contexts[$context]['resource-config']);
        }
        $plugins[] = new IlluminatePlugin($this->container, $this->contexts[$context]);
        $apie = $this->apie->createContext($plugins);
        $apieContext = new ApieContext(
            $this->container,
            $apie,
            $this->contexts[$context],
            $this->contexts[$context]['contexts']
        );
        $key = $this->contextKey;
        $key[] = $context;
        $apieContext->contextKey = $key;
        return $apieContext;
    }

    public function getConfig(string $key)
    {
        return $this->config[$key];
    }

    public function getContextKey(): array
    {
        return $this->contextKey;
    }

    /**
     * @return ApieContext[]
     */
    public function allContexts(): array
    {
        $res = [];
        foreach (array_keys($this->contexts) as $context) {
            $res[$context] = $this->getContext($context);
        }
        return $res;
    }
}
