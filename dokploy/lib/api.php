<?php

class DokployAPI
{
    private $host;
    private $token;
    private $timeout = 30;

    public function __construct($host, $token)
    {
        $this->host = rtrim($host, '/');
        $this->token = $token;
    }

    private function request($method, $endpoint, $data = null)
    {
        $url = $this->host . '/api/' . ltrim($endpoint, '/');
        $ch = curl_init();

        $headers = [
            'Accept: application/json',
            'x-api-key: ' . $this->token
        ];

        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
        ];

        switch (strtoupper($method)) {
            case 'POST':
                $opts[CURLOPT_POST] = true;
                if ($data !== null) {
                    $opts[CURLOPT_POSTFIELDS] = json_encode($data);
                }
                $headers[] = 'Content-Type: application/json';
                break;

            case 'DELETE':
                $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                $headers[] = 'Content-Type: application/json';
                break;

            default:
                $opts[CURLOPT_HTTPGET] = true;
                break;
        }

        $opts[CURLOPT_HTTPHEADER] = $headers;

        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);

        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($errno) {
            throw new Exception('Curl error: ' . $err);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $msg = isset($decoded['message']) ? $decoded['message'] : $response;
            throw new Exception('API error: ' . $msg . ' (HTTP ' . $httpCode . ')');
        }

        return $decoded;
    }

    // Create organization
    public function createOrganization($payload)
    {
        return $this->request('POST', 'organization.create', $payload);
    }

    // Delete organization
    public function deleteOrganization($payload)
    {
        return $this->request('POST', 'organization.delete', $payload);
    }

    // Get organization by ID
    public function getOrganization($orgId)
    {
        return $this->request('GET', 'organization.one' . $orgId);
    }

    // Create new user
    public function createUser($payload)
    {
        return $this->request('POST', 'auth/organization/invite-member', $payload);
    }
}
