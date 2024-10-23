<?php

namespace Akeneo\Pim\Enrichment\Bundle\Controller\InternalApi;

use Symfony\Component\HttpFoundation\RedirectResponse;

class InternalApiController
{
    public function __construct() {}

    public function isXmlHttpRequest($request){
        if (!$request->isXmlHttpRequest()) {
            return new RedirectResponse('/');
        }
    }
}
