<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\PriceLists\PriceListService;
use CodeIgniter\API\ResponseTrait;

/** Price lists API. @agent-controller: Price lists @agent-pattern: Thin controller - routing only */
class PriceListsController extends BaseController
{
    use ResponseTrait;

    protected PriceListService $service;
    public function __construct() { $this->service = service('priceListService'); }

    /** List price lists. @agent-use: GET /api/price-lists @agent-pattern: Standard list pattern */
    public function index() { return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet()))); }

    /** Get detail. @agent-use: GET /api/price-lists/{id} @agent-pattern: Thin get */
    public function show($id) { return $this->wrap(fn () => $this->respond($this->service->get((int) $id))); }

    /** Create price list. @agent-use: POST /api/price-lists @agent-pattern: Thin create */
    public function create()
    {
        return $this->wrap(fn () => $this->respondCreated($this->service->create($this->safeInput())));
    }

    /** Update price list. @agent-use: PUT /api/price-lists/{id} @agent-pattern: Thin update */
    public function update($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $this->safeInput())));
    }

    /** Delete price list. @agent-use: DELETE /api/price-lists/{id} @agent-pattern: Thin delete */
    public function delete($id) { return $this->wrap(fn () => $this->respond($this->service->delete((int) $id))); }

    /** List items. @agent-use: GET /api/price-lists/{id}/items @agent-pattern: Delegate items fetch */
    public function items($id) { return $this->wrap(fn () => $this->respond($this->service->items((int) $id))); }

    /** Upsert items (Replace All). @agent-use: POST /api/price-lists/{id}/items @agent-pattern: Bulk replace */
    public function saveItems($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $items = $payload['items'] ?? $payload;
        return $this->wrap(fn () => $this->respond($this->service->upsertItems((int) $id, (array) $items)));
    }

    /** Add items (Append). @agent-use: POST /api/price-lists/{id}/add-items */
    public function addItems($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $items = $payload['items'] ?? $payload;
        return $this->wrap(fn () => $this->respond($this->service->addItems((int) $id, (array) $items)));
    }

    /** Apply formula batch. @agent-use: POST /api/price-lists/{id}/apply-formula */
    public function applyFormula($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->applyFormula((int) $id, $this->safeInput())));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }

    private function safeInput(): array
    {
        try { $json = $this->request->getJSON(true); if (is_array($json)) { return $json; } } catch (\Throwable $e) {}
        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
