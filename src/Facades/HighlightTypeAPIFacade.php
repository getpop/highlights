<?php

declare(strict_types=1);

namespace PoPSchema\Highlights\Facades;

use PoPSchema\Highlights\TypeAPIs\HighlightTypeAPIInterface;
use PoP\Root\Container\ContainerBuilderFactory;

class HighlightTypeAPIFacade
{
    public static function getInstance(): HighlightTypeAPIInterface
    {
        return ContainerBuilderFactory::getInstance()->get('highlight_type_api');
    }
}
