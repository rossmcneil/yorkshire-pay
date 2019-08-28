<?php

namespace Rossmcneil\YorkshirePay;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Rossmcneil\YorkshirePay\Skeleton\SkeletonClass
 */
class YorkshirePayFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'yorkshire-pay';
    }
}
