<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ZoneDetails
{
    /**
     * Get JSON representation of zone details for a specified domain.
     *
     * This method retrieves zone details, including A and CNAME records,
     * for the specified domain and returns the information in JSON format.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param string $domain The domain for which zone details are requested.
     * @return Response The HTTP response containing JSON representation of zone details.
     */
    public function getZoneDetailsJson(Request $request, Response $response, $domain): Response
    {
        if ($this->isValidDomain($domain) == false) {
            $response->getBody()->write(json_encode(['error' => 'Invalid domain']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400); // Bad Request
        } else {
            $zoneDetails = $this->extractZoneData($domain);
            if ($zoneDetails == false){
                $response->getBody()->write(json_encode(['error' => 'Zone was not found on DNS server']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404); // Not Found
            } else {
                $response->getBody()->write(json_encode(['message' => $zoneDetails]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }
        }
    }

    /**
     * Extract zone data for the given domain.
     *
     * This method extracts various details related to the specified domain's zone file,
     * including the last modification date, A records, and CNAMEs.
     *
     * @param string $domain The domain for which to extract zone data.
     * @return array|false An array containing domain details if the domain is valid and its zone file exists,
     *                      otherwise false.
     */
    private function extractZoneData(string $domain)
    {
        $zoneFilesDir = '/etc/bind/zones';
        $zoneFilePath = $zoneFilesDir . '/db.' . $domain;
        if (!file_exists($zoneFilePath)) {
            return false;
        }
        try {
            $modificationDate = $this->getFileModificationDate($zoneFilePath);
            $zoneFileContents = file_get_contents($zoneFilePath);
            $aRecords = $this->extractARecords($zoneFileContents);
            $cnames = $this->extractCnameRecords($zoneFileContents);
            $details = ['domain' => $domain, 'modificationDate' => $modificationDate, 'aRecords' => $aRecords, 'cnames' => $cnames];
            return $details;
        } catch (\Exception $e) {
            // Log and handle the exception
            $logMessage = "Exception in " . __FILE__ . " on line " . $e->getLine() . " in function: " . get_class($e) . ": " . $e->getMessage();
            error_log($logMessage);
            return false;
        }
    }
    

    /**
     * Validates a domain name using a regular expression.
     *
     * This method checks if the provided domain follows the standard format for domain names.
     * It uses a regular expression to ensure that the domain contains valid characters and
     * adheres to the specified length constraints.
     *
     * @param string $domain The domain name to be validated.
     * @return bool True if the domain is valid, false otherwise.
     */
    private function isValidDomain(string $domain): bool {
        # regular expression which validates a domain name
        return (bool)preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)*\.[a-z]{2,24}$/', $domain);
    }

    /**
     * Retrieve the last modified date of the file associated with the given zone file contents.
     *
     * This method uses the `stat` command to get the last modification date and time
     * of the file containing the provided zone file contents.
     *
     * @param string $zoneFileContents The contents of the zone file.
     * @return string The date and time of the last modification or "unknown" if not available.
     */
    private function getFileModificationDate(string $zoneFilePath): string {
        try {
            $command = "stat -c %y \"$zoneFilePath\"";
            $output = null;
            $exitStatus = null;
            exec($command, $output, $exitStatus);
            if ($exitStatus === 0 && isset($output[0])) {
                return $output[0];
            } else {
                throw new \Exception("Failed to retrieve file modification date");
            }
        } catch (\Exception $e) {
            $logMessage = "Exception in " . __FILE__ . " on line " . $e->getLine() . " in function: " . get_class($e) . ": " . $e->getMessage();
            error_log($logMessage);
            return "unknown";
        }
    }

    /**
     * Extract A records from a zone file.
     *
     * This method parses the provided zone file contents and extracts A records.
     *
     * @param string $zoneFileContents The contents of the zone file.
     * @return array An array of A records with keys 'name' and 'ip'.
     */
    private function extractARecords(string $zoneFileContents): array
    {
        $lines = explode("\n", $zoneFileContents);
        $records = [];
        foreach ($lines as $line) {
             // Skip comments and empty lines
            if (empty($line) || $line[0] == ';' || $line[0] == '$') {
                continue;
            }
            if (preg_match('/^\s*([^\s]+)\s+IN\s+A\s+([^\s]+)\s*$/', $line, $matches)) {
                $name = $matches[1];
                $ip = $matches[2];
                $records[] = ['name' => $name, 'ip' => $ip];
            }
        }
        return $records;
    }

    /**
     * Extract CNAME records from a zone file.
     *
     * This method parses the provided zone file contents and extracts CNAME records.
     *
     * @param string $zoneFileContents The contents of the zone file.
     * @return array An array of CNAME records with keys 'name' and 'alias'.
     */
    private function extractCnameRecords(string $zoneFileContents): array
    {
        $lines = explode("\n", $zoneFileContents);
        $records = [];
        foreach ($lines as $line) {
            // Skip comments and empty lines
            if (empty($line) || $line[0] == ';' || $line[0] == '$') {
                continue;
            }
            if (preg_match('/^\s*([^\s]+)\s+IN\s+CNAME\s+([^\s]+)\s*$/', $line, $matches)) {
                $name = $matches[1];
                $alias = $matches[2];
                $records[] = ['name' => $name, 'alias' => $alias];
            }
        }
        return $records;
    }


}
