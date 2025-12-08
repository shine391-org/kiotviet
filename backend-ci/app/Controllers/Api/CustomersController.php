<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Customers\CustomerService;
use CodeIgniter\API\ResponseTrait;

/** Customers API. @agent-controller: Customers @agent-pattern: Thin controller */
class CustomersController extends BaseController
{
    use ResponseTrait;

    protected CustomerService $service;

    public function __construct()
    {
        $this->service = service('customerService');
    }

    /** List customers. @agent-use: GET /api/customers @agent-pattern: Standard list pattern */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Show customer detail. @agent-use: GET /api/customers/{id} */
    public function show($id = null)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    /** Create customer. @agent-use: POST /api/customers */
    public function create()
    {
        $data = $this->safeInput();
        return $this->wrap(fn () => $this->respondCreated($this->service->create($data)));
    }

    /** Update customer. @agent-use: PUT /api/customers/{id} */
    public function update($id)
    {
        $data = $this->safeInput();
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $data)));
    }

    /** Delete customer (soft delete). @agent-use: DELETE /api/customers/{id} */
    public function delete($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->delete((int) $id)));
    }

    /** Export customers to CSV. @agent-use: GET /api/customers/export */
    public function export()
    {
        return $this->wrap(function () {
            $rows = $this->service->export($this->request->getGet());
            $csv = $this->toCsv($rows);
            return $this->response
                ->setHeader('Content-Type', 'text/csv')
                ->setHeader('Content-Disposition', 'attachment; filename="customers.csv"')
                ->setBody($csv);
        });
    }

    /** Import customers from uploaded CSV. @agent-use: POST /api/customers/import */
    public function import()
    {
        return $this->wrap(function () {
            $file = $this->request->getFile('file');
            if (! $file || ! $file->isValid()) {
                return $this->failValidationErrors('File upload is required');
            }

            $rows = $this->parseCsv($file->getTempName());
            $result = $this->service->import($rows);

            return $this->respondCreated([
                'success' => true,
                'data' => $result,
                'message' => 'Import completed',
            ]);
        });
    }

    /** Shared try/catch wrapper. */
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

    /** Safely fetch request body supporting JSON or form-data, without parse errors. */
    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                return $json;
            }
        } catch (\Throwable $e) {
            // ignore and fallback
        }

        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }

    /** Convert array to CSV string (UTF-8). */
    private function toCsv(array $rows): string
    {
        if (empty($rows)) {
            return '';
        }
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return $csv ?: '';
    }

    /** Parse CSV file to rows. */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }
        $rows = [];
        $header = null;
        while (($data = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map('trim', $data);
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $data[$i] ?? null;
            }
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }
}
