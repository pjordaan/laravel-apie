<?php

namespace W2w\Laravel\Apie\Services\StatusChecks;

use ArrayIterator;
use Throwable;
use W2w\Laravel\Apie\Models\Status;
use W2w\Lib\Apie\ApiResources\Status as ResourceStatus;
use W2w\Lib\Apie\StatusChecks\StaticStatusCheck;
use W2w\Lib\Apie\StatusChecks\StatusCheckListInterface;

/**
 * Adds an extra check for the Status api resource to do a database check. Any record stored in the database
 * will be a status check report. Any background process can add/remove status checks.
 */
class StatusFromDatabaseRetriever implements StatusCheckListInterface
{
    private $debug;

    /**
     * @param bool $debug
     */
    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        try {
            $statuses = Status::where([])->get();
        } catch (Throwable $t) {
            return new ArrayIterator(
                [
                new StaticStatusCheck(
                    new ResourceStatus(
                        'database-test',
                        'Can not connect to database',
                        null,
                        [
                            'exception' => $t->getMessage(),
                            'trace'     => $this->debug ? $t->getTraceAsString() : null,
                        ]
                    )
                ),
                ]
            );
        }
        $list = array_map(
            function (Status $statusModel) {
                return $this->convert($statusModel);
            }, iterator_to_array($statuses)
        );
        $list[] = new StaticStatusCheck(
            new ResourceStatus(
                'database-test',
                'OK'
            )
        );

        return new ArrayIterator($list);
    }

    /**
     * Converts a Eloquent Status into a StaticStatusCheck.
     *
     * @param  Status $statusModel
     * @return StaticStatusCheck
     */
    private function convert(Status $statusModel): StaticStatusCheck
    {
        $context = json_decode($statusModel->context, true);
        if (!is_array($context)) {
            $context = null;
        }
        $statusResource = new ResourceStatus(
            $statusModel->id,
            $statusModel->status,
            $statusModel->optional_reference,
            $context
        );

        return new StaticStatusCheck($statusResource);
    }
}
