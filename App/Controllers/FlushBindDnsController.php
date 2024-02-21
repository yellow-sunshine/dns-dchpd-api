<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlushBindDnsController
{
    /**
     * Initiates the flushing of DNS cache using the system command.
     * Executes the necessary command to flush DNS cache and returns a JSON response
     * indicating success or failure of the operation.
     *
     * @param Request $request  The HTTP request object.
     * @param Response $response  The HTTP response object.
     *
     * @return Response  The HTTP response containing a success message if DNS flushing was
     *                  successful or an error message if the flushing operation failed.
     */
    public function flushDns(Request $request, Response $response): Response
    {
        if ($this->executeflushDns() === 0) {
            // Success
            $response->getBody()->write(json_encode(['message' => 'DNS flushed successfully']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            // Failed
            $response->getBody()->write(json_encode(['error' => 'Failed to flush DNS']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * Executes the system command to flush the DNS cache using the "rndc" tool with sudo privileges.
     * The command runs "sudo /usr/sbin/rndc flush" to initiate the DNS cache flushing process.
     *
     * @return int  The exit status code of the executed command. A value of 0 indicates success,
     *              while non-zero values indicate an error or failure during the command execution.
     */
    private function executeflushDns(): int
    {
        $command = 'sudo /usr/sbin/rndc flush';
        $output = null;
        $exitStatus = null;
        exec($command, $output, $exitStatus);
        return $exitStatus;
    }
}