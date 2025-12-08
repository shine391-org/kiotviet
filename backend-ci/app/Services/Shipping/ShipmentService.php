<?php

namespace App\Services\Shipping;

use App\Repositories\Shipping\ShipmentRepository;
use App\Transformers\ShipmentTransformer;

/**
 * Shipment service - business logic for shipments (invoice delivery view).
 *
 * @agent-service: Shipments
 * @agent-pattern: Service layer
 */
class ShipmentService
{
    protected ShipmentRepository $repo;
    protected ShipmentTransformer $transformer;

    public function __construct(?ShipmentRepository $repo = null, ?ShipmentTransformer $transformer = null)
    {
        $this->repo = $repo ?? new ShipmentRepository();
        $this->transformer = $transformer ?? new ShipmentTransformer();
    }

    /** List shipments with filters and pagination. */
    public function list(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 15)));
        $filters['page'] = $page;
        $filters['limit'] = $limit;

        $items = $this->repo->findAll($filters);
        $total = $this->repo->count($filters);
        $summary = $this->repo->summary($filters);

        return [
            'success' => true,
            'data' => $this->transformer->transformList($items),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $limit) : 1,
            ],
            'summary' => $summary,
        ];
    }

    /** Get shipment detail by invoice ID. */
    public function get(int $id): array
    {
        $row = $this->repo->findById($id);
        if (! $row) {
            throw new \RuntimeException('Không tìm thấy vận đơn');
        }
        return [
            'success' => true,
            'data' => $this->transformer->transform($row),
        ];
    }
}
