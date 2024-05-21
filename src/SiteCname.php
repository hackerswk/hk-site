<?php

/**
 * Class for handling CRUD operations on the site_cname table using PDO.
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\HkSite;

use \PDO as PDO;
use \PDOException as PDOException;

class SiteCname
{
    /** @var PDO Database connection */
    private $pdo;

    /**
     * Constructor.
     *
     * @param PDO $pdo PDO database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new entry in the site_cname table.
     *
     * @param int $siteId The site ID.
     * @param string $cname The CNAME.
     * @param string $cvalue The CVALUE.
     * @return bool True on success, false on failure.
     */
    public function create($siteId, $cname, $cvalue)
    {
        $sql = <<<EOF
        INSERT INTO site_cname (site_id, cname, cvalue)
        VALUES (:site_id, :cname, :cvalue)
EOF;
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':site_id' => $siteId,
                ':cname' => $cname,
                ':cvalue' => $cvalue,
            ]);
        } catch (PDOException $e) {
            die("Create failed: " . $e->getMessage());
        }
    }

    /**
     * Read entries from the site_cname table.
     *
     * @param int $siteId The site ID.
     * @return array The resulting entries.
     */
    public function read($siteId)
    {
        $sql = <<<EOF
        SELECT * FROM site_cname WHERE site_id = :site_id
EOF;
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':site_id' => $siteId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Read failed: " . $e->getMessage());
        }
    }

    /**
     * Update an entry in the site_cname table.
     *
     * @param int $siteId The site ID.
     * @param string $cname The CNAME.
     * @param string $cvalue The new CVALUE.
     * @return bool True on success, false on failure.
     */
    public function update($siteId, $cname, $cvalue)
    {
        $sql = <<<EOF
        UPDATE site_cname
        SET cvalue = :cvalue
        WHERE site_id = :site_id AND cname = :cname
EOF;
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':site_id' => $siteId,
                ':cname' => $cname,
                ':cvalue' => $cvalue,
            ]);
        } catch (PDOException $e) {
            die("Update failed: " . $e->getMessage());
        }
    }

    /**
     * Delete an entry from the site_cname table.
     *
     * @param int $siteId The site ID.
     * @param string $cname The CNAME.
     * @return bool True on success, false on failure.
     */
    public function delete($siteId, $cname)
    {
        $sql = <<<EOF
        DELETE FROM site_cname
        WHERE site_id = :site_id AND cname = :cname
EOF;
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':site_id' => $siteId,
                ':cname' => $cname,
            ]);
        } catch (PDOException $e) {
            die("Delete failed: " . $e->getMessage());
        }
    }

    /**
     * Check if a CNAME exists in the site_cname table.
     *
     * @param string $cname The CNAME.
     * @return bool True if the CNAME exists, false otherwise.
     */
    public function checkCname($cname)
    {
        $sql = <<<EOF
        SELECT COUNT(*) FROM site_cname WHERE cname = :cname
EOF;
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':cname' => $cname]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            die("Check CNAME failed: " . $e->getMessage());
        }
    }
}
