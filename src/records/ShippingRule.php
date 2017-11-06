<?php

namespace craft\commerce\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Shipping rule record.
 *
 * @property int                          $id
 * @property string                       $name
 * @property string                       $description
 * @property int                          $shippingZoneId
 * @property int                          $methodId
 * @property int                          $priority
 * @property bool                         $enabled
 * @property int                          $minQty
 * @property int                          $maxQty
 * @property float                        $minTotal
 * @property float                        $maxTotal
 * @property float                        $minWeight
 * @property float                        $maxWeight
 * @property float                        $baseRate
 * @property float                        $perItemRate
 * @property float                        $weightRate
 * @property float                        $percentageRate
 * @property float                        $minRate
 * @property float                        $maxRate
 * @property Country                      $country
 * @property State                        $state
 * @property \yii\db\ActiveQueryInterface $shippingZone
 * @property ShippingMethod               $method
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  2.0
 */
class ShippingRule extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%commerce_shippingrules}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['name'], 'required']
        ];
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getShippingZone(): ActiveQueryInterface
    {
        return $this->hasOne(ShippingZone::class, ['id' => 'shippingZoneId']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getMethod(): ActiveQueryInterface
    {
        return $this->hasOne(ShippingZone::class, ['id' => 'shippingMethodId']);
    }
}
