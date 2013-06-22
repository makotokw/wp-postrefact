<?php

$loader = require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/bootstrap.php';

use Postrefact\DB;
use Postrefact\Writer;
use Postrefact\FilePostReader;
use Postrefact\DatabasePostReader;
use Postrefact\Filesystem;

export(__DIR__ . '/_export');

function export($outputDir)
{
    $twig = getTwig();
    Filesystem::mkdir($outputDir);

    $postReader = new DatabasePostReader(
        'SELECT *'
        . ' FROM ' . DB::t('posts')
        . ' WHERE post_status = "publish"'
        . ' AND post_type IN ("post", "page")'
        . ' ORDER BY post_date'
    );

    $postTemplate = $twig->loadTemplate('postrefact.html.twig');
    $postWriter = new Writer($outputDir . DIRECTORY_SEPARATOR . 'post.html');
    $pageWriter = new Writer($outputDir . DIRECTORY_SEPARATOR . 'page.html');

    $postReader->read(
        function ($post) use ($postTemplate, $postWriter, $pageWriter) {

            switch ($post['post_type']) {
                case 'post':
                    $postWriter->write(
                        $postTemplate->render(compact('post'))
                    );
                    break;
                case 'page':
                    $pageWriter->write(
                        $postTemplate->render(compact('post'))
                    );
                    break;
            }
        }
    );
}

function update($inputDir)
{
    $conn = DB::getConnection();
    $posts = DB::t('posts');
    $stmtUpdatePost = $conn->prepare(
        <<<EOT
UPDATE $posts
    SET post_title = ?, post_content = ?
    WHERE ID = ?
EOT
    );

    $fnUpdate = function ($post) use ($stmtUpdatePost) {

        // update post
        $stmtUpdatePost->execute(
            array(
                $post['post_title'],
                $post['post_content'],
                $post['ID'],
            )
        );

        // update taxonomy relations
        DB::removeTermRelationsByTaxonomy($post['ID'], 'category');
        DB::removeTermRelationsByTaxonomy($post['ID'], 'post_tag');
        DB::removeTermRelationsByTaxonomy($post['ID'], 'portfolios');
        if (!empty($post['category'])) {
            if ($term = DB::findTermByTaxonomyAndName('category', $post['category'])) {
                DB::addTermRelation($post['ID'], $term['term_taxonomy_id']);
            } else {
                echo sprintf('Unknown "%s" as category.', $post['category']) . PHP_EOL;
            }
        }
        if (!empty($post['post_tag'])) {
            foreach ($post['post_tag'] as $tagName) {
                if ($tagName != '') {
                    if ($term = DB::findTermByTaxonomyAndName('post_tag', $tagName)) {
                        DB::addTermRelation($post['ID'], $term['term_taxonomy_id']);
                    } else {
                        echo sprintf('Unknown "%s" as tag.', $tagName) . PHP_EOL;
                    }
                }
            }
        }
        if (!empty($post['portfolios'])) {
            if (!is_array($post['portfolios'])) {
                $post['portfolios'] = array($post['portfolios']);
            }
            foreach ($post['portfolios'] as $termName) {
                if ($termName != '') {
                    if ($term = DB::findTermByTaxonomyAndName('portfolios', $termName)) {
                        DB::addTermRelation($post['ID'], $term['term_taxonomy_id']);
                    } else {
                        echo sprintf('Unknown "%s" as portfolios.', $termName) . PHP_EOL;
                    }
                }
            }
        }
    };

    foreach (array('post', 'page') as $postType) {
        $inputFile = $inputDir . DIRECTORY_SEPARATOR . $postType . '.html';
        if (!file_exists($inputFile)) {
            continue;
        }
        $reader = new FilePostReader($inputFile);
        $reader->walk(
            function ($post) use ($fnUpdate) {
                $fnUpdate($post);
            }
        );
    }
    DB::updateTaxonomyPublishCount();
}
