<?php
/**
 * Class for reading and setting data from the permissions table using PDO.
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\HkSite;

use \Exception as Exception;
use \PDO as PDO;
use \PDOException as PDOException;

class Permissions
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
     * Get all permissions.
     *
     * @return array Array of all permissions
     */
    public function getAllPermissions()
    {
        $sql = "SELECT id, unique_name FROM permissions";
        return $this->executeQuery($sql, []);
    }

    /**
     * Confirm if the permissions config profile exists.
     *
     * @param string $path The path to the directory containing the config files.
     * @return bool True if the config file exists, false otherwise.
     */
    public function checkPermissionsConfig($path)
    {
        $config_file = $path . '/permissions.php';
        if (file_exists($config_file)) {
            return true;
        }
        return false;
    }

    /**
     * Get permissions config.
     *
     * @param string $path The path to the directory containing the config files.
     * @return array The permissions configuration as an associative array.
     */
    public function getPermissionsConfig($path)
    {
        $config_file = $path . '/permissions.php';
        if (file_exists($config_file)) {
            $configHandler = new PhpConfigHandler($config_file);
            return $configHandler->readConfig();
        }
        return [];
    }

    /**
     * Create a php file of the permissions config.
     *
     * @param string $path The path to the directory to save the config file.
     * @return bool True if the config file was successfully created, false otherwise.
     * @throws Exception If an error occurs while creating the config file.
     */
    public function setPermissionsConfig($path)
    {
        try {
            // Fetch permissions
            $permissions = $this->getAllPermissions();
            $data = [];
            foreach ($permissions as $val) {
                $permission_array = [];
                $permission_array['id'] = $val['id'];
                $permission_array['unique_name'] = $val['unique_name'];
                array_push($data, $permission_array);
            }

            // Define the config file path
            $config_file = $path . '/permissions.php';

            // Create an instance of the config handler
            $configHandler = new PhpConfigHandler($config_file);

            // Generate the config file with the data array
            if ($configHandler->generateConfig($data)) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }

}
