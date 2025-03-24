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
    public function getSitePageSettings($site_id)
    {
        $sql = <<<EOF
        SELECT site_page_setting.id, site_page_setting.page_id, site_page_setting.page_path, site_page_setting.custom_name, site_page_setting.index, site_page_setting.sort,
        site_page_setting.status, page_config.name as base_name, page_config.page_path as base_path
        FROM site_page_setting
        INNER JOIN page_config ON site_page_setting.page_id = page_config.id
        WHERE site_page_setting.site_id = :site_id AND site_page_setting.deleted_at IS NULL
EOF;
        return $this->executeQuery($sql, ['site_id' => $site_id]);
    }

    /**
     * get site page menu from page setting
     *
     * @param array $site_page_setting The site's page setting
     * @return array Array of site page menu
     */
    public function getSiteMenu($site_page_setting, $site_type, $mod_ecommerce)
    {
        if (!isset($site_page_setting)) {
            return [];
        }
        $shopping_cart = (!$mod_ecommerce) ? 0 : 1;
        $menu = [];
        foreach ($site_page_setting as $row) {
            if ($row['status'] == 1) {
                $menu[$row['sort']] = [
                    'id' => $row['id'],
                    'name' => $row['custom_name'] != '_' ? $row['custom_name'] : $row['base_name'],
                    'path' => $row['page_path'] != null ? $row['page_path'] : $row['base_path'],
                    'status' => $row['status'],
                ];
            }

        }
        $menu[] = [
            'name' => 'login',
            'path' => 'login',
        ];
        if ($shopping_cart || $site_type == 2) {
            $menu[] = [
                'name' => 'shopping_cart',
                'path' => 'shopping_cart',
            ];
        }
        if (is_array($menu)) {
            ksort($menu); // 依排序重新排序陣列
        }
        return $menu;
    }
    /**
     * get site index page  from page setting
     *
     * @param array $site_page_setting The site's page setting
     * @return string Array of site page menu
     */
    public function getSiteHome($site_page_setting)
    {
        if (!isset($site_page_setting)) {
            return '';
        }
        $index = 'home';
        foreach ($site_page_setting as $row) {
            if ($row['index'] == 1) {
                $index = $row['base_path'];
            }
        }
        return $index;
    }
    /**
     * Get site block settings from the database.
     *
     * @param int $site_id The site ID to use as a query condition
     * @return array Array of site block settings
     */
    public function getSiteBlockSettings($site_id, $site_page_setting)
    {
        $site_block_setting = [];
        foreach ($site_page_setting as $row) {
            $sql = <<<EOF
            SELECT *
            FROM site_page_blocks
            WHERE site_page_id = :page_block_id AND parent_id IS NULL AND deleted_at IS NULL
EOF;
            $temp = $this->executeQuery($sql, ['page_block_id' => $row['id']]);
            if (empty($temp)) {
                continue;
            }
            foreach ($temp as $col) {
                if (!isset($site_block_setting[$row['base_path']])) {
                    $site_block_setting[$row['base_path']] = [];
                }
                if ($col['status'] == 1) {
                    $site_block_setting[$row['base_path']][$col['sort']] = [
                        'block_id' => $col['id'],
                        'name' => $col['name'],
                    ];
                    if ($col['content'] != null) {
                        $site_block_setting[$row['base_path']][$col['sort']]['content'] = $col['content'];
                    }
                    if ($col['name'] == 'feature_product') {
                        $sql_feature = <<<EOF
                        SELECT * FROM site_feature_setting where site_id = :site_id ;
EOF;
                        $temp_feature = $this->executeQuery($sql_feature, ['site_id' => $site_id]);
                        $site_block_setting[$row['base_path']][$col['sort']]['feature_type'] = $temp_feature['feature_type'] ?? 'latest';
                        $site_block_setting[$row['base_path']][$col['sort']]['main_class'] = $temp_feature['main_class'] ?? 0;
                        $site_block_setting[$row['base_path']][$col['sort']]['sub_class'] = $temp_feature['sub_class'] ?? 0;

                    }
                }

            }

            if (empty($site_block_setting[$row['base_path']])) {
                continue;
            }

            if (is_array($site_block_setting[$row['base_path']])) {
                ksort($site_block_setting[$row['base_path']]);
            }

        }

        return $site_block_setting;
    }

    /**
     * Get site tool block settings from the database.
     *
     * @param int $site_id The site ID to use as a query condition
     * @return array Array of site block settings
     */
    public function getSiteTools($site_id, $site_page_setting)
    {
        $site_block_setting = [];
        foreach ($site_page_setting as $row) {
            $sql = <<<EOF
            SELECT *
            FROM site_page_blocks
            WHERE site_page_id = :page_block_id AND parent_id IS NOT NULL AND deleted_at IS NULL
EOF;
            $temp = $this->executeQuery($sql, ["page_block_id" => $row['id']]);
            if (empty($temp)) {
                continue;
            }
            foreach ($temp as $col) {
                if (!isset($site_block_setting[$row['base_path']])) {
                    $site_block_setting[$row['base_path']] = [];
                }
                if ($col['status'] == 1) {
                    $site_block_setting[$row['base_path']][$col['sort']] = [
                        'name' => $col['name'],
                        'content' => $col['content'],
                        'base_menu' => $row['base_path'],
                    ];
                }

            }
            if (!empty($site_block_setting) && is_array($site_block_setting[$row['base_path']])) {
                ksort($site_block_setting[$row['base_path']]);
            }

        }

        return $site_block_setting;
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
            SELECT site_style_settings.id, site_style_settings.site_id, site_style_settings.theme_id, site_style_settings.custom_theme_id,
            t1.suffix as font, t2.suffix as color, t3.suffix as header, t4.suffix as footer
            FROM site_style_settings
            INNER JOIN theme_style as t1 ON site_style_settings.font_style = t1.id
            INNER JOIN theme_style as t2 ON site_style_settings.color_style = t2.id
            INNER JOIN theme_style as t3 ON site_style_settings.header_style = t3.id
            INNER JOIN theme_style as t4 ON site_style_settings.footer_style = t4.id
            WHERE site_style_settings.site_id = :site_id AND site_style_settings.deleted_at IS NULL
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
        SELECT * FROM theme_config WHERE id = :topic_id AND deleted_at IS NULL
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
            $site_page_setting = $this->getSitePageSettings($site_id);
            $topic_config = $this->getTopicConfig($topic_id);
            $mod_ecommerce = $this->getSiteProService($site_id, 9);
            //整理選單
            $menu = $this->getSiteMenu($site_page_setting, $topic_config['site_type'], $mod_ecommerce);
            $index = $this->getSiteHome($site_page_setting);
            $site_block_setting = $this->getSiteBlockSettings($site_id, $site_page_setting);
            // $site_tool_block = $this->getSiteTools($site_id ,$site_page_setting);
            $site_style_setting = $this->getSiteStyleSettings($site_id);
            $data = array(
                /*
                |--------------------------------------------------------------------------
                | site_page_setting Table
                |--------------------------------------------------------------------------
                |
                 */
                // 'site_page_setting' => $site_page_setting ?? [],
                'menu' => $menu ?? [],
                'home' => $index ?? '',
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
                'theme_id' => $site_style_setting['theme_id'] ?? '',
                'custom_theme_id' => $site_style_setting['custom_theme_id'] ?? '',
                'font' => $site_style_setting['font'] ?? '',
                'color' => $site_style_setting['color'] ?? '',
                'header' => $site_style_setting['header'] ?? '',
                'footer' => $site_style_setting['footer'] ?? '',
                /*
                |--------------------------------------------------------------------------
                | topic_config Table
                |--------------------------------------------------------------------------
                |
                 */
                'topic_type' => $topic_config['type'] ?? '',
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
    /**
     * Retrieves the site pro service record based on site ID and service ID.
     *
     * @param int $site_id The ID of the site.
     * @param int $service_id The ID of the service.
     * @return array|false The site pro service record as an associative array, or false if no record is found.
     */
    private function getSiteProService($site_id, $service_id)
    {
        $sql = <<<EOF
            SELECT * FROM site_pro_services WHERE site_id = $site_id AND service_id = $service_id
            AND is_terminated = 0 AND deleted_at IS NULL
EOF;
        // Execute the query and fetch the result
        $query = $this->pdo->query($sql); // Assuming $this->db is a PDO instance
        $record = $query->fetch(PDO::FETCH_ASSOC);
        return $record;
    }
}
