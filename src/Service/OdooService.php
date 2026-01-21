<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;

class OdooService
{
    private $client;
    private $logger;
    private $odooUrl;
    private $odooDb;
    private $odooUsername;
    private $odooPassword;

    public function __construct(LoggerInterface $logger, string $odooUrl = null, string $odooDb = null, string $odooUsername = null, string $odooPassword = null)
    {
        $this->logger = $logger;
        $this->client = HttpClient::create();
        $this->odooUrl = $odooUrl ?: $_ENV['ODOO_URL'] ?? 'http://localhost:8069';
        $this->odooDb = $odooDb ?: $_ENV['ODOO_DB'] ?? 'inventory_app';
        $this->odooUsername = $odooUsername ?: $_ENV['ODOO_USERNAME'] ?? 'admin';
        $this->odooPassword = $odooPassword ?: $_ENV['ODOO_PASSWORD'] ?? 'admin';
    }

    public function authenticate(): ?int
    {
        try {
            $response = $this->client->request('POST', $this->odooUrl . '/xmlrpc/2/common', [
                'headers' => ['Content-Type' => 'text/xml'],
                'body' => $this->createXmlRpcRequest('authenticate', [
                    $this->odooDb,
                    $this->odooUsername,
                    $this->odooPassword,
                    []
                ])
            ]);

            $xml = simplexml_load_string($response->getContent());
            if ($xml && isset($xml->params->param->value->int)) {
                return (int) $xml->params->param->value->int;
            }

            return null;
        } catch (\Exception $e) {
            $this->logger->error('Odoo authentication failed: ' . $e->getMessage());
            return null;
        }
    }

    public function importInventoryData(string $apiToken, array $aggregatedData): bool
    {
        $userId = $this->authenticate();
        if (!$userId) {
            return false;
        }

        try {

            $inventoryId = $this->createOrUpdateInventory($aggregatedData['inventory'], $userId);
            
            if (!$inventoryId) {
                return false;
            }


            foreach ($aggregatedData['fields'] as $field) {
                $this->createOrUpdateField($inventoryId, $field, $userId);
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to import inventory data to Odoo: ' . $e->getMessage());
            return false;
        }
    }

    private function createOrUpdateInventory(array $inventoryData, int $userId): ?int
    {
        $inventoryModel = 'x_inventory';
        

        $searchResponse = $this->executeKw($userId, $inventoryModel, 'search', [[['name', '=', $inventoryData['title']]]]);
        
        if (!empty($searchResponse)) {

            $this->executeKw($userId, $inventoryModel, 'write', [$searchResponse[0], [
                'description' => $inventoryData['description'],
                'category' => $inventoryData['category'],
                'total_items' => $inventoryData['total_items'],
                'total_fields' => $inventoryData['total_fields'],
                'last_import' => date('Y-m-d H:i:s'),
            ]]);
            return $searchResponse[0];
        } else {

            $createResponse = $this->executeKw($userId, $inventoryModel, 'create', [[
                'name' => $inventoryData['title'],
                'description' => $inventoryData['description'],
                'category' => $inventoryData['category'],
                'total_items' => $inventoryData['total_items'],
                'total_fields' => $inventoryData['total_fields'],
                'import_date' => date('Y-m-d H:i:s'),
            ]]);
            
            return is_array($createResponse) ? $createResponse[0] : $createResponse;
        }
    }

    private function createOrUpdateField(int $inventoryId, array $fieldData, int $userId): ?int
    {
        $fieldModel = 'x_inventory_field';
        

        $searchResponse = $this->executeKw($userId, $fieldModel, 'search', [[
            ['inventory_id', '=', $inventoryId],
            ['name', '=', $fieldData['title']]
        ]]);
        
        $aggregatedData = json_encode($fieldData['aggregation']);
        
        if (!empty($searchResponse)) {

            $this->executeKw($userId, $fieldModel, 'write', [$searchResponse[0], [
                'field_type' => $fieldData['type'],
                'required' => $fieldData['required'],
                'order_index' => $fieldData['order_index'],
                'total_values' => $fieldData['total_values'],
                'empty_values' => $fieldData['empty_values'],
                'aggregated_data' => $aggregatedData,
                'last_import' => date('Y-m-d H:i:s'),
            ]]);
            return $searchResponse[0];
        } else {
            $createResponse = $this->executeKw($userId, $fieldModel, 'create', [[
                'inventory_id' => $inventoryId,
                'name' => $fieldData['title'],
                'field_type' => $fieldData['type'],
                'required' => $fieldData['required'],
                'order_index' => $fieldData['order_index'],
                'total_values' => $fieldData['total_values'],
                'empty_values' => $fieldData['empty_values'],
                'aggregated_data' => $aggregatedData,
                'import_date' => date('Y-m-d H:i:s'),
            ]]);
            
            return is_array($createResponse) ? $createResponse[0] : $createResponse;
        }
    }

    private function executeKw(int $userId, string $model, string $method, array $params): array
    {
        $response = $this->client->request('POST', $this->odooUrl . '/xmlrpc/2/object', [
            'headers' => ['Content-Type' => 'text/xml'],
            'body' => $this->createXmlRpcRequest('execute_kw', [
                $this->odooDb,
                $userId,
                $this->odooPassword,
                $model,
                $method,
                $params
            ])
        ]);

        $xml = simplexml_load_string($response->getContent());
        if ($xml && isset($xml->params->param->value->array->data)) {
            return $this->parseXmlRpcResponse($xml->params->param->value->array->data);
        }

        return [];
    }

    private function createXmlRpcRequest(string $method, array $params): string
    {
        $xml = '<?xml version="1.0"?>';
        $xml .= '<methodCall>';
        $xml .= '<methodName>' . $method . '</methodName>';
        $xml .= '<params>';
        
        foreach ($params as $param) {
            $xml .= '<param><value>' . $this->arrayToXml($param) . '</value></param>';
        }
        
        $xml .= '</params>';
        $xml .= '</methodCall>';
        
        return $xml;
    }

    private function arrayToXml($data): string
    {
        if (is_array($data)) {
            $xml = '<array><data>';
            foreach ($data as $item) {
                $xml .= '<value>' . $this->arrayToXml($item) . '</value>';
            }
            $xml .= '</data></array>';
        } elseif (is_string($data)) {
            $xml = '<string>' . htmlspecialchars($data) . '</string>';
        } elseif (is_int($data)) {
            $xml = '<int>' . $data . '</int>';
        } elseif (is_bool($data)) {
            $xml = '<boolean>' . ($data ? '1' : '0') . '</boolean>';
        } else {
            $xml = '<string>' . htmlspecialchars((string)$data) . '</string>';
        }
        
        return $xml;
    }

    private function parseXmlRpcResponse($data): array
    {
        $result = [];
        if (isset($data->value)) {
            foreach ($data->value as $value) {
                if (isset($value->int)) {
                    $result[] = (int) $value->int;
                } elseif (isset($value->string)) {
                    $result[] = (string) $value->string;
                } elseif (isset($value->boolean)) {
                    $result[] = (bool) $value->boolean;
                }
            }
        }
        return $result;
    }
}
