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
        return $this->executeQuery($sql, ['site_id' => $site_id]);
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
        return $this->executeQuery($sql, ['topic_id' => $topic_id]);
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
     *
     * @return array Array of query results
     */
    private function executeQuery($sql)
    {
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $site = new Site();
            $site_data = $site->getSite($site_id, $is_public);
            $data = array(
                'site_block_setting' => $this->getSiteBlockSettings($site_id),
                'site_style_setting' => $this->getSiteStyleSettings($site_id),
                'site_tool' => $this->getSiteTools($site_id),
                'topic_block' => $this->getTopicBlocks($topic_id),
                'topic_config' => $this->getTopicConfig($topic_id),
                'topic_page' => $this->getTopicPages($topic_id),
                'topic_style' => $this->getTopicStyles($topic_id),
            );
            
            $config_file = $path . '/' . $site_data['site']['site_code'] . '-theme.php';
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
