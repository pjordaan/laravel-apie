<?php


namespace W2w\Laravel\Apie\Plugins\Illuminate\Encoders;

use W2w\Lib\Apie\Interfaces\FormatRetrieverInterface;

class DefaultContentTypeFormatRetriever implements FormatRetrieverInterface
{
    private $defaultFormat;

    private $defaultContentType;

    public function __construct(string $defaultFormat = 'json', string $defaultContentType = '*/*')
    {
        $this->defaultFormat = $defaultFormat;
        $this->defaultContentType = $defaultContentType;
    }

    /**
     * @param string $contentType
     * @return string|null
     */
    public function getFormat(string $contentType): ?string
    {
        if ($contentType === $this->defaultContentType) {
            return $this->defaultFormat;
        }
        return null;
    }

    /**
     * @param string $format
     * @return string|null
     */
    public function getContentType(string $format): ?string
    {
        return null;
    }
}
