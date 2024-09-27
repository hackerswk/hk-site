<?php
/**
 * Site tool class for reading data from the site_tool table using PDO.
 * Also includes methods for checking and getting site tool config files.
 *
 * @autor Stanley Sie <swookon@gmail.com>
 * @access public
 * @version Release: 1.0
 */

namespace Stanleysie\HkSite;

use \Exception as Exception;
use \PDO as PDO;
use \PDOException as PDOException;

class SiteTool
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
     * Get site tools from the database.
     *
     * @param int $site_id The site ID to use as a query condition
     * @return array Array of site tools
     */
    public function getSiteTools($site_id)
    {
        $sql = <<<EOF
        SELECT * FROM site_tool WHERE site_id = :site_id AND deleted_at IS NULL
EOF;
        return $this->executeQuery($sql, ['site_id' => $site_id]);
    }

    /**
     * Get a single site tool by tool ID.
     *
     * @param int $tool_id The tool ID to use as a query condition
     * @return array|null Single result of the query if found, null otherwise
     */
    public function getSiteToolById($tool_id)
    {
        $sql = <<<EOF
        SELECT * FROM site_tool WHERE id = :tool_id
EOF;
        return $this->executeSingleQuery($sql, ['tool_id' => $tool_id]);
    }

    /**
     * Confirm if the site tool config profile exists.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return bool True if the config file exists, false otherwise.
     */
    public function checkSiteToolConfig($site_code, $path)
    {
        $config_file = $path . '/site-tool.php';
        if (file_exists($config_file)) {
            return true;
        }
        return false;
    }

    /**
     * Get site tool config.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return array The site tool configuration as an associative array.
     */
    public function getSiteToolConfig($site_code, $path)
    {
        $config_file = $path . '/site-tool.php';
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
     * Set site tool configuration based on site ID.
     *
     * @param int $site_id The ID of the site.
     * @param string $path The path to store the tool configuration file.
     * @return bool True if the configuration is successfully generated, false otherwise.
     * @throws Exception When an error occurs during configuration generation.
     */
    public function setSiteToolConfig($site_id, $path)
    {
        try {
            $site_tool = $this->getSiteTools($site_id);

            $data = array(
                /*
                |--------------------------------------------------------------------------
                | site_tool Table
                |--------------------------------------------------------------------------
                |
                 */
                'site_tool' => $site_tool ?? [],
            );

            $config_file = $path . '/site-tool.php';
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
