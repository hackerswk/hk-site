<?php
/**
 * Site function class for reading data from the sites table using PDO.
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\HkSite;

use \Exception as Exception;
use \PDO as PDO;
use \PDOException as PDOException;

class SiteFunction
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
     * Execute SQL query and fetch results.
     *
     * @param string $sql SQL query
     * @param array $params Parameters for the query
     * @return array Array of query results
     */
    private function executeQuery($sql, $params)
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
     * Confirm if the site function config profile exists.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return bool True if the config file exists, false otherwise.
     */
    public function checkSiteFunctionConfig($site_code, $path)
    {
        $config_file = $path . '/' . $site_code . '-function.php';
        if (file_exists($config_file)) {
            return true;
        }
        return false;
    }

    /**
     * Get site function config.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return array The site theme configuration as an associative array.
     */
    public function getSiteFunctionConfig($site_code, $path)
    {
        $config_file = $path . '/' . $site_code . '-function.php';
        if (file_exists($config_file)) {
            $configHandler = new PhpConfigHandler($config_file);
            return $configHandler->readConfig();
        }
        return [];
    }

    /**
     * Set site function configuration based on site id.
     *
     * @param int $site_id The ID of the site.
     * @param bool $is_public Flag indicating if the site is public.
     * @param string $path The path to store the theme configuration file.
     * @return bool True if the configuration is successfully generated, false otherwise.
     * @throws Exception When an error occurs during configuration generation.
     */
    public function setSiteFunctionConfig($site_id, $is_public, $path)
    {
        try {
            $site = new Site($this->pdo);
            $site_data = $site->getSite($site_id, $is_public);
            $data = array(
                /*
                |--------------------------------------------------------------------------
                | 網站功能設定檔
                |--------------------------------------------------------------------------
                | 此設定檔目前由 sites 整合而成
                |
                */
                /**以下來自 sites table**/
                'set_fbe' => $site_data['set_fbe'] ?? '',
                'set_cs_btn' => $site_data['set_cs_btn'] ?? '',
                'set_g_search' => $site_data['set_g_search'] ?? '',
                'set_tracking_code' => $site_data['set_tracking_code'] ?? '',
                /** 持續新增..... **/ 
            );
            
            $config_file = $path . '/' . $site_data['site_code'] . '-function.php';
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
