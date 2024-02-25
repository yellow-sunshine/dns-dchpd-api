<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlushBindDns
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
     * Executes the rndc flush command to flush DNS cache and does so sudo privileges.
     * For this to work properly, the www-data user must have the this command in the sudoers file..
     *
     * @return int  The exit status code of the executed command. A value of 0 indicates success,
     *              while non-zero values indicate an error or failure during the command execution.
     */
    private function executeflushDns(): int
    {
        try {
            $command = 'sudo /usr/sbin/rndc flush';
            $output = null;
            $exitStatus = null;
            exec($command, $output, $exitStatus);
            return $exitStatus;
        } catch (Exception $e) {
            // Handle the exception here, you can log or perform other actions as needed
            return -1; // Or any other appropriate error code
        }
    }
}