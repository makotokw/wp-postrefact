<?php

namespace Postrefact;

class Writer
{
    /**
     * @var resource
     */
    protected $fh;

    public function __construct($path)
    {
        $this->fh = fopen($path, 'w');
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param string $content
     */
    public function write($content)
    {
        fwrite($this->fh, $content);
    }

    public function close()
    {
        if ($this->fh) {
            fclose($this->fh);
            $this->fh = null;
        }
    }
}
