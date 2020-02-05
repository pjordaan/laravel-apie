<?php

namespace W2w\Laravel\Apie\Services;

use W2w\Lib\Apie\Plugins\FileStorage\DataLayers\FileStorageDataLayer;

class FileStorageDataLayerContainer
{
    /**
     * @var ApieContext
     */
    private $apieContext;

    /**
     * @var string
     */
    private $storagePath;

    /**
     * @var FileStorageDataLayer[]
     */
    private $instantiated = [];

    public function __construct(string $storagePath, ApieContext $apieContext)
    {
        $this->storagePath = $storagePath;
        $this->apieContext = $apieContext;
    }

    public function getCurrentFileStorageDataLayer(): FileStorageDataLayer
    {
        $apieContext = $this->apieContext->getActiveContext();
        $apie = $apieContext->getApie();

        $context = implode('.', $apieContext->getContextKey());
        // make sure we keep the root Apie instance in a separate folder.
        if (!$context) {
            $context = '-';
        }
        if (!isset($this->instantiated[$context])) {
            $this->instantiated[$context] = new FileStorageDataLayer(
                $this->storagePath . DIRECTORY_SEPARATOR . $context,
                $apie->getPropertyAccessor()
            );
        }
        return $this->instantiated[$context];
    }
}
