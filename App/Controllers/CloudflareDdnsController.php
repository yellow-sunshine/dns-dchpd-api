<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CloudflareDdnsController
{

    /**
     * Get and return the contents of the "ip.json" file.
     *
     * This method reads the contents of the "ip.json" file, which stores
     * information about previous cloudflare DDNS updates, and returns the data 
     * in JSON format. If successful, it responds with a JSON message containing 
     * the retrieved IP details. In case of an exception, it returns an error 
     * message with a HTTP status code of 500 (Internal Server Error).
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @return Response The 200 HTTP response containing JSON representation of IP details
     *                 or an error message with a 500 status code.
     */
    public function show(Request $request, Response $response): Response
    {
        try {
            $ipJson = $this->readIpJson();
            $response->getBody()->write(json_encode(['message' => $ipJson]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Read and decode the contents of the "ip.json" file.
     *
     * This method reads the contents of the "ip.json" file, which is expected
     * to store JSON-formatted data, and returns the decoded array representation.
     * It performs error handling at each step, throwing exceptions in case of
     * file non-existence, failure to read contents, or failure to decode JSON.
     *
     * @return array The decoded array representation of the "ip.json" file contents.
     * @throws Exception If the file does not exist, if reading contents fails,
     *                   or if decoding the JSON content fails.
     */
    private function readIpJson(): array
    {
        $ipJsonPath = "/home/brent/bin/cloudflareDDNS/ip.json";
        if (!file_exists($ipJsonPath)) {
            throw new Exception('File does not exist.');
        }
        $ipJsonContents = file_get_contents($ipJsonPath);
        if ($ipJsonContents === false) {
            throw new Exception('Failed to read file contents.');
        }
        $ipJson = json_decode($ipJsonContents, true);
        if ($ipJson === null) {
            throw new Exception('Failed to decode JSON content.');
        }
        return $ipJson;
    }

    /**
     * Run Cloudflare DDNS update and force update if route is set to force.
     *
     * This method handles the Cloudflare DDNS update based on the route path in the request.
     * If the route path is '/cloudflare-ddns/run/force', it includes the '--force-update' flag
     * to force the update. It catches any exceptions that may occur during the process and
     * logs them for further investigation while returning appropriate JSON responses for success
     * or failure. This method does not actually execute the Cloudflare DDNS script, but rather
     * builds the command and options then calls the executeCloudflareDdns method to do so.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @return Response The HTTP response containing JSON representation of the update status.
     */
    public function runCloudflareDdns(Request $request, Response $response): Response
    {
        try {
            $routePath = $request->getUri()->getPath();
    
            // Use strtolower to make the comparison case-insensitive
            if (strtolower($routePath) == '/cloudflare-ddns/run/force') {
                $force = ' --force-update';
            }
    
            list($exitStatus, $output) = $this->executeCloudflareDdns($force ?? '');
    
            if ($exitStatus === 0) {
                // Success
                $response->getBody()->write(json_encode(['message' => 'CloudflareDDNS ran successfully', 'output' => $output]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                // Failed
                $response->getBody()->write(json_encode(['error' => 'Failed to flush DNS', 'output' => $output]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
        } catch (\Exception $e) {
            // Log the exception for further investigation
            error_log($e->getMessage());
    
            // Return a generic error response
            $response->getBody()->write(json_encode(['error' => 'Internal Server Error']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Execute the Cloudflare DDNS script with optional force update.
     *
     * This method constructs and executes the command to run the Cloudflare DDNS script
     * with the specified force update flag. It captures the script's output and exit status,
     * converting the output array to a string for easier display. It catches any exceptions
     * that may occur during the execution and returns an array containing the exit status
     * and output string. It is worth noting the command being executed is in the sudoers file to allow
     * www-data user to execute it without a password prompt. The command must be exact with or 
     * without the flag
     *
     * @param string $force Optional force update flag for the Cloudflare DDNS script.
     * @return array An array containing the exit status and output string of the script.
     */
    private function executeCloudflareDdns($force=''): array
    {
        try {
            $command = 'sudo /usr/bin/python3 /home/brent/bin/cloudflareDDNS/cloudflare_ddns.py /home/brent/bin/cloudflareDDNS/zones-public.json '.$force;
            $output = null;
            $exitStatus = null;
            exec($command, $output, $exitStatus);
            // Convert the array output to a string for easier display
            $outputString = implode("\n", $output);
            return [$exitStatus, $outputString];
        } catch (Exception $e) {
            // Handle exceptions if any
            return [1, 'Exception occurred: ' . $e->getMessage()];
        }
    }

}