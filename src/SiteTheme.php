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
            $site_block_setting = $this->getSiteBlockSettings($site_id);
            $site_style_setting = $this->getSiteStyleSettings($site_id);
            $site_tool = $this->getSiteTools($site_id);
            $topic_block = $this->getTopicBlocks($topic_id);
            $topic_config = $this->getTopicConfig($topic_id);
            $topic_page = $this->getTopicPages($topic_id);
            $topic_style = $this->getTopicStyles($topic_id);
            $site = new Site();
            $site_data = $site->getSite($site_id, $is_public);
            $data = array(
                /*
                |--------------------------------------------------------------------------
                | site_block_setting Table
                |--------------------------------------------------------------------------
                |
                */
                'site_id' => $site_block_setting['site_id'], // Site ID
                'page' => $site_block_setting['page'], // Page
                'blocks' => $site_block_setting['blocks'], // Blocks
                'visible' => $site_block_setting['visible'], // Visibility
                'delete_at' => $site_block_setting['delete_at'], // Deletion time

                /*
                |--------------------------------------------------------------------------
                | site_style_setting Table
                |--------------------------------------------------------------------------
                |
                */
                'id' => $site_style_setting['id'], // ID
                'font' => $site_style_setting['font'], // Font
                'color' => $site_style_setting['color'], // Color
                'header' => $site_style_setting['header'], // Header
                'footer' => $site_style_setting['footer'], // Footer

                /*
                |--------------------------------------------------------------------------
                | site_tool Table
                |--------------------------------------------------------------------------
                |
                */
                'id' => $site_tool['id'], // ID
                'type' => $site_tool['type'], // Tool type
                'url' => $site_tool['url'], // URL

                /*
                |--------------------------------------------------------------------------
                | topic_block Table
                |--------------------------------------------------------------------------
                |
                */
                'page' => $topic_block['page'], // Page
                'order' => $topic_block['order'], // Order
                'block_name' => $topic_block['block_name'], // Block Name
                'visible' => $topic_block['visible'], // Visibility

                /*
                |--------------------------------------------------------------------------
                | topic_config Table
                |--------------------------------------------------------------------------
                |
                */
                'topic_name' => $topic_config['topic_name'], // Topic Name
                'topic_icon' => $topic_config['topic_icon'], // Topic Icon
                'topic_description' => $topic_config['topic_description'], // Topic Description
                'topic_type' => $topic_config['topic_type'], // Topic Type (0: free, 1: one-page, 2: multi-page)
                'site_type' => $topic_config['site_type'], // Site Type (1: brand, 2: shopping, 3: booking, 4: reservation, 5: appointment)
                'activate' => $topic_config['activate'], // Activation status (0: inactive, 1: active)
                'default_topic' => $topic_config['default_topic'], // Default topic status (0: not default, 1: default)

                /*
                |--------------------------------------------------------------------------
                | topic_page Table
                |--------------------------------------------------------------------------
                |
                */
                'order' => $topic_page['order'], // Order
                'name' => $topic_page['name'], // Page Name
                'visible' => $topic_page['visible'], // Visibility

                /*
                |--------------------------------------------------------------------------
                | topic_style Table
                |--------------------------------------------------------------------------
                |
                */
                'style_type' => $topic_style['style_type'], // Style Type (e.g., color, font)
                'style_order' => $topic_style['style_order'], // Style Order
                'content' => $topic_style['content'], // Content Description
                'sample_image' => $topic_style['sample_image'], // Sample Image URL
                'activate' => $topic_style['activate'], // Activation status (0: inactive, 1: active)
                'deleted_at' => $topic_style['deleted_at'], // Deletion time 
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
