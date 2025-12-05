<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Inventory\PurchaseSuggestionService;
use App\Services\Inventory\ReorderPlanningService;
use CodeIgniter\API\ResponseTrait;

/**
 * Reorder planning API controller.
 *
 * @agent-controller: Reorder planning
 * @agent-pattern: Thin controller - routing only
 */
class ReorderPlanningController extends BaseController
{
    use ResponseTrait;

    protected ReorderPlanningService $planning;
    protected PurchaseSuggestionService $suggestions;

    public function __construct()
    {
        $this->planning = service('reorderPlanningService');
        $this->suggestions = service('purchaseSuggestionService');
    }

    /** List reorder levels. @agent-use: GET /api/reorder-levels @agent-pattern: Standard list */
    public function reorderLevels()
    {
        return $this->wrap(fn () => $this->respond($this->planning->listLevels($this->request->getGet())));
    }

    /** Create reorder level. @agent-use: POST /api/reorder-levels @agent-pattern: Thin create */
    public function createLevel()
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->planning->createLevel($data)));
    }

    /** Update reorder level. @agent-use: PUT /api/reorder-levels/{id} @agent-pattern: Thin update */
    public function updateLevel($id)
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->planning->updateLevel((int) $id, $data)));
    }

    /** Delete reorder level. @agent-use: DELETE /api/reorder-levels/{id} @agent-pattern: Soft delete */
    public function deleteLevel($id)
    {
        return $this->wrap(fn () => $this->respond($this->planning->deleteLevel((int) $id)));
    }

    /** Generate purchase suggestions. @agent-use: POST /api/purchase-suggestions/generate @agent-pattern: Thin action */
    public function generateSuggestions()
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->planning->generateSuggestions($data)));
    }

    /** List purchase suggestions. @agent-use: GET /api/purchase-suggestions @agent-pattern: Standard list */
    public function listSuggestions()
    {
        return $this->wrap(fn () => $this->respond($this->suggestions->list($this->request->getGet())));
    }

    /** Acknowledge suggestion. @agent-use: POST /api/purchase-suggestions/{id}/ack @agent-pattern: Thin update */
    public function acknowledge($id)
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->suggestions->acknowledge((int) $id, $data)));
    }

    /** Convert suggestion to PO. @agent-use: POST /api/purchase-suggestions/{id}/convert @agent-pattern: Thin action */
    public function convert($id)
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->suggestions->convertToPurchaseOrder((int) $id, $data)));
    }

    /** Shared exception wrapper. */
    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
