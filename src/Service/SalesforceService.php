<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;

class SalesforceService
{
    private $client;
    private $logger;
    private $clientId;
    private $clientSecret;
    private $username;
    private $password;
    private $securityToken;
    private $instanceUrl;
    private $accessToken;

    public function __construct(LoggerInterface $logger, string $salesforceClientId = null, string $salesforceClientSecret = null, string $salesforceUsername = null, string $salesforcePassword = null, string $salesforceSecurityToken = null)
    {
        $this->logger = $logger;
        $this->client = HttpClient::create();
        $this->clientId = $salesforceClientId ?: $_ENV['SALESFORCE_CLIENT_ID'] ?? null;
        $this->clientSecret = $salesforceClientSecret ?: $_ENV['SALESFORCE_CLIENT_SECRET'] ?? null;
        $this->username = $salesforceUsername ?: $_ENV['SALESFORCE_USERNAME'] ?? null;
        $this->password = $salesforcePassword ?: $_ENV['SALESFORCE_PASSWORD'] ?? null;
        $this->securityToken = $salesforceSecurityToken ?: $_ENV['SALESFORCE_SECURITY_TOKEN'] ?? null;
    }

    public function authenticate(): bool
    {
        if (!$this->clientId || !$this->clientSecret || !$this->username || !$this->password) {
            $this->logger->error('Salesforce credentials not configured');
            return false;
        }

        try {
            $response = $this->client->request('POST', 'https://login.salesforce.com/services/oauth2/token', [
                'body' => [
                    'grant_type' => 'password',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'username' => $this->username,
                    'password' => $this->password . $this->securityToken,
                ]
            ]);

            $data = $response->toArray();
            $this->accessToken = $data['access_token'];
            $this->instanceUrl = $data['instance_url'];

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Salesforce authentication failed: ' . $e->getMessage());
            return false;
        }
    }

    public function createAccount(array $accountData): ?string
    {
        if (!$this->authenticate()) {
            return null;
        }

        try {
            $response = $this->client->request('POST', $this->instanceUrl . '/services/data/v58.0/sobjects/Account/', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $accountData
            ]);

            if ($response->getStatusCode() === 201) {
                $data = $response->toArray();
                return $data['id'];
            }

            $this->logger->error('Failed to create Account: ' . $response->getContent(false));
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Error creating Account: ' . $e->getMessage());
            return null;
        }
    }

    public function createContact(array $contactData, string $accountId): ?string
    {
        if (!$this->authenticate()) {
            return null;
        }

        $contactData['AccountId'] = $accountId;

        try {
            $response = $this->client->request('POST', $this->instanceUrl . '/services/data/v58.0/sobjects/Contact/', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $contactData
            ]);

            if ($response->getStatusCode() === 201) {
                $data = $response->toArray();
                return $data['id'];
            }

            $this->logger->error('Failed to create Contact: ' . $response->getContent(false));
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Error creating Contact: ' . $e->getMessage());
            return null;
        }
    }

    public function createAccountAndContact(array $userData): ?array
    {
        $accountData = [
            'Name' => $userData['company_name'] ?? $userData['name'] . "'s Company",
            'Phone' => $userData['phone'] ?? null,
            'Website' => $userData['website'] ?? null,
            'Industry' => $userData['industry'] ?? 'Technology',
            'Description' => $userData['description'] ?? 'Customer from Inventory App',
        ];

        $accountId = $this->createAccount($accountData);
        if (!$accountId) {
            return null;
        }

        $contactData = [
            'FirstName' => $userData['first_name'] ?? $userData['name'],
            'LastName' => $userData['last_name'] ?? 'User',
            'Email' => $userData['email'],
            'Phone' => $userData['phone'] ?? null,
            'Title' => $userData['job_title'] ?? null,
            'LeadSource' => 'Inventory App',
        ];

        $contactId = $this->createContact($contactData, $accountId);
        if (!$contactId) {
            return null;
        }

        return [
            'account_id' => $accountId,
            'contact_id' => $contactId,
            'success' => true
        ];
    }
}
