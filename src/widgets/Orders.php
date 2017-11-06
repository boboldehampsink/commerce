<?php

namespace craft\commerce\widgets;

use Craft;
use craft\base\Widget;
use craft\commerce\elements\Order;
use craft\commerce\Plugin;
use craft\commerce\web\assets\orderswidget\OrdersWidgetAsset;
use craft\helpers\StringHelper;

/**
 * Class Orders
 *
 * @property string       $name
 * @property string|false $bodyHtml
 * @property string       $settingsHtml
 * @property string       $title
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  2.0
 */
class Orders extends Widget
{
    // Properties
    // =========================================================================

    /**
     * @var int|null
     */
    public $orderStatusId;

    /**
     * @var int
     */
    public $limit = 10;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function isSelectable(): bool
    {
        // This widget is only available to users that can manage orders
        return Craft::$app->getUser()->checkPermission('commerce-manageOrders');
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return Craft::t('commerce', 'Recent Orders');
    }

    /**
     * @inheritdoc
     */
    public static function iconPath(): string
    {
        return Craft::getAlias('@craft/commerce/icon-mask.svg');
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        if ($orderStatusId = $this->orderStatusId) {
            $orderStatus = Plugin::getInstance()->getOrderStatuses()->getOrderStatusById($orderStatusId);

            if ($orderStatus) {
                return Craft::t('commerce', 'Recent Orders').' – '.Craft::t('commerce', $orderStatus->name);
            }
        }

        return parent::getTitle();
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        $orders = $this->_getOrders();

        return Craft::$app->getView()->renderTemplate('commerce/_components/widgets/Orders/body', [
            'orders' => $orders,
            'showStatuses' => $this->orderStatusId === null,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): string
    {
        $orderStatuses = Plugin::getInstance()->getOrderStatuses()->getAllOrderStatuses();

        Craft::$app->getView()->registerAssetBundle(OrdersWidgetAsset::class);

        $id = 'analytics-settings-'.StringHelper::randomString();
        $namespaceId = Craft::$app->getView()->namespaceInputId($id);

        Craft::$app->getView()->registerJs("new Craft.Commerce.OrdersWidgetSettings('".$namespaceId."');");

        return Craft::$app->getView()->renderTemplate('commerce/_components/widgets/Orders/settings', [
            'id' => $id,
            'widget' => $this,
            'orderStatuses' => $orderStatuses,
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the recent entries, based on the widget settings and user permissions.
     *
     * @return array
     */
    private function _getOrders(): array
    {
        $orderStatusId = $this->orderStatusId;
        $limit = $this->limit;

        $query = Order::find();
        $query->isCompleted(true);
        $query->dateOrdered(':notempty:');
        $query->limit($limit);
        $query->orderBy('dateOrdered');

        if ($orderStatusId) {
            $query->orderStatusId($orderStatusId);
        }

        return $query->all();
    }
}
