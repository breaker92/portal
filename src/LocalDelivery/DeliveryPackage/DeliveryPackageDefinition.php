<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Production\LocalDelivery\DeliveryPackage;

use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Production\LocalDelivery\DeliveryBoy\DeliveryBoyDefinition;
use Shopware\Production\Merchants\Content\Merchant\MerchantDefinition;


class DeliveryPackageDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'delivery_package';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return DeliveryPackageCollection::class;
    }

    public function getEntityClass(): string
    {
        return DeliveryPackageEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new LongTextField('content', 'content'))->addFlags(new Required()),
            (new StringField('status', 'status'))->addFlags(new Required()),
            (new FloatField('price', 'price'))->addFlags(new Required()),
            new LongTextField('comment', 'comment'),
            (new StringField('recipient_first_name', 'recipientFirstName'))->addFlags(new Required()),
            (new StringField('recipient_last_name', 'recipientLastName'))->addFlags(new Required()),
            (new StringField('recipient_zipcode', 'recipientZipCode'))->addFlags(new Required()),
            (new StringField('recipient_city', 'recipientCity'))->addFlags(new Required()),
            (new StringField('recipient_street', 'recipientStreet'))->addFlags(new Required()),

            (new FkField('shipping_method_id', 'shippingMethodId', ShippingMethodDefinition::class))->addFlags(new Required()),
            (new FkField('merchant_id', 'merchantId', MerchantDefinition::class))->addFlags(new Required()),
            new FkField('delivery_boy_id', 'deliveryBoyId', DeliveryBoyDefinition::class),
            new ManyToOneAssociationField('shippingMethod',DeliveryBoyDefinition::class,'shipping_method_id'),
            new ManyToOneAssociationField('merchant',DeliveryBoyDefinition::class,'merchant_id'),
            new ManyToOneAssociationField('deliveryBoy',DeliveryBoyDefinition::class,'delivery_boy_id'),
        ]);
    }
}
