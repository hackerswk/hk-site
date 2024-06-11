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
     * @param string $path The path to the directory containing the config files.
     * @param int $is_domain Flag indicating if the site is represented by a domain.
     * @return bool True if the config file exists, false otherwise.
     */
    public function checkSiteLookup($path, $is_domain = 0)
    {
        $config_file = $this->getConfigFilePath($path, $is_domain);
        return file_exists($config_file);
    }

    /**
     * Get site lookup.
     *
     * @param string $path The path to the directory containing the config files.
     * @param int $is_domain Flag indicating if the site is represented by a domain.
     * @return array The site theme configuration as an associative array.
     */
    public function getSiteLookup($path, $is_domain = 0)
    {
        $config_file = $this->getConfigFilePath($path, $is_domain);
        if (file_exists($config_file)) {
            $configHandler = new PhpConfigHandler($config_file);
            return $configHandler->readConfig();
        }
        return [];
    }

    /**
     * Check if site_id exists in site-lookup.php.
     *
     * @param int $site_code The site code of the site to check.
     * @param string $path The path to the directory containing the config files.
     * @return bool True if the site_id exists, false otherwise.
     */
    public function isSiteCodeInSiteLookup($siteCode, $path)
    {
        $site_lookup = $this->getSiteLookup($path, 0); // 0 indicates site-lookup.php
        return isset($site_lookup[$siteCode]);
    }

    /**
     * Check if site_id exists in domain-lookup.php.
     *
     * @param int $domain The domain of the site to check.
     * @param string $path The path to the directory containing the config files.
     * @return bool True if the site_id exists, false otherwise.
     */
    public function isDomainInDomainLookup($domain, $path)
    {
        $domain_lookup = $this->getSiteLookup($path, 1); // 1 indicates domain-lookup.php
        return isset($domain_lookup[$domain]);
    }

    /**
     * Set site lookup configuration based on site id.
     *
     * @param int $site_id The ID of the site.
     * @param bool $is_public Flag indicating if the site is public.
     * @param string $path The path to store the theme configuration file.
     * @param int $is_domain Flag indicating if the site is represented by a domain.
     * @return bool True if the configuration is successfully generated, false otherwise.
     * @throws Exception When an error occurs during configuration generation.
     */
    public function setSiteLookup($site_id, $is_public, $path, $is_domain = 0)
    {
        try {
            // Ensure default lookup file exists
            $this->setDefaultLookup($path, $is_domain);

            $site = new Site($this->pdo);
            $site_data = $site->getSite($site_id, $is_public);
            $data = [
                'site_id' => $site_id,
                'site_code' => $site_data['site_code'] ?? '',
                'domain' => $site_data['domain'] ?? '',
                'file_path' => $site_data['file_path'] ?? '',
            ];

            $config_file = $this->getConfigFilePath($path, $is_domain);
            $configHandler = new PhpConfigHandler($config_file);

            // Read existing configuration
            $existingConfig = file_exists($config_file) ? $configHandler->readConfig() : [];

            // Determine the key based on whether it's a domain or site lookup
            $key = $is_domain ? $data['domain'] : $data['site_code'];

            // Add or update the site configuration using the appropriate key
            $existingConfig[$key] = $data;

            // Write the updated configuration
            if ($configHandler->generateConfig($existingConfig)) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }


    /**
     * Get the configuration file path.
     *
     * @param string $path The base path to the directory containing the config files.
     * @param int $is_domain Flag indicating if the site is represented by a domain.
     * @return string The full path to the configuration file.
     */
    private function getConfigFilePath($path, $is_domain)
    {
        if ($is_domain == 1) {
            return $path . '/domain-lookup.php';
        }
        return $path . '/site-lookup.php';
    }

    /**
     * Set default lookup configuration.
     *
     * @param string $path The base path to the directory containing the config files.
     * @param int $is_domain Flag indicating if the site is represented by a domain.
     * @return void
     */
    public function setDefaultLookup($path, $is_domain)
    {
        $config_file = $this->getConfigFilePath($path, $is_domain);
        if (!file_exists($config_file)) {
            $site = new Site($this->pdo);
            $public_sites = $site->getPublicSites();
            $data = [];

            foreach ($public_sites as $site_data) {
                if ($is_domain) {
                    // If is_domain is true, use the domain as the key
                    $data[$site_data['domain']] = [
                        'site_id' => $site_data['id'],
                        'site_code' => $site_data['site_code'],
                        'domain' => $site_data['domain'],
                        'file_path' => $site_data['file_path'],
                    ];
                } else {
                    // If is_domain is false, use the site_code as the key
                    $data[$site_data['site_code']] = [
                        'site_id' => $site_data['id'],
                        'site_code' => $site_data['site_code'],
                        'domain' => $site_data['domain'],
                        'file_path' => $site_data['file_path'],
                    ];
                }
            }

            $configHandler = new PhpConfigHandler($config_file);
            $configHandler->generateConfig($data);
        }
    }


    /**
     * Remove site from lookup configuration.
     *
     * @param string $key The key (domain or site_code) of the site to remove.
     * @param string $path The base path to the directory containing the config files.
     * @param int $is_domain Flag indicating if the site is represented by a domain.
     * @return bool True if the site is successfully removed, false otherwise.
     */
    public function removeSiteFromLookup($key, $path, $is_domain)
    {
        $config_file = $this->getConfigFilePath($path, $is_domain);
        if (file_exists($config_file)) {
            $configHandler = new PhpConfigHandler($config_file);
            $existingConfig = $configHandler->readConfig();

            if ($is_domain) {
                // If it's a domain lookup, check if the key (domain) exists
                if (isset($existingConfig[$key])) {
                    unset($existingConfig[$key]);

                    // Write the updated configuration
                    if ($configHandler->generateConfig($existingConfig)) {
                        return true;
                    }
                }
            } else {
                // If it's a site lookup, check if the key (site_code) exists
                if (isset($existingConfig[$key])) {
                    unset($existingConfig[$key]);

                    // Write the updated configuration
                    if ($configHandler->generateConfig($existingConfig)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

}
