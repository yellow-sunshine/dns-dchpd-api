<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DhcpdConfig
{


    /**
     * Retrieves DHCP Reservations from the DHCP server's leases file.
     * Parses the leases data and returns a JSON response with the relevant details.
     * If the DHCP leases file is not found or cannot be read, an error response is returned.
     *
     * @param Request $request  The HTTP request object.
     * @param Response $response  The HTTP response object.
     *
     * @return Response  The HTTP response containing JSON-encoded DHCP leases information
     *                  or an error message if the DHCP leases file is not found or cannot be read.
     */
    public function index(Request $request, Response $response): Response {
        $leases = $this->parseLeases();
        if ($leases !== false) {
            // Success
            $key = 'leases';
            $content = $leases;
            $statusCode = 200;
        } else {
            // Error: File not found or file cant be read
            $key = 'error';
            $content = 'DHCP leases file not found or can not be read';
            $statusCode = 404;
        }
        $response->getBody()->write(json_encode([$key => $content]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
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
    public function parseLeases() : array|false {
        // Get the raw content of the leases file
        $rawFileContent = $this->getRawFileContent(CONFIG_PATHS['dhcpLeases']);
        // Get an array of all the unparsed Lease Blocks
        $unparsedLeaseBlocks = $this->extractRawLeases($rawFileContent);
        // array map will run extractLeaseDetails on each leaseblock packing return arrays into $leaseBlocks
        $leaseBlocks = array_map([$this, 'extractLeaseDetails'], $unparsedLeaseBlocks);
        // Return leases or false if no leases found
        return $leaseBlocks ?? false;
    }


    /**
     * Gets text content of a file passed to it
     * Logically this should be in a separate class as it is a general file reading function
     * However, a function that reads any file passed to it is better encapsulated in the class that uses it
     *
     * @param string $filePath  Fully qualified path of the file we are reading.
     * @return string|false  The content of the file if it is readable
     *                     or false if the file is not readable.
     */
    private function getRawFileContent(string $filePath) : string|false {
        // Check if the file is readable and return it, or false if its not
        return is_readable($filePath) ? file_get_contents($filePath) : false;
    }


    /**
     * Extracts raw lease blocks from the given raw file content.
     *
     * @param string $rawFileContent  The content of the file containing raw DHCP lease information.
     * @return array  An array of raw lease blocks, or an empty array if none were found.
     */
    public function extractRawLeases(string $rawFileContent) : array {
        // Each lease has the lease keyword at the start of the block so we xplode it into an array
        $rawLeases = explode("lease ", $rawFileContent);
        // Remove the first empty element of the array
        array_shift($rawLeases);
        // Return the array of lease blocks and an empty array if none were found
        return $rawLeases ?? [];
    }

    /**
     * Extracts usefull lease parameters from the given raw lease block.
     *
     * @param string $unparsedLeaseBlock  The raw lease block containing DHCP lease information.
     * @return array  An array of lease parameters, including IP, starts, ends, tstp, cltt, MAC, and vendor class identifier.
     */
    public function extractLeaseParameter(string $unparsedLeaseBlock) : array {
        $parsedLease = [
            'ip' => $this->extractIp('ip', $unparsedLeaseBlock),
            'starts' => $this->extractLeaseParameter('starts', $unparsedLeaseBlock),
            'ends' => $this->extractLeaseParameter('ends', $unparsedLeaseBlock),
            'tstp' => $this->extractLeaseParameter('tstp', $unparsedLeaseBlock),
            'cltt' => $this->extractLeaseParameter('cltt', $unparsedLeaseBlock),
            'mac' => $this->extractLeaseParameter('hardware ethernet', $unparsedLeaseBlock), 
            'vendor-class-identifier' => $this->extractLeaseParameter('vendor-class-identifier', $unparsedLeaseBlock)
        ];
        return $parsedLease;
    }


    /**
     * Extracts a specific lease parameter from the given raw lease block based on the provided parameter type.
     * 
     * @param string $leaseParameter  The type of lease parameter to extract (e.g., 'ip', 'starts', 'ends', 'tstp', 'cltt', 'mac', 'vendor-class-identifier').
     * @param string $unparsedLeaseBlock  The raw lease block containing DHCP lease information.
     * @return string|null  The extracted value of the specified lease parameter or null if the parameter is not found.
     */
    public function extractLeaseParameter(string $leaseParameter, string $unparsedLeaseBlock) : ?string {
        // Switch over the lease parameter to determine the regex pattern to use
        switch($leaseParameter) {
            case "stars":
            case "ends":
            case "tstp":
            case "cltt":
                // Extract starts - https://regex101.com/r/DCZJAs/1
                $pattern = "/$leaseParameter (\d+ [\d\/]+ [\d:]+)/";
                preg_match($pattern, $unparsedLeaseBlock, $matches);
                return $matches[1] ?? null;
            case "ip":
                // Extract IP address - https://regex101.com/r/mJ4Tdc/1
                preg_match('/(\d+\.\d+\.\d+\.\d+)/', $unparsedLeaseBlock, $ipMatches);
                return $ipMatches[1] ?? null;
            case 'mac':
            case 'hardware ethernet':
                $vendorData = $this->extractVendor($unparsedLeaseBlock);
                return $vendorData[0] ?? null;
            case 'set vendor class identifier':
            case 'vendor class identifier':
            case 'vendor-class-identifier':
                $vendorData = $this->extractVendor($unparsedLeaseBlock);
                return $vendorData[1] ?? null;
            default:
                return null;
        }
    }


    /**
     * Extracts vendor related daa from from the given raw DHCP lease block.
     * 
     * @param string $unparsedLeaseBlock  The raw lease block containing DHCP lease information.
     * @return array  An array containing MAC address (index 0) and vendor class identifier (index 1).
     *                If either value is not found in the lease block, it will be null in the array.
     */
    public function extractVendor(string $unparsedLeaseBlock) : array {
        // Extract MAC address and vendor-class-identifier - https://regex101.com/r/gNZEfZ/1
        preg_match('/hardware ethernet (.*?);.*?set vendor-class-identifier = "(.*?)";/s', $unparsedLeaseBlock, $matches);
        // Return an array with the mac address and vendor class identifier adn null for each if not found
        return [$matches[1] ?? null, $matches[2] ?? null];
    }


}
