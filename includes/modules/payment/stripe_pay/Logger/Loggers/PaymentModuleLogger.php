<?php
namespace Zencart\Logger\Loggers;

use Zencart\Logger\Logger;
use Zencart\Logger\LoggerContract;

class PaymentModuleLogger extends Logger implements LoggerContract
{

    public function pushHandlers($handlerOptions): void
    {
        if (!isset($handlerOptions['handlers'])) {
            return;
        }
        if ($handlerOptions['handlers'] == 'No') {
            return;
        }
        $logTypes = array_map('trim', explode(',', $handlerOptions['handlers']));
        if (empty($handlerOptions['handlers'])) {
            $logTypes = [];
        }
        foreach ($logTypes as $logType) {
            $className = 'Zencart\Logger\Handlers\\' . $logType . 'LoggerHandler';
            $object = new $className($this->options);
            $object->setup($this);
        }
    }
}
