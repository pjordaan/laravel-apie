<?php

namespace W2w\Laravel\Apie\Console;

use Illuminate\Console\Command;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSpecGenerator;

class DumpOpenApiSpecCommand extends Command
{
    protected $signature = 'apie:dump-open-api {filename}';

    protected $description = 'Dumps openapi spec as JSON or yaml to a file.';

    public function handle(OpenApiSpecGenerator $generator)
    {
        $filename = $this->argument('filename');
        if (preg_match('/\.y[a]{0,1}ml$/i', $filename)) {
            $content = $this->createYamlContent($generator);
        } else {
            $content = $this->createJsonContent($generator);
        }
        if (@file_put_contents($this->argument('filename'), $content)) {
            $this->output->writeln('Created file "' . $this->argument('filename') . '" successfully!');
        } else {
            $this->output->error('Could not write file "' . $this->argument('filename') . '"!');
            return false;
        }
        return true;
    }

    private function createYamlContent(OpenApiSpecGenerator $generator): string
    {
        return $generator->getOpenApiSpec()->toYaml(20, 2);
    }

    private function createJsonContent(OpenApiSpecGenerator $generator): string
    {
        return json_encode($generator->getOpenApiSpec()->toArray(), JSON_UNESCAPED_SLASHES);
    }
}
