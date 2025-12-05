<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();
        
        $this->db->table('returns')->emptyTable();
        $this->db->table('invoice_orders')->emptyTable();
        $this->db->table('invoices')->emptyTable(); // Correct Table
        $this->db->table('delivery_notes')->emptyTable();
        $this->db->table('order_items')->emptyTable();
        $this->db->table('orders')->emptyTable(); 
        
        $this->db->enableForeignKeyChecks();

        $now = date('Y-m-d H:i:s');
        
        $customers = $this->db->table('customers')->get()->getResultArray();
        $products = $this->db->table('products')->get()->getResultArray();
        
        echo "DEBUG: Found " . count($customers) . " customers and " . count($products) . " products.\n";
        
        if (empty($customers) || empty($products)) {
             echo "⚠️ Dependencies missing. Skipping Orders.\n";
             return; 
        }

        $orders = [];
        $orderItems = [];
        $invoices = [];
        $invoiceOrders = [];
        $deliveries = [];
        $returns = [];
        
        $states = ['draft', 'processing', 'completed', 'returned'];
        
        for ($i = 1; $i <= 20; $i++) {
            $status = $states[$i % 4]; 
            
            $cust = $customers[array_rand($customers)];
            $code = 'DH' . date('ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $orderId = 1000 + $i;
            
            $numItems = rand(1, 4);
            $orderTotal = 0;
            
            for ($k = 0; $k < $numItems; $k++) {
                $prod = $products[array_rand($products)];
                $qty = rand(1, 3);
                $price = $prod['selling_price'];
                $subtotal = $qty * $price;
                
                $orderItems[] = [
                    'order_id' => $orderId,
                    'product_id' => $prod['id'],
                    'variant_id' => null, 
                    'quantity' => $qty,
                    'base_price' => $price, 
                    'final_price' => $price, 
                    'created_at' => $now
                ];
                $orderTotal += $subtotal;
            }

            // Create Order
            $orders[] = [
                'id' => $orderId,
                'code' => $code,
                'order_number' => $code,
                'customer_id' => $cust['id'],
                'order_date' => date('Y-m-d'),
                'total' => $orderTotal,
                'subtotal' => $orderTotal,
                'tax_total' => 0,
                'status' => $status == 'returned' ? 'completed' : $status,
                'payment_status' => ($status == 'completed' || $status == 'returned') ? 'paid' : 'unpaid',
                'shipping_address' => $cust['address'] ?? 'Hà Nội',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // 1. Delivery Note
            if (in_array($status, ['processing', 'completed', 'returned'])) {
                $deliveries[] = [
                    'delivery_number' => 'VNM' . $code,
                    'order_id' => $orderId,
                    'customer_id' => $cust['id'],
                    'status' => ($status == 'processing') ? 'picking' : 'delivered',
                    'shipping_address' => $cust['address'] ?? 'Hà Nội',
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }

            // 2. Invoice (Use INVOICES table)
            if (in_array($status, ['completed', 'returned'])) {
                $invId = 5000 + $i;
                $invoices[] = [
                    'id' => $invId,
                    'invoice_number' => 'HD' . $code,
                    'customer_id' => $cust['id'],
                    'issue_date' => date('Y-m-d'),
                    'total' => $orderTotal,
                    'net_total' => $orderTotal,
                    'customer_payable' => $orderTotal,
                    'customer_paid' => $orderTotal,
                    'payment_status' => 'paid',
                    'invoice_status' => 'complete', // Guessing status
                    'created_at' => $now,
                    'updated_at' => $now
                ];
                $invoiceOrders[] = [
                    'invoice_id' => $invId,
                    'order_id' => $orderId,
                    'created_at' => $now
                ];
            }

            // 3. Returns
            if ($status == 'returned') {
                $returns[] = [
                    'return_number' => 'TH' . $code,
                    'order_id' => $orderId,
                    'customer_id' => $cust['id'],
                    'return_amount' => $orderTotal,
                    'refund_amount' => $orderTotal,
                    'reason' => 'Defective',
                    'status' => 'approved',
                    'completed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
        }

        $this->db->table('orders')->insertBatch($orders);
        $this->db->table('order_items')->insertBatch($orderItems);
        
        if (!empty($deliveries)) {
            $this->db->table('delivery_notes')->insertBatch($deliveries);
        }
        if (!empty($invoices)) {
            $this->db->table('invoices')->insertBatch($invoices);
            $this->db->table('invoice_orders')->insertBatch($invoiceOrders);
        }
        if (!empty($returns)) {
            $this->db->table('returns')->insertBatch($returns);
        }

        echo "✅ Seeded 20 Orders (Draft, Processing, Completed, Returned).\n";
    }
}
