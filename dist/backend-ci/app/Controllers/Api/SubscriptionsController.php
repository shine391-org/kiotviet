<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Subscriptions\SubscriptionService;
use CodeIgniter\API\ResponseTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Subscriptions API.
 *
 * @agent-controller: Subscriptions
 * @agent-pattern: Thin controller - routing only
 */
class SubscriptionsController extends BaseController
{
    use ResponseTrait;

    protected SubscriptionService $service;

    public function __construct()
    {
        $this->service = service('subscriptionService');
    }

    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->show((int) $id)));
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($data)));
    }

    public function update($id)
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $data)));
    }

    public function pause($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->pause((int) $id)));
    }

    public function resume($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->resume((int) $id)));
    }

    public function cancel($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->cancel((int) $id)));
    }

    public function run()
    {
        return $this->wrap(fn () => $this->respond($this->service->runDue()));
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
