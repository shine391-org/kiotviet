<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class DebugFKController extends Controller
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        $totalInvoices = $db->table('invoices')->countAll();
        $nullCustomer = $db->table('invoices')->where('customer_id', null)->countAllResults();
        $zeroCustomer = $db->table('invoices')->where('customer_id', 0)->countAllResults();
        $okCustomer = $totalInvoices - $nullCustomer - $zeroCustomer;
        
        $sampleNull = $db->table('invoices')->select('id, invoice_number, customer_id')->where('customer_id', null)->limit(5)->get()->getResultArray();
        $sampleOk = $db->table('invoices')->select('id, invoice_number, customer_id')->where('customer_id >', 0)->limit(5)->get()->getResultArray();
        
        $totalCustomers = $db->table('customers')->countAll();
        $sampleCustomers = $db->table('customers')->select('id, code, name')->limit(5)->get()->getResultArray();

        return $this->response->setJSON([
            'invoices_total' => $totalInvoices,
            'customer_id_null' => $nullCustomer,
            'customer_id_zero' => $zeroCustomer,
            'customer_id_ok' => $okCustomer,
            'sample_null_invoices' => $sampleNull,
            'sample_linked_invoices' => $sampleOk,
            'total_customers' => $totalCustomers,
            'sample_customers' => $sampleCustomers
        ]);
    }

    public function fix()
    {
        $migrate = \Config\Services::migrations();
        $seeder = \Config\Database::seeder();
        try {
            $migrate->latest();
            $seeder->call('FixInvoiceCustomerIds');
            return $this->response->setJSON(['status' => 'success', 'message' => 'Migration & Seeder completed']);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }
}
