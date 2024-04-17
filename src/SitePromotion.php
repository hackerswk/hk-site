<?php
/**
 * Class for reading data from the site_promotion_activities table using PDO.
 *
 * @author Stanley Sie <swookon@gmail.com>
 * @access public
 * @version Release: 1.0
 */

namespace Stanleysie\HkSite;

use \Exception;
use \PDO;
use \PDOException;

class SitePromotionActivitiesReader
{
    /** @var PDO Database connection */
    private $pdo;

    /**
     * Constructor.
     *
     * @param PDO $pdo PDO database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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
     * Get site promotion activities.
     *
     * @param int $siteId The ID of the site.
     * @return array Array of site promotion activities with associated conditions
     */
    public function getSitePromotionActivities($siteId)
    {
        $sql = <<<EOF
        SELECT * FROM site_promotion_activities WHERE site_id = :site_id
EOF;
        $params = array(':site_id' => $siteId);
        $activities = $this->executeQuery($sql, $params);

        // Fetch associated conditions for each activity and add them to the activities array
        foreach ($activities as &$activity) {
            $activityId = $activity['id'];
            $conditions = $this->getSitePromotionConditions($activityId);
            $activity['conditions'] = $conditions;
        }

        return $activities;
    }

    /**
     * Get site promotion conditions.
     *
     * @param int $activityId The ID of the promotion activity.
     * @return array Array of site promotion conditions
     */
    public function getSitePromotionConditions($activityId)
    {
        $sql = <<<EOF
        SELECT * FROM site_promotion_conditions WHERE activity_id = :activity_id
EOF;
        $params = array(':activity_id' => $activityId);
        return $this->executeQuery($sql, $params);
    }

    /**
     * Get site promotion coupons.
     *
     * @param int $siteId The ID of the site.
     * @return array Array of site promotion coupons
     */
    public function getSitePromotionCoupons($siteId)
    {
        $sql = <<<EOF
        SELECT * FROM site_promotion_coupons WHERE site_id = :site_id
EOF;
        $params = array(':site_id' => $siteId);
        return $this->executeQuery($sql, $params);
    }

    /**
     * Confirm if the site promotion config profile exists.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return bool True if the config file exists, false otherwise.
     */
    public function checkSitePromotionConfig($site_code, $path)
    {
        $config_file = $path . '/' . $site_code . '-promotion.php';
        if (file_exists($config_file)) {
            return true;
        }
        return false;
    }

    /**
     * Get site promotion config.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return array The site theme configuration as an associative array.
     */
    public function getSitePromotionConfig($site_code, $path)
    {
        $config_file = $path . '/' . $site_code . '-promotion.php';
        if (file_exists($config_file)) {
            $configHandler = new PhpConfigHandler($config_file);
            return $configHandler->readConfig();
        }
        return [];
    }

    /**
     * Set site promotion configuration based on site id.
     *
     * @param int $site_id The ID of the site.
     * @param bool $is_public Flag indicating if the site is public.
     * @param string $path The path to store the theme configuration file.
     * @return bool True if the configuration is successfully generated, false otherwise.
     * @throws Exception When an error occurs during configuration generation.
     */
    public function setSitePromotionConfig($site_id, $is_public, $path)
    {
        try {
            $activities = $this->getSitePromotionActivities($site_id);
            $coupons = $this->getSitePromotionCoupons($site_id);
            $site = new Site($this->pdo);
            $site_data = $site->getSite($site_id, $is_public);
            $data = array(
                /*
                |--------------------------------------------------------------------------
                | 網站促銷活動設定檔
                |--------------------------------------------------------------------------
                |
                | 此設定檔目前由 site_promotion_activities, site_promotion_conditions, site_promotion_coupons 整合而成
                |
                 */
                /**以下來自 site_promotion_activities & site_promotion_conditions **/
                'activities' => $activities ?? [], // 網站促銷活動
                /**以下來自 site_promotion_coupons**/
                'coupons' => $coupons ?? [],
                /** 持續新增..... **/
            );

            $config_file = $path . '/' . $site_data['site_code'] . '-promotion.php';
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
