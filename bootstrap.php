<?php

$loader = require_once __DIR__ . '/vendor/autoload.php';

use Postrefact\DB;

{
    global $table_prefix;
    require_once __DIR__ . '/config.php';
    DB::setTablePrefix($table_prefix);
}

/**
 * @param string $viewPath
 * @return Twig_Environment
 */
function getTwig($viewPath = '')
{
    static $twig = null;
    if (is_null($twig)) {
        if (empty($viewPath)) {
            $viewPath = __DIR__ . '/views';
        }
        $loader = new Twig_Loader_Filesystem($viewPath);
        $twig = new Twig_Environment(
            $loader,
            array(
                'debug' => true,
                'cache' => __DIR__ . '/_cache',
            )
        );
    }
    return $twig;
}
