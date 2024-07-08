<?php
/**
 * SiteCarousel class for reading data from the site_carousel table using PDO.
 * Also includes methods for checking and getting site carousel config files.
 *
 * @autor Stanley Sie <swookon@gmail.com>
 * @access public
 * @version Release: 1.0
 */

namespace Stanleysie\HkSite;

use \Exception as Exception;
use \PDO as PDO;
use \PDOException as PDOException;

class SiteCarousel
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
     * Get site carousels from the database.
     *
     * @param int $site_id The site ID to use as a query condition
     * @return array Array of site carousels
     */
    public function getSiteCarousels($site_id)
    {
        $sql = <<<EOF
        SELECT * FROM site_carousel WHERE site_id = :site_id
EOF;
        return $this->executeQuery($sql, ['site_id' => $site_id]);
    }

    /**
     * Get a single site carousel by carousel ID.
     *
     * @param int $carousel_id The carousel ID to use as a query condition
     * @return array|null Single result of the query if found, null otherwise
     */
    public function getSiteCarouselById($carousel_id)
    {
        $sql = <<<EOF
        SELECT * FROM site_carousel WHERE id = :carousel_id
EOF;
        return $this->executeSingleQuery($sql, ['carousel_id' => $carousel_id]);
    }

    /**
     * Confirm if the site carousel config profile exists.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return bool True if the config file exists, false otherwise.
     */
    public function checkSiteCarouselConfig($site_code, $path)
    {
        $config_file = $path . '/site-carousel.php';
        if (file_exists($config_file)) {
            return true;
        }
        return false;
    }

    /**
     * Get site carousel config.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return array The site carousel configuration as an associative array.
     */
    public function getSiteCarouselConfig($site_code, $path)
    {
        $config_file = $path . '/site-carousel.php';
        if (file_exists($config_file)) {
            $configHandler = new PhpConfigHandler($config_file);
            return $configHandler->readConfig();
        }
        return [];
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
     * Set site carousel configuration based on site ID.
     *
     * @param int $site_id The ID of the site.
     * @param string $path The path to store the carousel configuration file.
     * @return bool True if the configuration is successfully generated, false otherwise.
     * @throws Exception When an error occurs during configuration generation.
     */
    public function setSiteCarouselConfig($site_id, $path)
    {
        try {
            $site_carousel = $this->getSiteCarousels($site_id);

            $data = array(
                /*
                |--------------------------------------------------------------------------
                | site_carousel Table
                |--------------------------------------------------------------------------
                |
                 */
                'site_carousel' => $site_carousel ?? [],
            );

            $config_file = $path . '/site-carousel.php';
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
