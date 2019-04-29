<?php

namespace hiqdev\recon\core\Exception;

/**
 * Class SkipTaskException represents error, that should silently skip
 * current task execution. For example, this exception MAY happen when
 * the task that is about to start is already processing by other thread.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class SkipTaskException extends ReconException
{
}
