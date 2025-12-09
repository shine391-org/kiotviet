<?php

namespace Tests\Validators;

use App\Validators\CashTransactionValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

class CashTransactionValidatorTest extends CIUnitTestCase
{
    private CashTransactionValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new CashTransactionValidator();
    }

    public function testValidateListFiltersDefaults(): void
    {
        $result = $this->validator->validateListFilters([]);
        
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(20, $result['limit']);
    }

    public function testValidateListFiltersWithCustomValues(): void
    {
        $result = $this->validator->validateListFilters([
            'page' => 3,
            'limit' => 50,
            'type' => 'RECEIPT',
            'status' => 'approved',
        ]);
        
        $this->assertEquals(3, $result['page']);
        $this->assertEquals(50, $result['limit']);
        $this->assertEquals('RECEIPT', $result['type']);
        $this->assertEquals('approved', $result['status']);
    }

    public function testValidateListFiltersWithDateRange(): void
    {
        $result = $this->validator->validateListFilters([
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
        ]);
        
        $this->assertEquals('2024-01-01', $result['date_from']);
        $this->assertEquals('2024-12-31', $result['date_to']);
    }

    public function testValidateListFiltersWithPaymentMethod(): void
    {
        $result = $this->validator->validateListFilters(['payment_method' => 'cash']);
        
        $this->assertEquals('cash', $result['payment_method']);
    }

    public function testValidateListFiltersWithBranchId(): void
    {
        $result = $this->validator->validateListFilters(['branch_id' => 1]);
        
        $this->assertEquals(1, $result['branch_id']);
    }

    public function testValidateListFiltersWithPayerInfo(): void
    {
        $result = $this->validator->validateListFilters([
            'payer_name' => 'John',
            'payer_phone' => '0123456789',
            'payer_code' => 'CUST001',
        ]);
        
        $this->assertEquals('John', $result['payer_name']);
        $this->assertEquals('0123456789', $result['payer_phone']);
        $this->assertEquals('CUST001', $result['payer_code']);
    }

    public function testValidateListFiltersWithBankInfo(): void
    {
        $result = $this->validator->validateListFilters([
            'bank_account' => '123456789',
            'transfer_note' => 'Payment for order',
        ]);
        
        $this->assertEquals('123456789', $result['bank_account']);
        $this->assertEquals('Payment for order', $result['transfer_note']);
    }

    public function testValidateListFiltersWithStaffName(): void
    {
        $result = $this->validator->validateListFilters(['staff_name' => 'Admin']);
        
        $this->assertEquals('Admin', $result['staff_name']);
    }

    public function testValidateListFiltersWithReferenceType(): void
    {
        $result = $this->validator->validateListFilters(['reference_type' => 'ORDER']);
        
        $this->assertEquals('ORDER', $result['reference_type']);
    }

    public function testValidateReceiptSuccess(): void
    {
        $data = [
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => date('Y-m-d'),
        ];
        
        $result = $this->validator->validateReceipt($data);
        
        $this->assertEquals('RECEIPT', $result['type']);
        $this->assertEquals(100000, $result['amount']);
    }

    public function testValidatePaymentSuccess(): void
    {
        $data = [
            'amount' => 50000,
            'category' => 'purchase',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => date('Y-m-d'),
        ];
        
        $result = $this->validator->validatePayment($data);
        
        $this->assertEquals('PAYMENT', $result['type']);
        $this->assertEquals(50000, $result['amount']);
    }

    public function testValidateReceiptWithOptionalFields(): void
    {
        $data = [
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => date('Y-m-d'),
            'description' => 'Test description',
            'note' => 'Test note',
            'payment_method' => 'cash',
            'payer_name' => 'John Doe',
            'payer_phone' => '0123456789',
        ];
        
        $result = $this->validator->validateReceipt($data);
        
        $this->assertEquals('Test description', $result['description']);
        $this->assertEquals('Test note', $result['note']);
        $this->assertEquals('cash', $result['payment_method']);
        $this->assertEquals('John Doe', $result['payer_name']);
    }

    public function testValidatePaymentWithReferenceInfo(): void
    {
        $data = [
            'amount' => 50000,
            'category' => 'purchase',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => date('Y-m-d'),
            'reference_type' => 'PURCHASE_ORDER',
            'reference_id' => 123,
            'reference_code' => 'PO-001',
        ];
        
        $result = $this->validator->validatePayment($data);
        
        $this->assertEquals('PURCHASE_ORDER', $result['reference_type']);
        $this->assertEquals(123, $result['reference_id']);
        $this->assertEquals('PO-001', $result['reference_code']);
    }

    public function testValidateThrowsWhenAmountMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateReceipt([
            'category' => 'sales',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => date('Y-m-d'),
        ]);
    }

    public function testValidateThrowsWhenAmountZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateReceipt([
            'amount' => 0,
            'category' => 'sales',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => date('Y-m-d'),
        ]);
    }

    public function testValidateThrowsWhenCategoryMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateReceipt([
            'amount' => 100000,
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => date('Y-m-d'),
        ]);
    }

    public function testValidateThrowsWhenBranchIdMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateReceipt([
            'amount' => 100000,
            'category' => 'sales',
            'created_by' => 1,
            'transaction_date' => date('Y-m-d'),
        ]);
    }

    public function testValidateThrowsWhenCreatedByMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateReceipt([
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => 1,
            'transaction_date' => date('Y-m-d'),
        ]);
    }

    public function testValidateThrowsWhenTransactionDateMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateReceipt([
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => 1,
            'created_by' => 1,
        ]);
    }

    public function testValidateThrowsWhenTransactionDateInFuture(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction date cannot be in the future');
        
        $this->validator->validateReceipt([
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => date('Y-m-d', strtotime('+1 day')),
        ]);
    }

    public function testValidateReceiptThrowsOnInvalidCategory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid category for RECEIPT');
        
        $this->validator->validateReceipt([
            'amount' => 100000,
            'category' => 'purchase',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => date('Y-m-d'),
        ]);
    }

    public function testValidatePaymentThrowsOnInvalidCategory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid category for PAYMENT');
        
        $this->validator->validatePayment([
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => date('Y-m-d'),
        ]);
    }

    public function testValidateWithBankTransferPayment(): void
    {
        $data = [
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => date('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'bank_account' => '123456789',
            'transfer_note' => 'Transfer from customer',
        ];
        
        $result = $this->validator->validateReceipt($data);
        
        $this->assertEquals('bank_transfer', $result['payment_method']);
        $this->assertEquals('123456789', $result['bank_account']);
    }

    public function testValidateTrimsStringFields(): void
    {
        $data = [
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => date('Y-m-d'),
            'description' => '  test description  ',
            'note' => '  test note  ',
        ];
        
        $result = $this->validator->validateReceipt($data);
        
        $this->assertEquals('test description', $result['description']);
        $this->assertEquals('test note', $result['note']);
    }
}
