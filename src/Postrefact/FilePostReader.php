<?php
namespace Postrefact;

use Symfony\Component\Yaml\Yaml;

class FilePostReader
{
    /**
     * @var string
     */
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * bool array_walk ( array &$array , callable $funcname [, mixed $userdata = NULL ] )
     */
    /**
     * @param callable $funcname
     */
    public function walk($funcname)
    {
        $fh = fopen($this->path, 'r');

        if (!$fh) {
            return;
        }

        $currentBlock = '';
        $meta = array();
        $content = array();
        $expect = array();

        /**
         * @param array $meta
         * @param array $content
         * @param array $expect
         */
        $fnEntry = function ($meta, $content, $expect) use ($funcname) {
            $metaYaml = implode('', $meta);
            $post = Yaml::parse($metaYaml);

            foreach (array('post_title') as $key) {
                $post[$key] = htmlspecialchars_decode($post[$key], ENT_QUOTES);
            }

            // trim lastline
            if ($contentCount = count($content)) {
                $content[$contentCount - 1] = rtrim($content[$contentCount - 1], "\n");
            }
            if ($expectCount = count($expect)) {
                $expect[$expectCount - 1] = rtrim($expect[$expectCount - 1], "\n");
            }
            $post['post_content'] = implode('', $content);
            $post['post_expect'] = implode('', $expect);
            $funcname($post);
            unset($post);
        };

        while (false !== ($line = fgets($fh))) {
            if (preg_match('/^!postrefact::post::([\w]+)/', $line, $match)) {
                $block = $match[1];
                switch ($block) {
                    case 'meta':
                        // do prevEntry
                        if (!empty($meta)) {
                            $fnEntry($meta, $content, $expect);
                        }
                        unset($meta, $content, $expect);
                        $meta = array();
                        $content = array();
                        $expect = array();
                        break;
                    case 'content':
                    case 'expect':
                        break;
                    default:
                        echo 'Unknown' . PHP_EOL;
                        exit;
                }
                $currentBlock = $block;
            } else {
                switch ($currentBlock) {
                    case 'meta':
                        $meta[] = $line;
                        break;
                    case 'content':
                        $content[] = $line;
                        break;
                    case 'expect':
                        $expect[] = $line;
                        break;
                }
            }
        }

        // do lastEntry
        if (!empty($meta)) {
            $fnEntry($meta, $content, $expect);
        }

        fclose($fh);
    }
}
