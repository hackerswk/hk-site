<?php
/**
 * SiteRule class for reading data from the site_rule table using PDO.
 * Also includes methods for checking and getting site rule config files.
 *
 * @autor Stanley Sie <swookon@gmail.com>
 * @access public
 * @version Release: 1.0
 */

namespace Stanleysie\HkSite;

use \Exception as Exception;
use \PDO as PDO;
use \PDOException as PDOException;

class SiteRule
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
     * Get site rules from the database.
     *
     * @param int $site_id The site ID to use as a query condition
     * @return array Array of site rules
     */
    public function getSiteRules($site_id)
    {
        $sql = <<<EOF
        SELECT * FROM site_rule
        WHERE site_id = :site_id
        ORDER BY id DESC
        LIMIT 1
EOF;
        return $this->executeSingleQuery($sql, ['site_id' => $site_id]);
    }

    /**
     * Get a single site rule by rule ID.
     *
     * @param int $rule_id The rule ID to use as a query condition
     * @return array|null Single result of the query if found, null otherwise
     */
    public function getSiteRuleById($rule_id)
    {
        $sql = <<<EOF
        SELECT * FROM site_rule WHERE id = :rule_id
EOF;
        return $this->executeSingleQuery($sql, ['rule_id' => $rule_id]);
    }

    /**
     * Confirm if the site rule config profile exists.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return bool True if the config file exists, false otherwise.
     */
    public function checkSiteRuleConfig($site_code, $path)
    {
        $config_file = $path . '/site-rule.php';
        if (file_exists($config_file)) {
            return true;
        }
        return false;
    }

    /**
     * Get site rule config.
     *
     * @param string $site_code The site code.
     * @param string $path The path to the directory containing the config files.
     * @return array The site rule configuration as an associative array.
     */
    public function getSiteRuleConfig($site_code, $path)
    {
        $config_file = $path . '/site-rule.php';
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
     * Set site rule configuration based on site ID.
     *
     * @param int $site_id The ID of the site.
     * @param string $path The path to store the rule configuration file.
     * @return bool True if the configuration is successfully generated, false otherwise.
     * @throws Exception When an error occurs during configuration generation.
     */
    public function setSiteRuleConfig($site_id, $path)
    {
        try {
            $site_rule = $this->getSiteRules($site_id);

            $data = array(
                /*
                |--------------------------------------------------------------------------
                | site_rule Table
                |--------------------------------------------------------------------------
                |
                 */
                'site_rule' => $site_rule ?? [],
            );

            $config_file = $path . '/site-rule.php';
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
