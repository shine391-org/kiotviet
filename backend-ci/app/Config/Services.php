<?php

namespace Config;

use App\Repositories\Attributes\AttributeRepository;
use App\Repositories\ProductMedia\ProductMediaRepository;
use App\Repositories\Products\ProductRepository;
use App\Services\Products\ProductService;
use App\Services\ProductMedia\ProductMediaService;
use App\Services\Attributes\AttributeService;
use App\Validators\ProductMediaDateValidator;
use App\Validators\ProductMediaSearchValidator;
use App\Validators\ProductMediaValidator;
use App\Validators\ProductValidator;
use App\Validators\AttributeValidator;
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

        return new ProductService(static::productRepository(false), static::productValidator(false));
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
}
