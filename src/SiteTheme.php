<?php
/**
 * Site theme class for reading data from specified tables using PDO.
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\HkSite;

use \Exception as Exception;
use \PDO as PDO;
use \PDOException as PDOException;

class SiteTheme
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
     * Get site block settings from the database.
     *
     * @param int $site_id The site ID to use as a query condition
     * @return array Array of site block settings
     */
    public function getSiteBlockSettings($site_id)
    {
        $sql = <<<EOF
        SELECT * FROM site_block_setting WHERE site_id = :site_id
EOF;
        return $this->executeQuery($sql, ['site_id' => $site_id]);
    }

    /**
     * Get site style settings from the database.
     *
     * @param int $site_id The site ID to use as a query condition
     * @return array Array of site style settings
     */
    public function getSiteStyleSettings($site_id)
    {
        $sql = <<<EOF
            SELECT * FROM site_style_setting WHERE site_id = :site_id
EOF;
        return $this->executeSingleQuery($sql, ['site_id' => $site_id]);
    }

    /**
     * Get site tools from the database.
     *
     * @param int $site_id The site ID to use as a query condition
     * @return array Array of site tools
     */
    public function getSiteTools($site_id)
    {
        $sql = <<<EOF
            SELECT * FROM site_tool WHERE site_id = :site_id
EOF;
        return $this->executeQuery($sql, ['site_id' => $site_id]);
    }

    /**
     * Get topic blocks from the database.
     *
     * @param int $topic_id The topic ID to use as a query condition
     * @return array Array of topic blocks
     */
    public function getTopicBlocks($topic_id)
    {
        $sql = <<<EOF
            SELECT * FROM topic_block WHERE topic_id = :topic_id
EOF;
        return $this->executeQuery($sql, ['topic_id' => $topic_id]);
    }

    /**
     * Get topic configuration from the database.
     *
     * @param int $topic_id The topic ID to use as a query condition
     * @return array Array of topic configuration
     */
    public function getTopicConfig($topic_id)
    {
        $sql = <<<EOF
            SELECT * FROM topic_config WHERE id = :topic_id
EOF;
        return $this->executeSingleQuery($sql, ['topic_id' => $topic_id]);
    }

    /**
     * Get topic pages from the database.
     *
     * @param int $topic_id The topic ID to use as a query condition
     * @return array Array of topic pages
     */
    public function getTopicPages($topic_id)
    {
        $sql = <<<EOF
            SELECT * FROM topic_page WHERE topic_id = :topic_id
EOF;
        return $this->executeQuery($sql, ['topic_id' => $topic_id]);
    }

    /**
     * Get topic styles from the database.
     *
     * @param int $topic_id The topic ID to use as a query condition
     * @return array Array of topic styles
     */
    public function getTopicStyles($topic_id)
    {
        $sql = <<<EOF
            SELECT * FROM topic_style WHERE topic_id = :topic_id
EOF;
        return $this->executeQuery($sql, ['topic_id' => $topic_id]);
    }

    /**
     * Execute SQL query and fetch results.
     *
     * @param string $sql SQL query
     * @param array $params Parameters to bind to the query
     * @return array Array of query results
     */
    private function executeQuery($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
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
     * Confirm if the site theme config profile exists.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return bool True if the config file exists, false otherwise.
     */
    public function checkSiteThemeConfig($site_code, $path)
    {
        $config_file = $path . '/' . $site_code . '-theme.php';
        if (file_exists($config_file)) {
            return true;
        }
        return false;
    }

    /**
     * Get site theme config.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return array The site theme configuration as an associative array.
     */
    public function getSiteThemeConfig($site_code, $path)
    {
        $config_file = $path . '/' . $site_code . '-theme.php';
        if (file_exists($config_file)) {
            $configHandler = new PhpConfigHandler($config_file);
            return $configHandler->readConfig();
        }
        return [];
    }

    /**
     * Get site information by site ID.
     *
     * @param int $siteId The site ID to use as a query condition
     * @return array|null Site information if found, else null
     */
    public function getSiteInfoBySiteId($siteId)
    {
        $sql = <<<EOF
        SELECT * FROM site_info WHERE site_id = :site_id
EOF;
        return $this->executeSingleQuery($sql, ['site_id' => $siteId]);
    }

    /**
     * Get site news by site ID.
     *
     * @param int $siteId The site ID to use as a query condition
     * @return array|null Site news if found, else null
     */
    public function getSiteNewsBySiteId($siteId)
    {
        $sql = <<<EOF
        SELECT * FROM site_news WHERE site_id = :site_id
EOF;
        return $this->executeQuery($sql, ['site_id' => $siteId]);
    }

    /**
     * Get site services by site ID.
     *
     * @param int $siteId The site ID to use as a query condition
     * @return array|null Site services if found, else null
     */
    public function getSiteServicesBySiteId($siteId)
    {
        $sql = <<<EOF
        SELECT * FROM site_service WHERE site_id = :site_id
EOF;
        return $this->executeQuery($sql, ['site_id' => $siteId]);
    }

    /**
     * Set site theme configuration based on site and topic IDs.
     *
     * @param int $site_id The ID of the site.
     * @param int $topic_id The ID of the topic.
     * @param bool $is_public Flag indicating if the site is public.
     * @param string $path The path to store the theme configuration file.
     * @return bool True if the configuration is successfully generated, false otherwise.
     * @throws Exception When an error occurs during configuration generation.
     */
    public function setSiteThemeConfig($site_id, $topic_id, $is_public, $path)
    {
        try {
            $site_block_setting = $this->getSiteBlockSettings($site_id);
            $site_style_setting = $this->getSiteStyleSettings($site_id);
            $site_tool = $this->getSiteTools($site_id);
            $topic_block = $this->getTopicBlocks($topic_id);
            $topic_config = $this->getTopicConfig($topic_id);
            $topic_page = $this->getTopicPages($topic_id);
            $topic_style = $this->getTopicStyles($topic_id);
            $site = new Site($this->pdo);
            $site_data = $site->getSite($site_id, $is_public);
            $site_info = $this->getSiteInfoBySiteId($site_id);
            $site_news = $this->getSiteNewsBySiteId($site_id);
            $site_services = $this->getSiteServicesBySiteId($site_id);
            $data = array(
                /*
                |--------------------------------------------------------------------------
                | site_block_setting Table
                |--------------------------------------------------------------------------
                |
                 */
                'site_block_setting' => $site_block_setting ?? [],

                /*
                |--------------------------------------------------------------------------
                | site_style_setting Table
                |--------------------------------------------------------------------------
                |
                 */
                'site_style_setting_id' => $site_style_setting['id'] ?? '',
                'font' => $site_style_setting['font'] ?? '',
                'color' => $site_style_setting['color'] ?? '',
                'header' => $site_style_setting['header'] ?? '',
                'footer' => $site_style_setting['footer'] ?? '',

                /*
                |--------------------------------------------------------------------------
                | site_tool Table
                |--------------------------------------------------------------------------
                |
                 */
                'site_tool' => $site_tool ?? [],

                /*
                |--------------------------------------------------------------------------
                | topic_block Table
                |--------------------------------------------------------------------------
                |
                 */
                'topic_block' => $topic_block ?? [],

                /*
                |--------------------------------------------------------------------------
                | topic_config Table
                |--------------------------------------------------------------------------
                |
                 */
                'topic_name' => $topic_config['topic_name'] ?? '',
                'topic_icon' => $topic_config['topic_icon'] ?? '',
                'topic_description' => $topic_config['topic_description'] ?? '',
                'topic_type' => $topic_config['topic_type'] ?? '',
                'site_type' => $topic_config['site_type'] ?? '',
                'activate' => $topic_config['activate'] ?? '',
                'default_topic' => $topic_config['default_topic'] ?? '',

                /*
                |--------------------------------------------------------------------------
                | topic_page Table
                |--------------------------------------------------------------------------
                |
                 */
                'topic_page' => $topic_page ?? [],

                /*
                |--------------------------------------------------------------------------
                | topic_style Table
                |--------------------------------------------------------------------------
                |
                 */
                'topic_style' => $topic_style ?? [],

                /*
                |--------------------------------------------------------------------------
                | site_info Table
                |--------------------------------------------------------------------------
                |
                 */
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

                /*
                |--------------------------------------------------------------------------
                | site_service Table
                |--------------------------------------------------------------------------
                |
                 */
                'site_service' => $site_services ?? [],

                /*
                |--------------------------------------------------------------------------
                | site_news Table
                |--------------------------------------------------------------------------
                |
                 */
                'site_news' => $site_news ?? [],
            );

            $config_file = $path . '/' . $site_data['site_code'] . '-theme.php';
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
