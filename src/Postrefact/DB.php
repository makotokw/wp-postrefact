<?php

namespace Postrefact;

class DB
{
    static protected $tablePrefix;

    public static function setTablePrefix($prefix)
    {
        self::$tablePrefix = $prefix;
    }

    public static function t($tableName)
    {
        return self::$tablePrefix . $tableName;
    }

    /**
     * @return \PDO
     */
    public static function getConnection()
    {
        /**
         * @var \PDO $conn
         */
        static $conn = null;
        if (!$conn) {
            $dsn = sprintf('mysql:dbname=%s;host=%s', DB_NAME, DB_HOST);
            $conn = new \PDO(
                $dsn,
                DB_USER,
                DB_PASSWORD,
                array(
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET ' . DB_CHARSET . ';',
                )
            );
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        return $conn;
    }


    public static function findTermAll()
    {
        static $terms = null;
        if (!$terms) {
            $conn = self::getConnection();
            $stmt = $conn->prepare(
                'SELECT t.term_id, t.name, tx.term_taxonomy_id, tx.taxonomy'
                . ' FROM ' . DB::t('terms') . ' AS t'
                . ' INNER JOIN ' . DB::t('term_taxonomy') . ' AS tx ON tx.term_id = t.term_id'
                . ' WHERE 1'
            );

            if ($stmt->execute()) {
                $terms = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $terms = array();
            }
        }
        return $terms;
    }

    public static function findTermByTaxonomyAndName($taxonomy, $name)
    {
        foreach (self::findTermAll() as $t) {
            if ($t['taxonomy'] == $taxonomy && $t['name'] == $name) {
                return $t;
            }
        }
        return false;
    }

    public static function removeTermRelationsByTaxonomy($postId, $taxonomy)
    {
        static $stmt = null;
        if (!$stmt) {
            $conn = self::getConnection();
            $stmt = $conn->prepare(
                'DELETE FROM ' . DB::t('term_relationships')
                . ' WHERE object_id = ?'
                . ' AND term_taxonomy_id IN '
                . ' (SELECT term_taxonomy_id FROM ' . DB::t('term_taxonomy') . ' WHERE taxonomy = ?)'
            );
        }
        $stmt->execute(array($postId, $taxonomy));
    }

    public static function addTermRelation($postId, $termTaxonomyId)
    {
        static $stmt = null;
        if (!$stmt) {
            $conn = self::getConnection();
            $stmt = $conn->prepare(
                'INSERT INTO ' . DB::t('term_relationships') . ' (object_id, term_taxonomy_id)'
                . ' VALUES (?, ?)'
            );
        }
        if ($postId > 0 && $termTaxonomyId > 0) {
            $stmt->execute(array($postId, $termTaxonomyId));
        }
    }

    public static function findPost($id)
    {
        static $stmt = null;
        if (!$stmt) {
            $conn = self::getConnection();
            $stmt = $conn->prepare(
                'SELECT *'
                . ' FROM ' . DB::t('posts')
                . ' WHERE ID = ?'
            );
        }
        if ($stmt->execute(array($id))) {
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        }
        return false;
    }

    public static function findTaxonomyByPostId($postId)
    {
        static $stmt = null;
        if (!$stmt) {
            $conn = self::getConnection();
            $stmt = $conn->prepare(
                'SELECT t.name, tx.taxonomy'
                . ' FROM ' . DB::t('terms') . ' AS t'
                . ' INNER JOIN ' . DB::t('term_taxonomy') . ' AS tx ON tx.term_id = t.term_id'
                . ' INNER JOIN ' . DB::t('term_relationships') . ' AS r ON r.term_taxonomy_id = tx.term_taxonomy_id'
                . ' WHERE r.object_id = ?'
            );
        }
        $taxonomy = array();
        if ($stmt->execute(array($postId))) {
            while ($t = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if (is_null(@$taxonomy[$t['taxonomy']])) {
                    $taxonomy[$t['taxonomy']] = array();
                }
                $taxonomy[$t['taxonomy']][] = $t['name'];
            }
        }
        return $taxonomy;
    }


    public static function updateTaxonomyCount()
    {
        $term_taxonomy = self::t('term_taxonomy');
        $term_relationships = self::t('term_relationships');
        $sql = <<<EOT
UPDATE $term_taxonomy SET count =
  (SELECT COUNT(*) FROM $term_relationships WHERE term_taxonomy_id = $term_taxonomy.term_taxonomy_id)
 WHERE 1;
EOT;
        $conn = self::getConnection();
        $conn->query($sql);
        $stmt = $conn->query($sql);
        return ($stmt) ? $stmt->rowCount() : 0;
    }

    public static function updateTaxonomyPublishCount()
    {
        $term_taxonomy = self::t('term_taxonomy');
        $term_relationships = self::t('term_relationships');
        $posts = self::t('posts');

        $sql = <<<EOT
UPDATE $term_taxonomy SET count =
	(SELECT count FROM (SELECT r.term_taxonomy_id, COUNT(*) AS count FROM $term_relationships r
 INNER JOIN $posts p ON p.ID = r.object_id
 WHERE  p.post_type = 'post' AND p.post_status = 'publish'
 GROUP BY r.term_taxonomy_id
) AS x WHERE term_taxonomy_id = $term_taxonomy.term_taxonomy_id) WHERE 1;
EOT;
        $conn = self::getConnection();
        $stmt = $conn->query($sql);
        return ($stmt) ? $stmt->rowCount() : 0;
    }
}
