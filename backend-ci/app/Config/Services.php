<?php

namespace Config;

use App\Repositories\Products\ProductRepository;
use App\Services\Products\ProductService;
use App\Validators\ProductValidator;
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
}
