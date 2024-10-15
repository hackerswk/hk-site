<?php
/**
 * SiteInfo class for reading data from the site_info table using PDO.
 * Also includes methods for checking and getting site info config files.
 *
 * @autor Stanley Sie <swookon@gmail.com>
 * @access public
 * @version Release: 1.0
 */

namespace Stanleysie\HkSite;

use \Exception as Exception;
use \PDO as PDO;
use \PDOException as PDOException;

class SiteInfo
{
    /** @var PDO Database connection */
    private $pdo;

    /**
     * Constructor.
     *
     * @param PDO $db PDO database connection
     */
    public function __construct(PDO $db)
    {
        $this->pdo = $db;
    }

    /**
     * Get site info from the database.
     *
     * @param int $site_id The site ID to use as a query condition
     * @return array Array of site info
     */
    public function getSiteInfoBySiteId($site_id)
    {
        $sql = <<<EOF
        SELECT * FROM site_info WHERE site_id = :site_id AND deleted_at IS NULL
EOF;
        return $this->executeSingleQuery($sql, ['site_id' => $site_id]);
    }

    /**
     * Confirm if the site info config profile exists.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return bool True if the config file exists, false otherwise.
     */
    public function checkSiteInfoConfig($site_code, $path)
    {
        $config_file = $path . '/site-info.php';
        if (file_exists($config_file)) {
            return true;
        }
        return false;
    }

    /**
     * Get site info config.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return array The site info configuration as an associative array.
     */
    public function getSiteInfoConfig($site_code, $path)
    {
        $config_file = $path . '/site-info.php';
        if (file_exists($config_file)) {
            $configHandler = new PhpConfigHandler($config_file);
            return $configHandler->readConfig();
        }
        return [];
    }

    /**
     * Execute SQL query and fetch a single result.
     *
     * @param string $sql SQL query
     * @param array $params Parameters to bind to the query
     * @return array|null Single result of the query if found, null otherwise
     */
    private function executeSingleQuery($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }

    /**
     * Set site info configuration based on site ID.
     *
     * @param int $site_id The ID of the site.
     * @param string $path The path to store the info configuration file.
     * @return bool True if the configuration is successfully generated, false otherwise.
     * @throws Exception When an error occurs during configuration generation.
     */
    public function setSiteInfoConfig($site_id, $path)
    {
        try {
            $site_info = $this->getSiteInfoBySiteId($site_id);
            $site = new Site($this->pdo); // Assuming Site class is available to get site name
            $site_data = $site->getSite($site_id);

            $data = array(
                'site_type' => $site_info['site_type'] ?? '',
                'currency' => $site_info['currency'] ?? '',
                'logo' => isset($site_info['logo']) ? 'https://img.holkee.com/site/store/logo/' . $site_data['name'] . '/' . $site_info['logo'] : '',
                'contact_phone' => $site_info['contact_phone'] ?? '',
                'contact_email' => $site_info['contact_email'] ?? '',
                'contact_location' => $site_info['contact_location'] ?? '',
                'google_map_status' => $site_info['google_map_status'] ?? '',
                'site_introdution_image' => isset($site_info['site_introdution_image']) ? 'https://img.holkee.com/site/store/' . $site_data['name'] . '/' . $site_info['site_introdution_image'] : '',
                'site_introduction_text' => $site_info['site_introduction_text'] ?? '',
                'contact_time' => $site_info['contact_time'] ?? '',
                'fb_link' => $site_info['fb_link'] ?? '',
                'line_link' => $site_info['line_link'] ?? '',
                'instagram_link' => $site_info['instagram_link'] ?? '',
                'youtube_link' => $site_info['youtube_link'] ?? '',
                'twitter_link' => $site_info['twitter_link'] ?? '',
                'tiktok_link' => $site_info['tiktok_link'] ?? '',
                'deleted_at' => $site_info['deleted_at'] ?? '',
            );

            $config_file = $path . '/site-info.php';
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
