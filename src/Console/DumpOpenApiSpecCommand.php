<?php

namespace W2w\Laravel\Apie\Console;

use Illuminate\Console\Command;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSpecGenerator;

class DumpOpenApiSpecCommand extends Command
{
    protected $signature = 'apie:dump-open-api {filename}';

    protected $description = 'Dumps openapi spec as JSON to a file.';

    public function handle(OpenApiSpecGenerator $generator)
    {
        $content = $generator->getOpenApiSpec()->toArray();
        if (@file_put_contents($this->argument('filename'), json_encode($content))) {
            $this->output->writeln('Created file "' . $this->argument('filename') . '" successfully!');
        } else {
            $this->output->error('Could not write file "' . $this->argument('filename') . '"!');
            return false;
        }
        return true;
    }
}
