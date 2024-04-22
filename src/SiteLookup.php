<?php
/**
 * Class for reading data from the site table using PDO.
 *
 * @author Stanley Sie <swookon@gmail.com>
 * @access public
 * @version Release: 1.0
 */

namespace Stanleysie\HkSite;

use \Exception;
use \PDO;
use \PDOException;

class SiteLookup
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
     * Confirm if the site lookup profile exists.
     *
     * @param string $site_code The site code or domain.
     * @param string $path The path to the directory containing the config files.
     * @param int $is_domain Flag indicating if the site is represented by a domain.
     * @return bool True if the config file exists, false otherwise.
     */
    public function checkSiteLookup($site_code, $path, $is_domain = 0)
    {
        $config_file = $path . '/';
        if ($is_domain == 1) {
            $config_file .= hash('sha256', $site_code) . '-lookup.php';
        } else {
            $config_file .= $site_code . '-lookup.php';
        }
        if (file_exists($config_file)) {
            return true;
        }
        return false;
    }

    /**
     * Get site lookup.
     *
     * @param string $site_code The site code or domain.
     * @param string $path The path to the directory containing the config files.
     * @param int $is_domain Flag indicating if the site is represented by a domain.
     * @return array The site theme configuration as an associative array.
     */
    public function getSiteLookup($site_code, $path, $is_domain = 0)
    {
        $config_file = $path . '/';
        if ($is_domain == 1) {
            $config_file .= hash('sha256', $site_code) . '-lookup.php';
        } else {
            $config_file .= $site_code . '-lookup.php';
        }
        if (file_exists($config_file)) {
            $configHandler = new PhpConfigHandler($config_file);
            return $configHandler->readConfig();
        }
        return [];
    }

    /**
     * Set site lookup configuration based on site id.
     *
     * @param int $site_id The ID of the site.
     * @param bool $is_public Flag indicating if the site is public.
     * @param string $path The path to store the theme configuration file.
     * @return bool True if the configuration is successfully generated, false otherwise.
     * @throws Exception When an error occurs during configuration generation.
     */
    public function setSiteLookup($site_id, $is_public, $path, $is_domain = 0)
    {
        try {
            $site = new Site($this->pdo);
            $site_data = $site->getSite($site_id, $is_public);
            $data = array(
                'site_code' => $site_data['site_code'] ?? '',
                'domain' => $site_data['domain'] ?? '',
                'file_path' => $site_data['file_path'] ?? '',
            );

            $config_file = $path . '/' . $site_data['site_code'] . '-lookup.php';
            if ($is_domain == 1) {
                $config_file = $path . '/' . hash('sha256', $site_data['domain']) . '-lookup.php';
            }

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
