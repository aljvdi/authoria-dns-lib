<?php
/**
 * AuthoriaDNS.php - AuthoriaDNS class
 * This class is used to manage DNS records for the AuthoriaDNS service
 * @package AuthoriaDNS
 * @author Alex Javadi
 * @version 1.0.0
 * @since 1.0.0
 */
namespace Javadi\Authoria\Dns;

use function PHPUnit\Framework\throwException;

class AuthoriaDNS
{
    private array $config; // Configuration array (API URL, etc.)

    /**
     * Request Manager is a private function that manages the requests to the AuthoriaDNS API, basically it's a wrapper for the cURL requests
     * @param string $path The path to the API endpoint
     * @param string $method The HTTP method to use (GET, POST, PUT, OPTIONS)
     * @param array|null $data The data to send with the request (if any, default is null)
     * @param bool $json_body Whether to send the data as JSON (default is false)
     * @return array The response from the API (decoded JSON, or exception in case of error)
     * @throws \Exception If the request fails, an exception is thrown
     */
    private function RequestManager(string $path, string $method, ?array $data, bool $json_body = false): array
    {
        try {
            // Initialise cURL
            $ch = curl_init($this->config['base_url'] . $path);

            // Set the cURL options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            if (!$this->config['ssl_check']) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST,
                (in_array(strtoupper($method), ['GET', 'POST', 'PUT', 'OPTIONS']) ? strtoupper($method) : throw new \RuntimeException('Invalid HTTP method!'))
            );

            if ($data) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: ' . ($json_body ? 'application/json' : 'application/x-www-form-urlencoded')]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, ($json_body ? json_encode($data) : http_build_query($data)));
            }

            // Execute the request
            $response = curl_exec($ch);

            // Check for errors
            if ($response === false) {
                throw new \Exception(curl_error($ch), curl_errno($ch));
            }

            return json_decode($response, true) ?? throw new \RuntimeException('AuthoriaDNS API response could not be decoded! ' . $response);
        }
        catch (\Exception $e) {
            die("AuthoriaDNS API CRequest failed: <b>{$e->getMessage()}</b>. <br> Please try again later. (Trace: {$e->getTraceAsString()}).");
        }
        finally {
            // Close the cURL session
            curl_close($ch);
        }
    }

    /**
     * AuthoriaDNS constructor.
     * @param string $authoriaDnsInstanceURL The URL of the AuthoriaDNS instance to connect to
     * @param bool $ssl_check Whether to check the SSL certificate (default is true)
     * @throws \Exception If the URL is invalid, or the server is not reachable or doesn't respond
     */
    public function __construct(string $authoriaDnsInstanceURL, bool $ssl_check = true)
    {

        try {

            // If URL hasn't http or https, we'll add it
            if (!preg_match('/^https?:\/\//', $authoriaDnsInstanceURL))
                $authoriaDnsInstanceURL = ($ssl_check ? 'https://' : 'http://') . $authoriaDnsInstanceURL;

            // Check if the URL is valid
            if (!filter_var($authoriaDnsInstanceURL, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException('Invalid AuthoriaDNS instance URL!');
            }

            $this->config = [
                'base_url' => $authoriaDnsInstanceURL . '/api/v1',
                'ssl_check' => $ssl_check
            ];

            // verify the URL
            $server_response = $this->RequestManager("/is-that-authoria", 'OPTIONS', null);


            if (!$server_response['authoria'] ?? false) {
                throw new \RuntimeException('The server is not an AuthoriaDNS instance!');
            }
        }
        catch (\Exception $e) {
            die("AuthoriaDNS API Initialisation failed: <b>{$e->getMessage()}</b>. <br> Please try again later. (Trace: {$e->getTraceAsString()}).");
        }
    }

    /**
     * New DNS Verification Request
     * @param string $domain The domain to verify (e.g. example.com)
     * @param int $ttl The TTL for the DNS record (in seconds, default is 300 seconds = 5 minutes)
     * @return string|array The ID of the verification request plus the token, or an error message
     * @throws \Exception If the request fails, an exception is thrown
     */
    public function new(string $domain, int $ttl = 300): string|array
    {
        $req = $this->RequestManager('/new', 'POST', ['domain' => $domain, 'ttl' => $ttl]);

        if ($req['error'] ?? false) {
            return $req['error'];
        }

        return ['id' => $req['id'], 'token' => $req['TXT_record_to_verify'], 'how_to_verify' => "Add a TXT record with the value '{$req['TXT_record_to_verify']}' to your domain's DNS records"];
    }

    /**
     * Get DNS Verification Request Details (and the Status of the Verification)
     * @param string $id The ID of the verification request
     * @return array The details of the verification request, including the status
     * @throws \Exception If the request fails, an exception is thrown
     */
    public function verify(string $id): array
    {
        return $this->RequestManager("/verify?id=$id", 'GET', null);
    }

    /**
     * Bulk Result of DNS Verification Requests
     * @param array $ids The IDs of the verification requests
     * @return array The details of the verification requests, including the status
     * @throws \Exception If the request fails, an exception is thrown
     */
    public function bulkVerify(array $ids): array
    {
        return $this->RequestManager("/bulk-verify", 'POST', ['ids' => $ids], true);
    }
}