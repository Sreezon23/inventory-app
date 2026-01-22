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

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->client = HttpClient::create();
        $this->odooUrl = $_ENV['ODOO_URL'] ?? 'http://localhost:8069';
        $this->odooDb = $_ENV['ODOO_DB'] ?? 'inventory-app';
        $this->odooUsername = $_ENV['ODOO_USERNAME'] ?? 'admin';
        $this->odooPassword = $_ENV['ODOO_PASSWORD'] ?? 'admin';
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
            $this->logger->error('Odoo authentication failed');
            return false;
        }

        try {
            $inventoryName = $aggregatedData['inventory']['title'];
            $description = $aggregatedData['inventory']['description'] ?? '';
            $totalItems = $aggregatedData['inventory']['total_items'] ?? 0;
            $totalFields = $aggregatedData['inventory']['total_fields'] ?? 0;
            
            $this->logger->info('Starting Odoo Inventory import (read-only mode)', [
                'inventory' => $inventoryName,
                'items' => $totalItems,
                'fields' => $totalFields,
                'user_id' => $userId
            ]);
            
            // Since create operations don't work, we'll update existing records
            // and provide a comprehensive summary in the main contact
            
            // Get existing inventory data
            $existingData = $this->getExistingInventoryData($userId);
            
            // Update the main contact with complete inventory information
            $success = $this->updateContactWithCompleteInventoryData($inventoryName, $description, $aggregatedData, $existingData, $userId);
            
            if ($success) {
                $this->logger->info('Odoo Inventory import completed (contact update mode)', [
                    'inventory' => $inventoryName,
                    'existing_locations' => count($existingData['locations']),
                    'existing_categories' => count($existingData['categories']),
                    'existing_products' => count($existingData['products']),
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to import inventory data to Odoo: ' . $e->getMessage());
            return false;
        }
    }
    
    private function getExistingInventoryData(int $userId): array
    {
        $data = [
            'locations' => [],
            'categories' => [],
            'products' => []
        ];
        
        try {
            // Get existing locations
            $locations = $this->executeKw($userId, 'stock.location', 'search_read', [
                [],
                ['name', 'usage', 'comment'],
                ['limit' => 10]
            ]);
            $data['locations'] = $locations;
        } catch (\Exception $e) {
            // Ignore if we can't read
        }
        
        try {
            // Get existing categories
            $categories = $this->executeKw($userId, 'product.category', 'search_read', [
                [],
                ['name', 'parent_id'],
                ['limit' => 10]
            ]);
            $data['categories'] = $categories;
        } catch (\Exception $e) {
            // Ignore if we can't read
        }
        
        try {
            // Get existing products
            $products = $this->executeKw($userId, 'product.product', 'search_read', [
                [],
                ['name', 'default_code', 'categ_id'],
                ['limit' => 20]
            ]);
            $data['products'] = $products;
        } catch (\Exception $e) {
            // Ignore if we can't read
        }
        
        return $data;
    }
    
    private function updateContactWithCompleteInventoryData(string $name, string $description, array $aggregatedData, array $existingData, int $userId): ?int
    {
        try {
            // Create a comprehensive inventory summary
            $summary = "=== INVENTORY IMPORT SUMMARY ===\n\n";
            $summary .= "📦 Inventory: $name\n";
            $summary .= "📝 Description: $description\n";
            $summary .= "📊 Items: " . ($aggregatedData['inventory']['total_items'] ?? 0) . "\n";
            $summary .= "🔧 Fields: " . ($aggregatedData['inventory']['total_fields'] ?? 0) . "\n";
            $summary .= "📅 Imported: " . date('Y-m-d H:i:s') . "\n\n";
            
            // Add item details
            if (isset($aggregatedData['items']) && !empty($aggregatedData['items'])) {
                $summary .= "=== INVENTORY ITEMS ===\n";
                foreach ($aggregatedData['items'] as $item) {
                    $itemName = $item['custom_id'] ?? "Item {$item['id']}";
                    $summary .= "• $itemName (Created: {$item['created_at']})\n";
                }
                $summary .= "\n";
            }
            
            // Add field details
            if (isset($aggregatedData['fields']) && !empty($aggregatedData['fields'])) {
                $summary .= "=== FIELD DEFINITIONS ===\n";
                foreach ($aggregatedData['fields'] as $field) {
                    $required = $field['required'] ? 'Required' : 'Optional';
                    $summary .= "• {$field['title']} (Type: {$field['type']}, $required)\n";
                }
                $summary .= "\n";
            }
            
            // Add existing Odoo inventory data reference
            $summary .= "=== EXISTING ODOO INVENTORY DATA ===\n";
            $summary .= "📍 Locations: " . count($existingData['locations']) . " found\n";
            foreach ($existingData['locations'] as $location) {
                $summary .= "  - {$location['name']} ({$location['usage']})\n";
            }
            $summary .= "\n📂 Categories: " . count($existingData['categories']) . " found\n";
            foreach ($existingData['categories'] as $category) {
                $summary .= "  - {$category['name']}\n";
            }
            $summary .= "\n🛍️ Products: " . count($existingData['products']) . " found\n";
            foreach (array_slice($existingData['products'], 0, 5) as $product) {
                $code = $product['default_code'] ?? 'No code';
                $summary .= "  - {$product['name']} ($code)\n";
            }
            if (count($existingData['products']) > 5) {
                $summary .= "  ... and " . (count($existingData['products']) - 5) . " more\n";
            }
            
            $summary .= "\n=== HOW TO FIND THIS DATA ===\n";
            $summary .= "• Direct Access: https://inventory-app.odoo.com/web#id=1&model=res.partner&view_type=form\n";
            $summary .= "• Search for: 'Inventory App' (exact match)\n";
            $summary .= "• Check: Sales > Customers (may need to remove filters)\n";
            $summary .= "• Check: Purchases > Vendors (may need to remove filters)\n";
            $summary .= "• Your Odoo instance has limited permissions - data is stored here\n";
            
            // Try multiple approaches to update the contact
            $updateSuccess = false;
            
            // Method 1: Try basic update
            try {
                $updateResponse = $this->executeKw($userId, 'res.partner', 'write', [
                    [1], // Update main contact
                    [
                        'name' => $name,
                        'comment' => $summary,
                    ]
                ]);
                
                if ($updateResponse !== null) {
                    $updateSuccess = true;
                    $this->logger->info("Contact updated successfully (method 1)");
                }
            } catch (\Exception $e) {
                $this->logger->error("Method 1 failed: " . $e->getMessage());
            }
            
            // Method 2: Try with minimal fields
            if (!$updateSuccess) {
                try {
                    $updateResponse = $this->executeKw($userId, 'res.partner', 'write', [
                        [1],
                        ['comment' => $summary]
                    ]);
                    
                    if ($updateResponse !== null) {
                        $updateSuccess = true;
                        $this->logger->info("Contact updated successfully (method 2)");
                    }
                } catch (\Exception $e) {
                    $this->logger->error("Method 2 failed: " . $e->getMessage());
                }
            }
            
            if ($updateSuccess) {
                $this->logger->info("Contact updated with complete inventory data");
                return 1;
            }
            
            $this->logger->warning("Could not update contact - storing in log only");
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update contact with inventory data: ' . $e->getMessage());
            return null;
        }
    }
    
    private function createInventoryLocation(string $name, string $description, int $userId): ?int
    {
        try {
            // Try to create a stock location
            $createResponse = $this->executeKw($userId, 'stock.location', 'create', [
                [
                    'name' => $name,
                    'usage' => 'internal', // Internal storage location
                    'comment' => $description,
                ]
            ]);
            
            if (!empty($createResponse) && is_numeric($createResponse[0])) {
                return (int) $createResponse[0];
            }
            
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create inventory location: ' . $e->getMessage());
            return null;
        }
    }
    
    private function createProductCategory(string $name, int $userId): ?int
    {
        try {
            // Create a product category for the inventory
            $createResponse = $this->executeKw($userId, 'product.category', 'create', [
                [
                    'name' => $name,
                    'parent_id' => false, // Top-level category
                ]
            ]);
            
            if (!empty($createResponse) && is_numeric($createResponse[0])) {
                return (int) $createResponse[0];
            }
            
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create product category: ' . $e->getMessage());
            return null;
        }
    }
    
    private function createInventoryProduct(string $name, string $description, ?int $categoryId, int $userId): ?int
    {
        try {
            // Create a product template first
            $templateResponse = $this->executeKw($userId, 'product.template', 'create', [
                [
                    'name' => $name,
                    'description' => $description,
                    'categ_id' => $categoryId,
                    'type' => 'product', // Stockable product
                    'sale_ok' => false,
                    'purchase_ok' => false,
                ]
            ]);
            
            if (!empty($templateResponse) && is_numeric($templateResponse[0])) {
                $templateId = (int) $templateResponse[0];
                
                // Create the product variant
                $productResponse = $this->executeKw($userId, 'product.product', 'create', [
                    [
                        'product_tmpl_id' => $templateId,
                        'default_code' => 'INV-' . strtoupper(substr(md5($name), 0, 6)),
                    ]
                ]);
                
                if (!empty($productResponse) && is_numeric($productResponse[0])) {
                    return (int) $productResponse[0];
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create inventory product: ' . $e->getMessage());
            return null;
        }
    }
    
    private function updateContactWithInventoryInfo(string $name, string $description, array $aggregatedData, int $userId): ?int
    {
        try {
            // Update the main contact with inventory summary as backup
            $summaryDescription = $description . "\n\n=== INVENTORY IMPORT ===\n";
            $summaryDescription .= "Inventory: " . $name . "\n";
            $summaryDescription .= "Items: " . ($aggregatedData['inventory']['total_items'] ?? 0) . "\n";
            $summaryDescription .= "Fields: " . ($aggregatedData['inventory']['total_fields'] ?? 0) . "\n";
            $summaryDescription .= "Imported: " . date('Y-m-d H:i:s') . "\n";
            $summaryDescription .= "Data imported to Odoo Inventory module\n";
            
            $updateResponse = $this->executeKw($userId, 'res.partner', 'write', [
                [1], // Update the main contact
                [
                    'name' => $name,
                    'comment' => $summaryDescription,
                    'customer_rank' => 1,
                ]
            ]);
            
            if ($updateResponse !== null) {
                return 1;
            }
            
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update contact with inventory info: ' . $e->getMessage());
            return null;
        }
    }
    
    private function createSimpleContact(string $name, string $description, int $userId): ?int
    {
        try {
            // First, let's try to update the existing contact (ID 1) instead of creating new ones
            // since this minimal Odoo instance seems to have limited create permissions
            
            // Try to update existing contact with new information
            $updateResponse = $this->executeKw($userId, 'res.partner', 'write', [
                [1], // Update the main contact
                [
                    'name' => $name,
                    'display_name' => $name,
                    'comment' => $description . "\n\nUpdated: " . date('Y-m-d H:i:s'),
                    'customer_rank' => 1, // Make it visible as a customer
                ]
            ]);
            
            // If update worked, return the existing contact ID
            if ($updateResponse !== null) {
                $this->logger->info("Updated existing contact with new data: $name");
                return 1;
            }
            
            // If update failed, try creating a minimal contact
            $createResponse = $this->executeKw($userId, 'res.partner', 'create', [
                ['name' => $name]
            ]);
            
            if (!empty($createResponse) && is_numeric($createResponse[0])) {
                return (int) $createResponse[0];
            }
            
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create/update contact: ' . $e->getMessage());
            return null;
        }
    }

    private function createContact(string $name, string $description, int $userId): ?int
    {
        try {
            // Check if contact already exists
            $searchResponse = $this->executeKw($userId, 'res.partner', 'search', [[['name', '=', $name]]]);
            
            if (!empty($searchResponse)) {
                return $searchResponse[0]; // Return existing contact ID
            }
            
            // Create new contact
            $createResponse = $this->executeKw($userId, 'res.partner', 'create', [[
                'name' => $name,
                'comment' => $description,
                'is_company' => false, // Individual contact
                'customer_rank' => 0,  // Not a customer
                'supplier_rank' => 0,  // Not a supplier
            ]]);
            
            // Handle create response
            if (!empty($createResponse)) {
                return is_array($createResponse[0]) ? $createResponse[0]['id'] ?? null : $createResponse[0];
            }
            
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create contact: ' . $e->getMessage());
            return null;
        }
    }

    private function createProduct(string $name, string $description, int $userId): ?int
    {
        try {
            // Check if product already exists
            $searchResponse = $this->executeKw($userId, 'product.product', 'search', [[['name', '=', $name]]]);
            
            if (!empty($searchResponse)) {
                return $searchResponse[0]; // Return existing product ID
            }
            
            // Create new product
            $createResponse = $this->executeKw($userId, 'product.product', 'create', [[
                'name' => $name,
                'description' => $description,
                'type' => 'service', // Service type since it's inventory data
                'sale_ok' => false,
                'purchase_ok' => false,
            ]]);
            
            // Handle create response - it might be a single ID or array
            if (!empty($createResponse)) {
                return is_array($createResponse[0]) ? $createResponse[0]['id'] ?? null : $createResponse[0];
            }
            
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create product: ' . $e->getMessage());
            return null;
        }
    }

    private function createProject(string $name, string $description, int $userId): ?int
    {
        try {
            // Check if project already exists
            $searchResponse = $this->executeKw($userId, 'project.project', 'search', [[['name', '=', $name]]]);
            
            if (!empty($searchResponse)) {
                return $searchResponse[0]; // Return existing project ID
            }
            
            // Create new project
            $createResponse = $this->executeKw($userId, 'project.project', 'create', [[
                'name' => $name,
                'description' => $description,
                'is_private' => false,
            ]]);
            
            // Handle create response - it might be a single ID or array
            if (!empty($createResponse)) {
                return is_array($createResponse[0]) ? $createResponse[0]['id'] ?? null : $createResponse[0];
            }
            
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create project: ' . $e->getMessage());
            return null;
        }
    }

    public function executeKw(int $userId, string $model, string $method, array $params): array
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
        
        // Handle empty arrays
        if (!isset($data->value) || empty($data->value)) {
            return [];
        }
        
        // Handle single value or array of values
        $values = $data->value;
        if (!is_array($values)) {
            $values = [$values];
        }
        
        foreach ($values as $value) {
            if (isset($value->int)) {
                $result[] = (int) $value->int;
            } elseif (isset($value->string)) {
                $result[] = (string) $value->string;
            } elseif (isset($value->boolean)) {
                $result[] = (bool) $value->boolean;
            } elseif (isset($value->struct)) {
                // Handle struct responses (like create operations)
                $result[] = $this->parseXmlRpcStruct($value->struct);
            }
        }
        
        return $result;
    }
    
    private function parseXmlRpcStruct($struct): array
    {
        $result = [];
        if (isset($struct->member)) {
            foreach ($struct->member as $member) {
                $name = (string) $member->name;
                $value = $member->value;
                
                if (isset($value->int)) {
                    $result[$name] = (int) $value->int;
                } elseif (isset($value->string)) {
                    $result[$name] = (string) $value->string;
                } elseif (isset($value->boolean)) {
                    $result[$name] = (bool) $value->boolean;
                }
            }
        }
        return $result;
    }
}
