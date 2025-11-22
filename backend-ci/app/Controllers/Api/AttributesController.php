<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Attributes\AttributeService;
use CodeIgniter\API\ResponseTrait;

/** Attributes API. @agent-controller: Attributes @agent-pattern: Thin controller - routing only */
class AttributesController extends BaseController
{
    use ResponseTrait;

    protected AttributeService $service;
    public function __construct() { $this->service = service('attributeService'); }

    /** List attributes. @agent-use: GET /api/attributes @agent-pattern: Standard list */
    public function index() { return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet()))); }

    /** Show attribute. @agent-use: GET /api/attributes/{id} @agent-pattern: Get by id */
    public function show($id) { return $this->wrap(fn () => $this->respond($this->service->show((int) $id))); }

    /** Create attribute. @agent-use: POST /api/attributes @agent-pattern: Thin create */
    public function create() { $data = $this->request->getJSON(true) ?? []; return $this->wrap(fn () => $this->respondCreated($this->service->create($data))); }

    /** Update attribute. @agent-use: PUT /api/attributes/{id} @agent-pattern: Thin update */
    public function update($id) { $data = $this->request->getJSON(true) ?? []; return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $data))); }

    /** Delete attribute. @agent-use: DELETE /api/attributes/{id} @agent-pattern: Thin delete */
    public function delete($id) { return $this->wrap(fn () => $this->respond($this->service->delete((int) $id))); }

    /** List options. @agent-use: GET /api/attributes/{id}/options @agent-pattern: Nested list */
    public function options($attributeId) { return $this->wrap(fn () => $this->respond($this->service->options((int) $attributeId))); }

    /** Create option. @agent-use: POST /api/attributes/{id}/options @agent-pattern: Thin create */
    public function createOption($attributeId) { $data = $this->request->getJSON(true) ?? []; return $this->wrap(fn () => $this->respondCreated($this->service->createOption((int) $attributeId, $data))); }

    /** Update option. @agent-use: PUT /api/attributes/options/{id} @agent-pattern: Thin update */
    public function updateOption($optionId) { $data = $this->request->getJSON(true) ?? []; return $this->wrap(fn () => $this->respond($this->service->updateOption((int) $optionId, $data))); }

    /** Delete option. @agent-use: DELETE /api/attributes/options/{id} @agent-pattern: Thin delete */
    public function deleteOption($optionId) { return $this->wrap(fn () => $this->respond($this->service->deleteOption((int) $optionId))); }

    /** Create attribute value. @agent-use: POST /api/attribute-values @agent-pattern: Thin create */
    public function createValue() { $data = $this->request->getJSON(true) ?? []; return $this->wrap(fn () => $this->respondCreated($this->service->createValue($data))); }

    /** Delete attribute value. @agent-use: DELETE /api/attribute-values/{id} @agent-pattern: Thin delete */
    public function deleteValue($id) { return $this->wrap(fn () => $this->respond($this->service->deleteValue((int) $id))); }

    /** Products by option. @agent-use: GET /api/attributes/options/{optionId}/products @agent-pattern: Delegate read */
    public function productsByOption($optionId) { return $this->wrap(fn () => $this->respond($this->service->productsByOption((int) $optionId))); }

    /** Shared try/catch wrapper. @agent-use: Controller error handling @agent-pattern: Wrap service calls */
    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
