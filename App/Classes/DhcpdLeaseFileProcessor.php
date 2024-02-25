<?php
namespace App\Classes;

class DhcpdLeaseFileProcessor
{

    /**
     * Retrieve DHCP leases from the specified file and parse the information.
     *
     * @param string $dhcpLeasesFileLocation  The file path to the DHCP leases file.
     *
     * @return array|null  An array containing parsed DHCP leases information, or null
     *                    if the file cannot be read or no leases are found.
     */
    public function getLeases($dhcpLeasesFileLocation): ?array {
        // Get the raw content of the leases file
        $rawFileContent = $this->getRawFileContent($dhcpLeasesFileLocation);
        // If the file is not readable, return null
        if ($rawFileContent === false) {
            error_log("Error reading file: $dhcpLeasesFileLocation");
            return null;
        }
        // Get an array of all the unparsed Lease Blocks
        $unparsedLeaseBlocks = $this->extractLeaseBlocks($rawFileContent);
        // Map over the array of unparsed lease blocks and extract the lease details
        $leaseBlocks = array_map([$this, 'extractLeaseDetails'], $unparsedLeaseBlocks);
        // Return leases or null if no leases found
        return $leaseBlocks ?? null;
    }


    # Self explainitory, returns the content of a file or false if it cant be read
    private function getRawFileContent(string $filePath) : string|false {
        return is_readable($filePath) ? file_get_contents($filePath) : false;
    }


    /**
     * Extracts lease blocks from a passed dhcpd.leases' content.
     *
     * Each lease block in the DHCP leases file starts with the "lease" keyword.
     * This function splits the raw content into an array of lease blocks, each being a string
     *
     * @param string $rawFileContent  The raw content of the DHCP leases file.
     *
     * @return array  An array of lease blocks, or an empty array if no blocks were found.
     */
    public function extractLeaseBlocks(string $rawFileContent) : array {
        $rawLeases = explode("lease ", $rawFileContent);
        // Remove the first empty element of the array
        array_shift($rawLeases);
        // Return the array of lease blocks and an empty array if none were found
        return $rawLeases ?? [];
    }


    # Proxy function using extractLeaseParameter to pack an array of extracted lease parameters
    public function extractLeaseDetails(string $unparsedLeaseBlock) : array {
        $parsedLease = [
            'ip' => $this->extractLeaseParameter('ip', $unparsedLeaseBlock),
            'starts' => $this->extractLeaseParameter('starts', $unparsedLeaseBlock),
            'ends' => $this->extractLeaseParameter('ends', $unparsedLeaseBlock),
            'tstp' => $this->extractLeaseParameter('tstp', $unparsedLeaseBlock),
            'cltt' => $this->extractLeaseParameter('cltt', $unparsedLeaseBlock),
            'mac' => $this->extractLeaseParameter('hardware ethernet', $unparsedLeaseBlock), 
            'vendor' => $this->extractLeaseParameter('vendor-class-identifier', $unparsedLeaseBlock)
        ];
        return $parsedLease;
    }


    /**
     * Extracts a specific parameter from an unparsed DHCP lease block.
     *
     * This method takes a lease parameter name and the entire unparsed lease block,
     * uses the appropriate regex pattern to extract the parameter value, and returns
     * the extracted value. If the parameter is not found, it returns null.
     *
     * @param string $leaseParameter    The name of the DHCP lease parameter to extract.
     * @param string $unparsedLeaseBlock The unparsed DHCP lease block containing the parameter.
     *
     * @return string|null  The extracted value of the specified parameter, or null if not found.
     */
    private function extractLeaseParameter(string $leaseParameter, string $unparsedLeaseBlock) : ?string {
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
                // Extract IP address - https://regex101.com/r/mJ4Tdc/2
                preg_match('/\b([1-9](\d{1,2})?\.[0-9](\d{1,2})?\.[0-9](\d{1,2})?\.[1-9](\d{1,2})?)\b/', 
                            $unparsedLeaseBlock, $ipMatches);
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

    # Extracts the mac address and vendor class identifier from a lease block and returns as an array
    private function extractVendor(string $unparsedLeaseBlock) : array {
        // Extract MAC address and vendor-class-identifier - https://regex101.com/r/gNZEfZ/1
        preg_match('/hardware ethernet (.*?);.*?set vendor-class-identifier = "(.*?)";/s', 
                    $unparsedLeaseBlock, $matches);
        // Return an array with the mac address and vendor class identifier adn null for each if not found
        return [$matches[1] ?? null, $matches[2] ?? null];
    }


}
