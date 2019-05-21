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
     * @param string $path
     * @return string
     */
    public static function formatPath(string $path)
    {
        //$path = '/' . '/';
        $path = preg_replace('/\s+/','/', $path); // removes whitespaces
        $path = preg_replace('#/+#','/', $path); // removes extra slashes
        $path = trim($path, '/'); // removes the slashes around the path

        // if the path becomes empty after trimming, we add a single slash
        if (empty($path)) {
            $path = '/';
        }

        return $path;
    }

}
