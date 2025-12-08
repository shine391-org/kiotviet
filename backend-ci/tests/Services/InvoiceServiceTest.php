<?php

namespace Tests\Services;

use App\Services\Invoices\InvoiceService;
use App\Services\Invoices\VATCalculator;
use App\Services\Invoices\InvoicePDFGenerator;
use App\Repositories\Invoices\InvoiceRepository;
use App\Transformers\InvoiceTransformer;
use App\Validators\InvoiceValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-test: InvoiceService (stubbed)
 * @agent-pattern: Service orchestration without DB
 */
class InvoiceServiceTest extends CIUnitTestCase
{
    private InvoiceService $service;
    private InvoiceServiceFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new InvoiceServiceFakeRepo();
        $validator = new InvoiceValidator();
        $transformer = new InvoiceTransformer();
        $vatCalc = new VATCalculator();
        $pdf = new class extends InvoicePDFGenerator {
            public function generate(array $invoice): string
            {
                return '/tmp/invoice_' . $invoice['id'] . '.pdf';
            }
        };
        $this->service = new InvoiceService($this->repo, $validator, $transformer, $vatCalc, $pdf);
    }

    public function testListReturnsInvoicesWithPagination(): void
    {
        $result = $this->service->list(['page' => 1, 'limit' => 10]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('totals', $result);
        $this->assertArrayHasKey('pageTotals', $result);
        $this->assertCount(2, $result['data']);
    }

    public function testListWithDateFilters(): void
    {
        $result = $this->service->list([
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31',
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']);
    }

    public function testListWithStatusFilter(): void
    {
        $result = $this->service->list(['status' => 'completed']);

        $this->assertTrue($result['success']);
    }

    public function testGetReturnsInvoice(): void
    {
        $result = $this->service->get(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['data']['id']);
        $this->assertSame('INV-2024-001', $result['data']['invoice_number']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invoice not found');
        $this->service->get(999);
    }

    public function testUpdateReturnsUpdatedInvoice(): void
    {
        $result = $this->service->update(1, ['notes' => 'Updated notes']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invoice not found');
        $this->service->update(999, ['notes' => 'Test']);
    }

    public function testCancelReturnsSuccess(): void
    {
        $result = $this->service->cancel(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testCancelThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invoice not found');
        $this->service->cancel(999);
    }

    public function testCancelThrowsWhenAlreadyCancelled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invoice already cancelled');
        $this->service->cancel(2); // Invoice 2 is already cancelled
    }

    public function testDeleteReturnsSuccess(): void
    {
        $result = $this->service->delete(1);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('thành công', $result['message']);
    }

    public function testDeleteThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invoice not found');
        $this->service->delete(999);
    }

    public function testGeneratePdfReturnsPath(): void
    {
        $result = $this->service->generatePdf(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertStringContainsString('.pdf', $result['data']['pdf_path']);
    }

    public function testGeneratePdfThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invoice not found');
        $this->service->generatePdf(999);
    }
}

class InvoiceServiceFakeRepo extends InvoiceRepository
{
    private array $fakeData = [];

    public function __construct()
    {
        $this->fakeData = [
            1 => [
                'id' => 1,
                'invoice_number' => 'INV-2024-001',
                'customer_id' => 1,
                'branch_id' => 1,
                'issue_date' => '2024-01-15',
                'due_date' => '2024-02-15',
                'subtotal' => 1000000,
                'vat_rate' => 0.1,
                'vat_amount' => 100000,
                'total' => 1100000,
                'invoice_status' => 'completed',
                'customer_payable' => 1100000,
                'customer_paid' => 0,
                'cod_amount' => 1100000,
                'shipping_fee' => 0,
                'created_at' => '2024-01-15 10:00:00',
            ],
            2 => [
                'id' => 2,
                'invoice_number' => 'INV-2024-002',
                'customer_id' => 2,
                'branch_id' => 1,
                'issue_date' => '2024-02-20',
                'due_date' => '2024-03-20',
                'subtotal' => 2000000,
                'vat_rate' => 0.1,
                'vat_amount' => 200000,
                'total' => 2200000,
                'invoice_status' => 'cancelled',
                'customer_payable' => 2200000,
                'customer_paid' => 2200000,
                'cod_amount' => 0,
                'shipping_fee' => 50000,
                'created_at' => '2024-02-20 14:00:00',
            ],
        ];
    }

    public function findAll(array $filters = []): array
    {
        return array_values($this->fakeData);
    }

    public function count(array $filters = []): int
    {
        return count($this->fakeData);
    }

    public function totals(array $filters = []): array
    {
        return [
            'total_amount' => 3300000,
            'total_paid' => 2200000,
            'total_unpaid' => 1100000,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fakeData[$id] ?? null;
    }

    public function create(array $data, array $orderIds = []): array
    {
        $id = max(array_keys($this->fakeData)) + 1;
        $data['id'] = $id;
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->fakeData[$id] = $data;
        return $data;
    }

    public function update(int $id, array $data): bool
    {
        if (! isset($this->fakeData[$id])) {
            return false;
        }
        $this->fakeData[$id] = array_merge($this->fakeData[$id], $data);
        return true;
    }

    public function softDelete(int $id): bool
    {
        if (! isset($this->fakeData[$id])) {
            return false;
        }
        $this->fakeData[$id]['deleted_at'] = date('Y-m-d H:i:s');
        return true;
    }

    public function updatePdfPath(int $invoiceId, string $path): void
    {
        if (isset($this->fakeData[$invoiceId])) {
            $this->fakeData[$invoiceId]['pdf_path'] = $path;
        }
    }

    public function nextNumber(int $branchId, ?string $issueDate = null): string
    {
        $date = $issueDate ?? date('Y-m-d');
        return 'INV-' . date('Y', strtotime($date)) . '-' . sprintf('%03d', count($this->fakeData) + 1);
    }

    public function ordersByIds(array $ids): array
    {
        return [];
    }

    public function findExistingInvoiceForOrders(array $orderIds): ?array
    {
        return null;
    }
}
