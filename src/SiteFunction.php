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
     * Get site shipping services.
     *
     * @param int $siteId The ID of the site.
     * @return array Array of site shipping services
     */
    public function getSiteShippingServices($siteId)
    {
        $sql = <<<EOF
        SELECT * FROM site_shipping_services WHERE site_id = :site_id
EOF;
        $params = array(':site_id' => $siteId);
        return $this->executeQuery($sql, $params);
    }

    /**
     * Get site payment services.
     *
     * @param int $siteId The ID of the site.
     * @return array Array of site payment services
     */
    public function getSitePaymentServices($siteId)
    {
        $sql = <<<EOF
        SELECT * FROM site_payment_services WHERE site_id = :site_id
EOF;
        $params = array(':site_id' => $siteId);
        return $this->executeQuery($sql, $params);
    }

    /**
     * Get site shipping payment relationships.
     *
     * @param int $siteId The ID of the site.
     * @return array Array of site shipping payment relationships
     */
    public function getSiteShippingPaymentRelationships($siteId)
    {
        $sql = <<<EOF
        SELECT * FROM site_shipping_payment_relationships WHERE site_id = :site_id
EOF;
        $params = array(':site_id' => $siteId);
        return $this->executeQuery($sql, $params);
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
            $shipping_services = $this->getSiteShippingServices($site_id);
            $payment_services = $this->getSitePaymentServices($site_id);
            $shipping_payment_relationships = $this->getSiteShippingPaymentRelationships($site_id);
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
                /**以下來自 site_shipping_services table**/
                'shipping_services' => $shipping_services ?? [], // 網站物流服務
                /**以下來自 site_payment_services table**/
                'payment_services' => $payment_services ?? [], // 網站金流服務
                /**以下來自 site_shipping_payment_relationships table**/
                'shipping_payment_relationships' => $shipping_payment_relationships ?? [], // 網站物流與金流服務關聯
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
