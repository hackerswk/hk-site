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
     * @var PDO|null The database connection.
     */
    private $database;

    /**
     * Site constructor.
     *
     * @param PDO|null $db The PDO database connection object.
     */
    public function __construct($db = null)
    {
        $this->database = $db;
    }

    /**
     * Get site info of sites.
     *
     * @param string $site_id The ID of the site.
     * @param bool $is_public Flag indicating whether the site is public (default: true).
     * @return array The site information as an associative array.
     */
    public function getSite($site_id, $is_public = true)
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
     * @param string $site_id The ID of the site.
     * @return array The site member configuration as an associative array.
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
     * @param string $site_id The ID of the site.
     * @return array The site meta information as an associative array.
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
     * Get site information by site ID.
     *
     * @param int $site_id The ID of the site.
     * @return array An array containing site information.
     */
    public function getSiteInfo($site_id)
    {
        $sql = <<<EOF
            SELECT * FROM site_info
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
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return bool True if the config file exists, false otherwise.
     */
    public function checkSiteConfig($site_code, $path)
    {
        $config_file = $path . '/' . $site_code . '.php';
        if (file_exists($config_file)) {
            return true;
        }
        return false;
    }

    /**
     * Get site config.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return array The site configuration as an associative array.
     */
    public function getSiteConfig($site_code, $path)
    {
        $config_file = $path . '/' . $site_code . '.php';
        if (file_exists($config_file)) {
            $configHandler = new PhpConfigHandler($config_file);
            return $configHandler->readConfig();
        }
        return [];
    }

    /**
     * Create a php file of the site config.
     *
     * @param string $site_id The ID of the site.
     * @param bool $is_public Flag indicating whether the site is public.
     * @param string $path The path to the directory to save the config file.
     * @return bool True if the config file was successfully created, false otherwise.
     * @throws Exception If an error occurs while creating the config file.
     */
    public function setSiteConfig($site_id, $is_public, $path)
    {
        try {
            $site = $this->getSite($site_id, $is_public);
            $site_member_config = $this->getSiteMemberConfig($site_id);
            $site_meta = $this->getSiteMeta($site_id);
            $site_info = $this->getSiteInfo($site_id);
            $data = array(
                /** Below are from the sites table **/
                'id' => $site['id'] ?? '', // Site ID
                'name' => $site['name'] ?? '', // Unique site name
                'category_id' => $site['category_id'] ?? '', // Site category ID
                'site_code' => $site['site_code'] ?? '', // Site code
                'domain' => $site['domain'] ?? '', // Domain
                'type' => $site['type'] ?? '', // Site type: 1 => website; 2 => shopsite
                'file_path' => $site['file_path'] ?? '', // Site file path
                'favicon' => $site['favicon'] ?? '', // Site favicon
                'verification_code' => $site['verification_code'] ?? '', // Verification code for site ownership (used for Google search engine registration)
                'is_public' => $site['is_public'] ?? '', // Site publishing status: 0 => unpublished; 1 => published
                'site_theme' => '', // 待補充資料
                /** Below are from the site_info table **/
                'site_logo' => isset($site_info['logo']) ? 'https://img.holkee.com/site/store/logo/' . $site['name'] . '/' . $site_info['logo'] : '',
                /** Below are from the site_meta table **/
                'title' => $site_meta['title'] ?? '', // Title
                'locale' => $site_meta['locale'] ?? '', // Site language
                'share_img' => $site_meta['share_img'] ?? '', // Share image
                /** Below are from the site_member_config table **/
                'login_email' => $site_member_config['login_email'] ?? '', // Allow members to register/login via email: 1 => Yes; 0 => No
                'login_mobile' => $site_member_config['login_mobile'] ?? '', // Allow members to register/login via mobile: 1 => Yes; 0 => No
                'login_fb' => $site_member_config['login_fb'] ?? '', // Allow members to register/login via Facebook: 1 => Yes; 0 => No
                'login_line' => $site_member_config['login_line'] ?? '', // Allow members to register/login via Line: 1 => Yes; 0 => No
                'login_google' => $site_member_config['login_google'] ?? '', // Allow members to register/login via Google: 1 => Yes; 0 => No
            );

            $config_file = $path . '/' . $data['site_code'] . '.php';
            $configHandler = new PhpConfigHandler($config_file);
            if ($configHandler->generateConfig($data)) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }
}
