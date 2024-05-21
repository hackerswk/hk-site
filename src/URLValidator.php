<?php
/**
 * URLValidator Class
 *
 * This class provides a method to validate URLs.
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

class URLValidator
{
    /**
     * Validate if the given URL is valid.
     *
     * This method first uses PHP's built-in filter to check if the URL is valid.
     * Then, it uses the ping command to check if the URL is responsive.
     *
     * @param string $url The URL to be validated.
     * @return bool Returns true if the URL is valid and responsive, otherwise false.
     */
    public static function isValid($url)
    {
        // Use PHP's built-in function to filter the URL
        $filteredUrl = filter_var($url, FILTER_VALIDATE_URL);

        // Check if the URL is valid
        if ($filteredUrl === false) {
            return false;
        }

        // Use the ping command to check if the URL is responsive
        $pingCommand = sprintf('ping -c 1 %s', escapeshellarg($filteredUrl));
        exec($pingCommand, $output, $returnCode);

        // Return true if the ping command return code is 0, indicating a response
        return $returnCode === 0;
    }
}
