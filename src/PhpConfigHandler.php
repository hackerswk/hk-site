<?php
/**
 * PHP config handler class
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\HkSite;

class PhpConfigHandler
{
    /**
     * @var string The path to the configuration file.
     */
    protected $configFile;

    /**
     * PhpConfigHandler constructor.
     *
     * @param string $configFile The path to the configuration file.
     */
    public function __construct($configFile)
    {
        $this->configFile = $configFile;
    }

    /**
     * Generates a PHP configuration file.
     *
     * @param array $configArray The configuration array to be written to the file.
     * @return bool True if the configuration file was successfully generated, false otherwise.
     */
    public function generateConfig(array $configArray)
    {
        $content = "<?php\n\nreturn " . var_export($configArray, true) . ";\n";

        return file_put_contents($this->configFile, $content) !== false;
    }

    /**
     * Reads the PHP configuration file.
     *
     * @return array|null The configuration array if the file exists and is readable, null otherwise.
     */
    public function readConfig()
    {
        if (file_exists($this->configFile)) {
            $config = include $this->configFile;
            if (is_array($config)) {
                return $config;
            }
        }
        return null;
    }
}
