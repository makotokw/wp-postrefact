<?php
namespace Postrefact;

use Symfony\Component\Filesystem\Filesystem as SfFilesystem;

class Filesystem
{
    public static function mkdir($path)
    {
        $fs = new SfFilesystem();
        $fs->mkdir($path);
    }
}
