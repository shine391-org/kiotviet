<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Orders\OrderTemplateService;
use CodeIgniter\API\ResponseTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Order templates API.
 *
 * @agent-controller: Order templates
 * @agent-pattern: Thin controller - routing only
 */
class OrderTemplatesController extends BaseController
{
    use ResponseTrait;

    protected OrderTemplateService $service;

    public function __construct()
    {
        $this->service = service('orderTemplateService');
    }

    /** List templates. @agent-use: GET /api/order-templates */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Show template. @agent-use: GET /api/order-templates/{id} */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->show((int) $id)));
    }

    /** Create template. @agent-use: POST /api/order-templates */
    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createTemplate($data)));
    }

    /** Update template. @agent-use: PUT /api/order-templates/{id} */
    public function update($id)
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->updateTemplate((int) $id, $data)));
    }

    /** Delete template. @agent-use: DELETE /api/order-templates/{id} */
    public function delete($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->deleteTemplate((int) $id)));
    }

    /** Apply template to create order. @agent-use: POST /api/order-templates/{id}/apply */
    public function apply($id)
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->applyTemplate((int) $id, $data)));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
