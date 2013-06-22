<?php

namespace Postrefact;

use Symfony\Component\Yaml\Yaml;

class DatabasePostReader
{
    /**
     * @var \PDOStatement
     */
    protected $stmt;

    /**
     * @param string $query
     */
    public function __construct($query = '')
    {
        $conn = DB::getConnection();

        if ($query != '') {
            $stmt = $conn->prepare($query);
        } else {
            $stmt = $conn->prepare(
                'SELECT *'
                . ' FROM ' . DB::t('posts')
                . ' WHERE post_status = "publish"'
                . ' AND post_type IN ("post", "page")'
                . ' ORDER BY post_date'
            );
        }

        $this->stmt = $stmt;
    }

    /**
     * @param callable $funcName
     * @param array $params
     */
    public function read($funcName, $params = array())
    {
        $this->stmt->execute($params);
        while ($post = $this->stmt->fetch(\PDO::FETCH_ASSOC)) {
            $taxonomy = DB::findTaxonomyByPostId($post['ID']);
            $post = array_merge($post, $taxonomy);
            $funcName($post);
        }
    }
}


