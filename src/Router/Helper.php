<?php

namespace Artyum\Router;

/**
 * Class Helper
 * @package Artyum\Router
 */
class Helper
{

    /**
     * Removes unneeded slashes.
     *
     * @param string $uri
     * @return string
     */
    public static function formatUri(string $uri)
    {
        //$path = '/' . '/';
        $uri = preg_replace('/\s+/','/', $uri); // removes whitespaces
        $uri = preg_replace('#/+#','/', $uri); // removes extra slashes
        $uri = trim($uri, '/'); // removes the slashes around the path

        // if the path becomes empty after trimming, we add a single slash
        if (empty($uri)) {
            $uri = '/';
        }

        return $uri;
    }

}
