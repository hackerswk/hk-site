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
        SELECT * FROM site_block_setting WHERE site_id = :site_id AND deleted_at IS NULL
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
            SELECT * FROM site_style_setting WHERE site_id = :site_id AND deleted_at IS NULL
EOF;
        return $this->executeSingleQuery($sql, ['site_id' => $site_id]);
    }

    /**
     * Get topic config from the database.
     *
     * @param int $topic_id The topic ID to use as a query condition
     * @return array Array of topic config
     */
    public function getTopicConfig($topic_id)
    {
        $sql = <<<EOF
        SELECT * FROM topic_config WHERE id = :topic_id AND deleted_at IS NULL
EOF;
        return $this->executeSingleQuery($sql, ['topic_id' => $topic_id]);
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
        $config_file = $path . '/site-theme.php';
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
        $config_file = $path . '/site-theme.php';
        if (file_exists($config_file)) {
            $configHandler = new PhpConfigHandler($config_file);
            return $configHandler->readConfig();
        }
        return [];
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
            $topic_config = $this->getTopicConfig($topic_id);

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
                'home' => $site_style_setting['home_page'] ?? '',
                /*
                |--------------------------------------------------------------------------
                | topic_config Table
                |--------------------------------------------------------------------------
                |
                 */
                'topic_type' => $topic_config['topic_type'] ?? '',
                'site_type' => $topic_config['site_type'] ?? '',
            );

            $config_file = $path . '/site-theme.php';
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
