<?php

declare(strict_types=1);

namespace Snack;

use League\Plates\Engine;

abstract class Controller
{
    protected Engine $template;

    public function __construct()
    {
        $this->template = Snack::getInstance()->getTemplates();
    }
}
