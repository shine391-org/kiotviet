<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Orders\OrderSubscriptionService;
use CodeIgniter\API\ResponseTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Order subscriptions API.
 *
 * @agent-controller: Order subscriptions
 * @agent-pattern: Thin controller - routing only
 */
class OrderSubscriptionsController extends BaseController
{
    use ResponseTrait;

    protected OrderSubscriptionService $service;

    public function __construct()
    {
        $this->service = service('orderSubscriptionService');
    }

    /** List subscriptions. @agent-use: GET /api/order-subscriptions */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Create subscription. @agent-use: POST /api/order-subscriptions */
    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createSubscription($data)));
    }

    /** Update subscription. @agent-use: PUT /api/order-subscriptions/{id} */
    public function update($id)
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->updateSubscription((int) $id, $data)));
    }

    /** Run due subscriptions. @agent-use: POST /api/order-subscriptions/run */
    public function run()
    {
        return $this->wrap(fn () => $this->respond($this->service->runDueSubscriptions()));
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
