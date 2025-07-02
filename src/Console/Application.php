<?php

declare(strict_types=1);

namespace Anchor\DocsCoverage\Console;

use Anchor\DocsCoverage\Console\Command\AnalyzeCommand;
use Symfony\Component\Console\Application as BaseApplication;

final class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('Anchor Documentation Coverage Analyzer', '1.0.0');
        
        $this->add(new AnalyzeCommand());
    }
} 