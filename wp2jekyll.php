<?php

define('UN_CATEGORY_LABEL', 'Uncategorized');

require_once __DIR__ . '/bootstrap.php';

use Postrefact\DB;
use Postrefact\DatabasePostReader;
use Postrefact\Filesystem;

export(__DIR__ . '/_export');

function export($outputDir)
{
    $twig = getTwig();

    $pageOutputDir = $outputDir;
    $postOutputDir = $outputDir . DIRECTORY_SEPARATOR . '_posts';
    Filesystem::mkdir($pageOutputDir);
    Filesystem::mkdir($postOutputDir);

    $postReader = new DatabasePostReader(
        'SELECT *'
        . ' FROM ' . DB::t('posts')
        . ' WHERE post_status = "publish"'
        . ' AND post_type IN ("post", "page")'
        . ' ORDER BY post_date'
    );

    $postTemplate = $twig->loadTemplate('jekyll.post.html.twig');
    $postReader->read(
        function ($post) use ($pageOutputDir, $postOutputDir, $postTemplate) {
            if (empty($post['post_content'])) {
                return;
            }

            // remove uncategorized
            if (is_array($post['category'])) {
                $post['category'] = array_filter(
                    $post['category'],
                    function ($c) {
                        if ($c == UN_CATEGORY_LABEL) {
                            return false;
                        }
                        return true;
                    }
                );
            }

            switch ($post['post_type']) {
                case 'post':
                    // default filename is Y-m-d-base-name.html
                    $filename = sprintf('%s-%s.html', date('Y-m-d', strtotime($post['post_date'])), $post['post_name']);
                    file_put_contents(
                        $postOutputDir . DIRECTORY_SEPARATOR . $filename,
                        $postTemplate->render(compact('post'))
                    );
                    break;
                case 'page':
                    $filename = sprintf('%s.html', $post['post_name']);
                    file_put_contents(
                        $pageOutputDir . DIRECTORY_SEPARATOR . $filename,
                        $postTemplate->render(compact('post'))
                    );
                    break;
            }
        }
    );
}

