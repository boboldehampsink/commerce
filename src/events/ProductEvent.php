<?php

namespace craft\commerce\events;

use craft\commerce\elements\Product;
use craft\events\CancelableEvent;

/**
 * Class ProductEvent
 *
 * @package craft\commerce\events
 */
class ProductEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var Product The address model
     */
    public $product;

    /**
     * @var bool If this is a new product
     */
    public $isNew;
}
