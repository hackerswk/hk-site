<?php
/**
 * Site config class
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\HkSite;

use \Exception as Exception;
use \PDO as PDO;

class Site
{
    /**
     * database
     *
     * @var object
     */
    private $database;

    /**
     * initialize
     */
    public function __construct($db = null)
    {
        $this->database = $db;
    }

    /**
     * Get site info of sites.
     *
     * @param string $site_id
     * @param bool $is_public
     * @return array
     */
    public function getSite($site_id, $is_public = 1)
    {
        $sql = <<<EOF
            SELECT * FROM sites
            WHERE id = :site_id AND is_deleted = 0 AND is_public = :is_public
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':site_id' => $site_id,
            ':is_public' => $is_public,
        ]);

        if ($query->rowCount() > 0) {
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result;
        }

        return [];
    }

    /**
     * Get site member config of site_member_config.
     *
     * @param string $site_id
     * @return array
     */
    public function getSiteMemberConfig($site_id)
    {
        $sql = <<<EOF
            SELECT * FROM site_member_config
            WHERE site_id = :site_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':site_id' => $site_id,
        ]);

        if ($query->rowCount() > 0) {
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result;
        }

        return [];
    }

    /**
     * Get site meta of site_meta.
     *
     * @param string $site_id
     * @return array
     */
    public function getSiteMeta($site_id)
    {
        $sql = <<<EOF
            SELECT * FROM site_meta
            WHERE site_id = :site_id
EOF;
        $query = $this->database->prepare($sql);
        $query->execute([
            ':site_id' => $site_id,
        ]);

        if ($query->rowCount() > 0) {
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result;
        }

        return [];
    }

    /**
     * Confirm if the site config profile exists.
     *
     * @return bool
     */
    public function checkSiteConfig($site_code, $path)
    {
        $json_file = $path . '/' . $site_code . '.json';
        if (file_exists($json_file)) {
            return true;
        }

        return false;
    }

    /**
     * Cet site config.
     *
     * @return array
     */
    public function getSiteConfig($site_code, $path)
    {
        $json_file = $path . '/' . $site_code . '.json';
        if (file_exists($json_file)) {
            $json_data = file_get_contents($json_file);
            $data = json_decode($json_data, true);
            return $data;
        }

        return [];
    }

    /**
     * Create a json file of the site config.
     *
     * @return bool
     */
    public function setSiteConfig($site_id, $is_public, $path)
    {
        try {
            $data = array(
                'site' => $this->getSite($site_id, $is_public),
                'site_member_config' => $this->getSiteMemberConfig($site['site_id']),
                'siet_meta' => $this->getSiteMeta($site['site_id']),
            );
            $json_data = json_encode($data);
            $json_file = $path . '/' . $site['site_code'] . '.json';
            if (file_put_contents($json_file, $json_data)) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return false;
    }
}
