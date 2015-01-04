<?php
/**
 * Copyright (C) 2015 David Young
 *
 * Defines the interface for route filters to implement
 */
namespace RDev\Routing\Filters;
use RDev\HTTP;
use RDev\Routing\Routes;

interface IFilter
{
    /**
     * Runs the filter
     *
     * @param Routes\CompiledRoute $route The route that is calling the filter
     * @param HTTP\Request $request The current HTTP request
     * @param HTTP\Response|null $response The response if this filter is being used as a post-filter, otherwise null
     * @return HTTP\Response|null The response if the filter must issue one, otherwise null
     */
    public function run(Routes\CompiledRoute $route, HTTP\Request $request, HTTP\Response $response = null);
}