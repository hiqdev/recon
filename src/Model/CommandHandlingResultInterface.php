<?php

namespace hiqdev\recon\core\Model;

use hiapi\commands\BaseCommand;

/**
 * Interface CommandHandlingResultInterface represents result of command handling.
 *
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
interface CommandHandlingResultInterface
{
    /**
     * @return BaseCommand|null the command being handled
     */
    public function command(): ?BaseCommand;

    /**
     * @return string|null the debug output of command handling
     */
    public function stdout(): ?string;

    /**
     * @return string|null the error output of command handling
     */
    public function stderr(): ?string;

    /**
     * If the task was handled using remote handler, the getter MUST provide
     * the remote task ID.
     *
     * @return string|null
     */
    public function remoteId(): ?string;

    /**
     * Whether task execution succeeded.
     *
     * Possible values:
     *
     * - boolean `true`: task has been successfully executed
     * - boolean `false`: task execution has been finished with error
     * - `null`: task execution result is not known yet. Task execution continues in background/asynchronously.
     *
     * @return bool|null
     */
    public function isSuccess(): ?bool;
}
