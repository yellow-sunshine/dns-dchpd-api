<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Classes\DhcpdLeaseFileProcessor;

class DhcpdLeases
{

    private $config;
    private $leaseFileProcessor;


    /**
     * Constructor for DhcpdLeases Controller.
     *
     * @param DhcpdLeaseFileProcessor $leaseFileProcessor  The processor for DHCP lease files.
     * @param array $config  Configuration settings for the controller.
     */
    public function __construct(DhcpdLeaseFileProcessor $leaseFileProcessor, array $config){
        $this->config = $config;
        $this->leaseFileProcessor = $leaseFileProcessor;
    }


    /**
     * Retrieve and process DHCP leases, then respond accordingly.
     *
     * @param Request $request  The HTTP request object.
     * @param Response $response  The HTTP response object.
     *
     * @return Response  The HTTP response containing JSON-encoded DHCP leases information
     *                  or an error message if the DHCP leases file is not found or cannot be read.
     */
    public function index(Request $request, Response $response): Response {
        // Process the leases file and return the content
        $leases = $this->leaseFileProcessor->getLeases($this->config['paths']['dhcpLeasesFileLocation']);
        if ($leases !== false) {
            // Success: File found and parsed
            return $this->respond('leases', $leases, 200, $response);
        } else {
            // Error: File not found or file cant be read
            return $this->respond('error', 'DHCP leases file not found or can not be read', 404, $response);
        }
    }


    /**
     * Generate a JSON response with the specified key, content, and HTTP status code.
     *
     * @param string $key                The key for the JSON response.
     * @param mixed $responseContent     The content to be included in the response. Can be a string or an array.
     * @param int $httpResponseCode      The HTTP status code for the response.
     * @param Response $response         The HTTP response object.
     *
     * @return Response  The HTTP response containing JSON-encoded data with the specified key, content, and status code.
     */
    private function respond(string $key, mixed $responseContent, int $httpResponseCode, Response $response): Response {
        $response->getBody()->write(json_encode([$key => $responseContent]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($httpResponseCode);
    }

}
