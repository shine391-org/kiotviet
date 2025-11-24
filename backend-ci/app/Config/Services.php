<?php

namespace Config;

use App\Repositories\Attributes\AttributeRepository;
use App\Repositories\ProductMedia\ProductMediaRepository;
use App\Repositories\Products\ProductRepository;
use App\Repositories\PriceLists\PriceListItemRepository;
use App\Repositories\PriceLists\PriceListRepository;
use App\Repositories\Orders\OrderRepository;
use App\Services\Products\ProductService;
use App\Services\ProductMedia\ProductMediaService;
use App\Services\Attributes\AttributeService;
use App\Services\PriceLists\PriceCalculatorService;
use App\Services\PriceLists\PriceListService;
use App\Services\Orders\OrderService;
use App\Validators\ProductMediaDateValidator;
use App\Validators\ProductMediaSearchValidator;
use App\Validators\ProductMediaValidator;
use App\Validators\ProductValidator;
use App\Validators\PriceListValidator;
use App\Validators\OrderValidator;
use App\Validators\AttributeValidator;
use App\Repositories\Inventory\InventoryRepository;
use App\Services\Inventory\InventoryService;
use App\Validators\InventoryValidator;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function productRepository(bool $getShared = true): ProductRepository
    {
        if ($getShared) {
            return static::getSharedInstance('productRepository');
        }

        return new ProductRepository();
    }

    public static function productValidator(bool $getShared = true): ProductValidator
    {
        if ($getShared) {
            return static::getSharedInstance('productValidator');
        }

        return new ProductValidator();
    }

    public static function productService(bool $getShared = true): ProductService
    {
        if ($getShared) {
            return static::getSharedInstance('productService');
        }

        return new ProductService(
            static::productRepository(false),
            static::productValidator(false),
            static::priceCalculatorService(false)
        );
    }

    public static function productVariantRepository(bool $getShared = true): \App\Repositories\ProductVariants\ProductVariantRepository
    {
        if ($getShared) {
            return static::getSharedInstance('productVariantRepository');
        }

        return new \App\Repositories\ProductVariants\ProductVariantRepository();
    }

    public static function productVariantValidator(bool $getShared = true): \App\Validators\ProductVariantValidator
    {
        if ($getShared) {
            return static::getSharedInstance('productVariantValidator');
        }

        return new \App\Validators\ProductVariantValidator();
    }

    public static function productVariantService(bool $getShared = true): \App\Services\ProductVariants\ProductVariantService
    {
        if ($getShared) {
            return static::getSharedInstance('productVariantService');
        }

        return new \App\Services\ProductVariants\ProductVariantService(
            static::productVariantRepository(false),
            static::productVariantValidator(false),
            static::productRepository(false)
        );
    }

    public static function attributeRepository(bool $getShared = true): AttributeRepository
    {
        if ($getShared) {
            return static::getSharedInstance('attributeRepository');
        }

        return new AttributeRepository();
    }

    public static function attributeValidator(bool $getShared = true): AttributeValidator
    {
        if ($getShared) {
            return static::getSharedInstance('attributeValidator');
        }

        return new AttributeValidator();
    }

    public static function attributeService(bool $getShared = true): AttributeService
    {
        if ($getShared) {
            return static::getSharedInstance('attributeService');
        }

        return new AttributeService(
            static::attributeRepository(false),
            static::attributeValidator(false)
        );
    }

    public static function productMediaRepository(bool $getShared = true): ProductMediaRepository
    {
        if ($getShared) {
            return static::getSharedInstance('productMediaRepository');
        }

        return new ProductMediaRepository();
    }

    public static function productMediaValidator(bool $getShared = true): ProductMediaValidator
    {
        if ($getShared) {
            return static::getSharedInstance('productMediaValidator');
        }

        return new ProductMediaValidator();
    }

    public static function productMediaDateValidator(bool $getShared = true): ProductMediaDateValidator
    {
        if ($getShared) {
            return static::getSharedInstance('productMediaDateValidator');
        }

        return new ProductMediaDateValidator();
    }

    public static function productMediaSearchValidator(bool $getShared = true): ProductMediaSearchValidator
    {
        if ($getShared) {
            return static::getSharedInstance('productMediaSearchValidator');
        }

        return new ProductMediaSearchValidator();
    }

    public static function productMediaService(bool $getShared = true): ProductMediaService
    {
        if ($getShared) {
            return static::getSharedInstance('productMediaService');
        }

        return new ProductMediaService(
            static::productMediaRepository(false),
            static::productMediaValidator(false),
            static::productMediaDateValidator(false),
            static::productMediaSearchValidator(false)
        );
    }

    public static function inventoryRepository(bool $getShared = true): InventoryRepository
    {
        if ($getShared) {
            return static::getSharedInstance('inventoryRepository');
        }

        return new InventoryRepository();
    }

    public static function inventoryValidator(bool $getShared = true): InventoryValidator
    {
        if ($getShared) {
            return static::getSharedInstance('inventoryValidator');
        }

        return new InventoryValidator();
    }

    public static function inventoryService(bool $getShared = true): InventoryService
    {
        if ($getShared) {
            return static::getSharedInstance('inventoryService');
        }

        return new InventoryService(
            static::inventoryRepository(false),
            static::inventoryValidator(false)
        );
    }

    public static function priceListRepository(bool $getShared = true): PriceListRepository
    {
        if ($getShared) { return static::getSharedInstance('priceListRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new PriceListRepository(null, $db);
    }

    public static function priceListItemRepository(bool $getShared = true): PriceListItemRepository
    {
        if ($getShared) { return static::getSharedInstance('priceListItemRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new PriceListItemRepository(null, $db);
    }

    public static function priceListValidator(bool $getShared = true): PriceListValidator
    {
        return $getShared ? static::getSharedInstance('priceListValidator') : new PriceListValidator();
    }

    public static function priceListService(bool $getShared = true): PriceListService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('priceListService'); }

        return new PriceListService(
            static::priceListRepository(false),
            static::priceListItemRepository(false),
            static::priceListValidator(false),
            null,
            static::productRepository(false)
        );
    }

    public static function priceCalculatorService(bool $getShared = true): PriceCalculatorService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('priceCalculatorService'); }

        return new PriceCalculatorService(
            static::priceListRepository(false),
            static::priceListItemRepository(false),
            static::productRepository(false),
            static::productVariantRepository(false)
        );
    }

    public static function orderRepository(bool $getShared = true): OrderRepository
    {
        if ($getShared) { return static::getSharedInstance('orderRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new OrderRepository(null, null, $db);
    }

    public static function orderValidator(bool $getShared = true): OrderValidator
    {
        return $getShared ? static::getSharedInstance('orderValidator') : new OrderValidator();
    }

    public static function orderService(bool $getShared = true): OrderService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('orderService'); }

        return new OrderService(
            static::orderRepository(false),
            static::orderValidator(false),
            static::priceCalculatorService(false)
        );
    }
}
