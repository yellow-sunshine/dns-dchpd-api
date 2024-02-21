<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DhcpdController
{
    /**
     * Retrieves DHCP leases information from the DHCP server's leases file.
     * Parses the leases data and returns a JSON response with the relevant details.
     * If the DHCP leases file is not found or cannot be read, an error response is returned.
     *
     * @param Request $request  The HTTP request object.
     * @param Response $response  The HTTP response object.
     *
     * @return Response  The HTTP response containing JSON-encoded DHCP leases information
     *                  or an error message if the DHCP leases file is not found or cannot be read.
     */
    public function index(Request $request, Response $response): Response
    {
        $leases = $this->parseLeases();
        if ($leases !== false) {
            // Success
            $response->getBody()->write(json_encode(['leases' => $leases]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            // Error: File not found or file cant be read
            $response->getBody()->write(json_encode(['error' => 'DHCP leases file not found or can not be read']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    }

    /**
     * Parses DHCP leases information from the DHCP server's leases file.
     * Reads the content of the leases file, extracts relevant details for each lease,
     * and returns an array of lease information including IP address, lease duration,
     * MAC address, vendor class identifier, and timestamp details.
     *
     * @return array|false  An array containing lease information for each DHCP lease,
     *                     or false if the DHCP leases file is not found or cannot be read.
     */
    private function parseLeases()
    {
        $leasesFilePath = '/var/lib/dhcp/dhcpd.leases';
        // Check if the file exists and can be read
        if (!is_readable($leasesFilePath) || !($leasesContent = file_get_contents($leasesFilePath))) {
            return false; 
        }
        $leasesArray = explode("lease ", $leasesContent);
        array_shift($leasesArray);
        $result = [];
        foreach ($leasesArray as $lease) {
            // Extract IP address - https://regex101.com/r/mJ4Tdc/1
            preg_match('/(\d+\.\d+\.\d+\.\d+)/', $lease, $ipMatches);
            $ip = isset($ipMatches[1]) ? $ipMatches[1] : null;
            // starts, ends, tstp, cltt  - https://regex101.com/r/DCZJAs/1
            preg_match('/starts (\d+ [\d\/]+ [\d:]+)/', $lease, $startsMatches);
            $starts = isset($startsMatches[1]) ? $startsMatches[1] : null;
            preg_match('/ends (\d+ [\d\/]+ [\d:]+)/', $lease, $endsMatches);
            $ends = isset($endsMatches[1]) ? $endsMatches[1] : null;
            preg_match('/tstp (\d+ [\d\/]+ [\d:]+)/', $lease, $tstpMatches);
            $tstp = isset($tstpMatches[1]) ? $tstpMatches[1] : null;
            preg_match('/cltt (\d+ [\d\/]+ [\d:]+)/', $lease, $clttMatches);
            $cltt = isset($clttMatches[1]) ? $clttMatches[1] : null;
            // Extract MAC address and vendor-class-identifier - https://regex101.com/r/gNZEfZ/1
            preg_match('/hardware ethernet (.*?);.*?set vendor-class-identifier = "(.*?)";/s', $lease, $matches);
            $mac = isset($matches[1]) ? $matches[1] : null;
            $vendorClassIdentifier = isset($matches[2]) ? $matches[2] : null;
            // Create an array and keys for each lease
            $leaseInfo = [
                'ip' => $ip,
                'starts' => $starts,
                'ends' => $ends,
                'tstp' => $tstp,
                'cltt' => $cltt,
                'mac' => $mac,
                'vendor-class-identifier' => $vendorClassIdentifier,
            ];
            $result[] = $leaseInfo;
        }
        return $result;
    }


}