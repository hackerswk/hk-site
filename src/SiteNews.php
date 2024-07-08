<?php
/**
 * SiteNews class for reading data from the site_news table using PDO.
 * Also includes methods for checking and getting site news config files.
 *
 * @autor Stanley Sie <swookon@gmail.com>
 * @access public
 * @version Release: 1.0
 */

namespace Stanleysie\HkSite;

use \Exception as Exception;
use \PDO as PDO;
use \PDOException as PDOException;

class SiteNews
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
     * Get site news from the database.
     *
     * @param int $site_id The site ID to use as a query condition
     * @return array Array of site news
     */
    public function getSiteNews($site_id)
    {
        $sql = <<<EOF
        SELECT * FROM site_news WHERE site_id = :site_id
EOF;
        return $this->executeQuery($sql, ['site_id' => $site_id]);
    }

    /**
     * Get a single site news by news ID.
     *
     * @param int $news_id The news ID to use as a query condition
     * @return array|null Single result of the query if found, null otherwise
     */
    public function getSiteNewsById($news_id)
    {
        $sql = <<<EOF
        SELECT * FROM site_news WHERE id = :news_id
EOF;
        return $this->executeSingleQuery($sql, ['news_id' => $news_id]);
    }

    /**
     * Confirm if the site news config profile exists.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return bool True if the config file exists, false otherwise.
     */
    public function checkSiteNewsConfig($site_code, $path)
    {
        $config_file = $path . '/site-news.php';
        if (file_exists($config_file)) {
            return true;
        }
        return false;
    }

    /**
     * Get site news config.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return array The site news configuration as an associative array.
     */
    public function getSiteNewsConfig($site_code, $path)
    {
        $config_file = $path . '/site-news.php';
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
     * Set site news configuration based on site ID.
     *
     * @param int $site_id The ID of the site.
     * @param string $path The path to store the news configuration file.
     * @return bool True if the configuration is successfully generated, false otherwise.
     * @throws Exception When an error occurs during configuration generation.
     */
    public function setSiteNewsConfig($site_id, $path)
    {
        try {
            $site_news = $this->getSiteNews($site_id);

            $data = array(
                /*
                |--------------------------------------------------------------------------
                | site_news Table
                |--------------------------------------------------------------------------
                |
                 */
                'site_news' => $site_news ?? [],
            );

            $config_file = $path . '/site-news.php';
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
