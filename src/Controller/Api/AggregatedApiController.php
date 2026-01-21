<?php

namespace App\Controller\Api;

use App\Entity\ApiToken;
use App\Entity\Inventory;
use App\Entity\InventoryField;
use App\Entity\InventoryItem;
use App\Repository\ApiTokenRepository;
use App\Repository\InventoryFieldRepository;
use App\Repository\InventoryItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class AggregatedApiController extends AbstractController
{
    private ApiTokenRepository $apiTokenRepository;
    private InventoryItemRepository $itemRepository;
    private InventoryFieldRepository $fieldRepository;

    public function __construct(
        ApiTokenRepository $apiTokenRepository,
        InventoryItemRepository $itemRepository,
        InventoryFieldRepository $fieldRepository
    ) {
        $this->apiTokenRepository = $apiTokenRepository;
        $this->itemRepository = $itemRepository;
        $this->fieldRepository = $fieldRepository;
    }

    #[Route('/inventory/{token}/aggregated', name: 'api_inventory_aggregated', methods: ['GET'])]
    public function getAggregatedResults(string $token, Request $request): JsonResponse
    {
        // Validate API token
        $apiToken = $this->apiTokenRepository->findByToken($token);
        if (!$apiToken) {
            return new JsonResponse(['error' => 'Invalid or expired API token'], 401);
        }

        $inventory = $apiToken->getInventory();
        
        // Get all items for this inventory
        $items = $this->itemRepository->findBy(['inventory' => $inventory]);
        $fields = $this->fieldRepository->findBy(['inventory' => $inventory]);

        $aggregatedData = [
            'inventory' => [
                'id' => $inventory->getId(),
                'title' => $inventory->getTitle(),
                'description' => $inventory->getDescription(),
                'category' => $inventory->getCategory(),
                'created_at' => $inventory->getCreatedAt()->format('Y-m-d H:i:s'),
                'total_items' => count($items),
                'total_fields' => count($fields),
            ],
            'fields' => [],
            'aggregated_results' => []
        ];

        // Process each field and calculate aggregations
        foreach ($fields as $field) {
            $fieldData = [
                'id' => $field->getId(),
                'title' => $field->getTitle(),
                'type' => $field->getType(),
                'required' => $field->isRequired(),
                'order_index' => $field->getOrderIndex(),
            ];

            // Get all values for this field
            $values = [];
            foreach ($items as $item) {
                $fieldValue = $item->getFieldValue($field);
                if ($fieldValue !== null && $fieldValue !== '') {
                    $values[] = $fieldValue;
                }
            }

            // Calculate aggregations based on field type
            $aggregation = $this->calculateAggregation($field->getType(), $values);
            
            $fieldData['aggregation'] = $aggregation;
            $fieldData['total_values'] = count($values);
            $fieldData['empty_values'] = count($items) - count($values);

            $aggregatedData['fields'][] = $fieldData;
            $aggregatedData['aggregated_results'][$field->getTitle()] = $aggregation;
        }

        return new JsonResponse($aggregatedData);
    }

    private function calculateAggregation(string $fieldType, array $values): array
    {
        $result = [
            'type' => $fieldType,
            'count' => count($values),
        ];

        if (empty($values)) {
            return $result;
        }

        switch ($fieldType) {
            case 'number':
            case 'integer':
                $numericValues = array_filter($values, 'is_numeric');
                if (!empty($numericValues)) {
                    $result['min'] = min($numericValues);
                    $result['max'] = max($numericValues);
                    $result['average'] = array_sum($numericValues) / count($numericValues);
                    $result['sum'] = array_sum($numericValues);
                }
                break;

            case 'text':
            case 'textarea':
                // Most popular values (top 5)
                $valueCounts = array_count_values($values);
                arsort($valueCounts);
                $result['most_popular'] = array_slice($valueCounts, 0, 5, true);
                $result['unique_values'] = count($valueCounts);
                break;

            case 'select':
            case 'radio':
                // Count occurrences of each option
                $valueCounts = array_count_values($values);
                arsort($valueCounts);
                $result['distribution'] = $valueCounts;
                break;

            case 'checkbox':
                // For checkboxes, count true/false
                $trueCount = count(array_filter($values, function($v) { return $v === true || $v === 'true' || $v === 1; }));
                $falseCount = count($values) - $trueCount;
                $result['true_count'] = $trueCount;
                $result['false_count'] = $falseCount;
                $result['true_percentage'] = count($values) > 0 ? ($trueCount / count($values)) * 100 : 0;
                break;

            case 'date':
                // Date range
                $dates = array_filter($values, function($v) {
                    return $v instanceof \DateTime || (is_string($v) && strtotime($v));
                });
                if (!empty($dates)) {
                    $dateObjects = array_map(function($v) {
                        return $v instanceof \DateTime ? $v : new \DateTime($v);
                    }, $dates);
                    $result['earliest'] = min($dateObjects)->format('Y-m-d');
                    $result['latest'] = max($dateObjects)->format('Y-m-d');
                }
                break;

            case 'email':
                // Validate emails and count domains
                $validEmails = array_filter($values, function($v) {
                    return filter_var($v, FILTER_VALIDATE_EMAIL);
                });
                $domains = [];
                foreach ($validEmails as $email) {
                    $domain = substr(strrchr($email, "@"), 1);
                    $domains[] = $domain;
                }
                $domainCounts = array_count_values($domains);
                arsort($domainCounts);
                $result['valid_emails'] = count($validEmails);
                $result['domain_distribution'] = array_slice($domainCounts, 0, 5, true);
                break;

            default:
                // For unknown types, just count occurrences
                $valueCounts = array_count_values($values);
                arsort($valueCounts);
                $result['value_counts'] = array_slice($valueCounts, 0, 10, true);
                break;
        }

        return $result;
    }
}
