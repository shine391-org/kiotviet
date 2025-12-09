<?php

namespace Tests\Services;

use App\Services\SalesChannels\SalesChannelService;
use App\Repositories\SalesChannels\SalesChannelRepository;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;
use InvalidArgumentException;

class SalesChannelServiceTest extends CIUnitTestCase
{
    private SalesChannelService $service;
    private SalesChannelFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new SalesChannelFakeRepo();
        $this->service = new SalesChannelService($this->repo);
    }

    public function testListReturnsTransformedData(): void
    {
        $result = $this->service->list([]);
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertCount(3, $result['data']);
    }

    public function testListTransformsDataCorrectly(): void
    {
        $result = $this->service->list([]);
        
        $channel = $result['data'][0];
        $this->assertArrayHasKey('id', $channel);
        $this->assertArrayHasKey('code', $channel);
        $this->assertArrayHasKey('name', $channel);
        $this->assertArrayHasKey('is_active', $channel);
        $this->assertArrayHasKey('is_default', $channel);
        $this->assertArrayHasKey('sort_order', $channel);
        $this->assertArrayHasKey('settings', $channel);
        $this->assertIsBool($channel['is_active']);
        $this->assertIsBool($channel['is_default']);
        $this->assertIsInt($channel['sort_order']);
    }

    public function testGetReturnsChannel(): void
    {
        $result = $this->service->get(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('STORE', $result['data']['code']);
        $this->assertEquals('In-store', $result['data']['name']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Sales channel not found');
        
        $this->service->get(999);
    }

    public function testCreateSuccess(): void
    {
        $data = ['code' => 'NEW', 'name' => 'New Channel'];
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Sales channel created', $result['message']);
        $this->assertEquals('NEW', $result['data']['code']);
    }

    public function testCreateThrowsWhenCodeEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Code and name are required');
        
        $this->service->create(['code' => '', 'name' => 'Test']);
    }

    public function testCreateThrowsWhenNameEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Code and name are required');
        
        $this->service->create(['code' => 'TEST', 'name' => '']);
    }

    public function testCreateThrowsOnDuplicateCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sales channel code already exists');
        
        $this->service->create(['code' => 'STORE', 'name' => 'Duplicate']);
    }

    public function testUpdateSuccess(): void
    {
        $result = $this->service->update(1, ['name' => 'Updated Store']);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Sales channel updated', $result['message']);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Sales channel not found');
        
        $this->service->update(999, ['name' => 'Test']);
    }

    public function testDeleteSuccess(): void
    {
        $result = $this->service->delete(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Sales channel deleted', $result['message']);
    }

    public function testDeleteThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Sales channel not found');
        
        $this->service->delete(999);
    }

    public function testCreateWithSettings(): void
    {
        $data = [
            'code' => 'CUSTOM',
            'name' => 'Custom Channel',
            'settings' => ['key' => 'value'],
        ];
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']['settings']);
    }

    public function testListTransformsSettingsFromJson(): void
    {
        $result = $this->service->list([]);
        
        $onlineChannel = array_filter($result['data'], fn($c) => $c['code'] === 'ONLINE');
        $online = reset($onlineChannel);
        $this->assertIsArray($online['settings']);
        $this->assertEquals('value', $online['settings']['key']);
    }
}

class SalesChannelFakeRepo extends SalesChannelRepository
{
    private array $channels = [
        1 => ['id' => 1, 'code' => 'STORE', 'name' => 'In-store', 'description' => 'Physical store', 'icon' => 'store', 'color' => '#000', 'is_active' => 1, 'is_default' => 1, 'sort_order' => 1, 'settings' => null],
        2 => ['id' => 2, 'code' => 'ONLINE', 'name' => 'Online', 'description' => 'E-commerce', 'icon' => 'web', 'color' => '#00f', 'is_active' => 1, 'is_default' => 0, 'sort_order' => 2, 'settings' => '{"key":"value"}'],
        3 => ['id' => 3, 'code' => 'POS', 'name' => 'Point of Sale', 'description' => 'POS system', 'icon' => 'pos', 'color' => '#0f0', 'is_active' => 1, 'is_default' => 0, 'sort_order' => 3, 'settings' => null],
    ];

    public function __construct() {}

    public function findAll(array $filters = []): array
    {
        return array_values($this->channels);
    }

    public function findById(int $id): ?array
    {
        return $this->channels[$id] ?? null;
    }

    public function findByCode(string $code): ?array
    {
        foreach ($this->channels as $channel) {
            if ($channel['code'] === $code) {
                return $channel;
            }
        }
        return null;
    }

    public function create(array $data): array
    {
        $id = max(array_keys($this->channels)) + 1;
        $this->channels[$id] = array_merge([
            'id' => $id,
            'is_active' => 1,
            'is_default' => 0,
            'sort_order' => 0,
        ], $data);
        return $this->channels[$id];
    }

    public function update(int $id, array $data): array
    {
        if (isset($this->channels[$id])) {
            $this->channels[$id] = array_merge($this->channels[$id], $data);
        }
        return $this->channels[$id] ?? [];
    }

    public function delete(int $id): bool
    {
        unset($this->channels[$id]);
        return true;
    }
}
