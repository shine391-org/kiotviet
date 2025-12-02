<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Campaigns\EmailCampaignService;
use CodeIgniter\API\ResponseTrait;

/** Email campaigns API. @agent-controller: EmailCampaigns @agent-pattern: Thin controller */
class EmailCampaignsController extends BaseController
{
    use ResponseTrait;

    protected EmailCampaignService $service;

    public function __construct()
    {
        $this->service = service('emailCampaignService');
    }

    /** Create/schedule email campaign. @agent-use: POST /api/email-campaigns */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->schedule($payload)));
    }

    /** Update status (pause/resume). @agent-use: POST /api/email-campaigns/{id}/status */
    public function updateStatus($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $status = $payload['status'] ?? 'draft';
        return $this->wrap(fn () => $this->respond($this->service->updateStatus((int) $id, $status)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
