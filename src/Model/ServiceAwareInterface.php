<?php

namespace hiqdev\recon\core\Model;

interface ServiceAwareInterface
{
    public function getService(): Service;
}
