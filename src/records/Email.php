<?php

namespace craft\commerce\records;

use craft\db\ActiveRecord;

/**
 * Email record.
 *
 * @property int    $id
 * @property string $name
 * @property string $subject
 * @property string $recipientType
 * @property string $to
 * @property string $bcc
 * @property bool   $enabled
 * @property string $templatePath
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  2.0
 */
class Email extends ActiveRecord
{
    // Constants
    // =========================================================================

    const TYPE_CUSTOMER = 'customer';
    const TYPE_CUSTOM = 'custom';

    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%commerce_emails}}';
    }
}
