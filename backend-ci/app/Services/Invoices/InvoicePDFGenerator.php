<?php

namespace App\Services\Invoices;

use App\Transformers\InvoiceTransformer;

/**
 * Minimal PDF generator (stores simple HTML snapshot).
 *
 * @agent-service: Invoice PDF
 * @agent-pattern: On-demand generation
 * @agent-reusable: MEDIUM
 */
class InvoicePDFGenerator
{
    protected InvoiceTransformer $transformer;

    public function __construct(?InvoiceTransformer $transformer = null)
    {
        $this->transformer = $transformer ?? new InvoiceTransformer();
    }

    /**
     * Generate and persist a pseudo-PDF (HTML) file.
     * In real env replace with dompdf; here we save HTML for tests.
     */
    public function generate(array $invoice): string
    {
        $data = $this->transformer->transform($invoice);
        $html = $this->renderHtml($data);
        $year = substr($data['issue_date'] ?? date('Y'), 0, 4);
        $dir = WRITEPATH . 'invoices/' . $year;
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $filename = sprintf('%s/%s.html', $dir, $data['invoice_number']);
        file_put_contents($filename, $html);
        return str_replace(WRITEPATH, '/writable/', $filename);
    }

    private function renderHtml(array $invoice): string
    {
        $orders = $invoice['orders'] ?? [];
        $rows = '';
        foreach ($orders as $o) {
            $rows .= sprintf(
                '<tr><td>%s</td><td>%s</td></tr>',
                htmlspecialchars((string) ($o['order_id'] ?? '')),
                htmlspecialchars(number_format((float) ($o['total'] ?? 0), 2))
            );
        }

        return sprintf(
            '<html><body><h1>Invoice %s</h1><p>Subtotal: %0.2f</p><p>VAT: %0.2f</p><p>Total: %0.2f</p><table>%s</table></body></html>',
            htmlspecialchars((string) ($invoice['invoice_number'] ?? 'N/A')),
            (float) ($invoice['subtotal'] ?? 0),
            (float) ($invoice['vat_amount'] ?? 0),
            (float) ($invoice['total'] ?? 0),
            $rows
        );
    }
}
