<?php declare(strict_types=1);

namespace Shopware\Production\Merchants\Content\Merchant;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\PasswordField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Production\Merchants\Content\Merchant\Aggregate\MerchantOrder\MerchantOrderDefinition;
use Shopware\Production\Merchants\Content\Merchant\Aggregate\MerchantProduct\MerchantProductDefinition;
use Shopware\Production\Merchants\Content\Merchant\Aggregate\MerchantShippingMethod\MerchantShippingMethodDefinition;

class MerchantDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'merchant';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return MerchantCollection::class;
    }

    public function getEntityClass(): string
    {
        return MerchantEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new BoolField('public', 'public')),
            (new StringField('name', 'name'))->addFlags(new Required()),
            (new StringField('email', 'email'))->addFlags(new Required()),
            (new PasswordField('password', 'password'))->addFlags(new Required()),

            new StringField('website', 'website'),
            new StringField('description', 'description'),
            (new StringField('phone_number', 'phoneNumber')),

            (new FkField('customer_id', 'customerId', CustomerDefinition::class)),
            (new OneToOneAssociationField('customer', 'customer_id', 'id', CustomerDefinition::class, false))->addFlags(new CascadeDelete()),

            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new Required()),
            (new OneToOneAssociationField('salesChannel', 'sales_channel_id', 'id', SalesChannelDefinition::class, false)),

            (new FkField('category_id', 'categoryId', CategoryDefinition::class)),
            (new OneToOneAssociationField('category', 'category_id', 'id', CategoryDefinition::class, false)),

            new ManyToManyAssociationField('products', ProductDefinition::class, MerchantProductDefinition::class, 'merchant_id', 'product_id'),
            new ManyToManyAssociationField('orders', OrderDefinition::class, MerchantOrderDefinition::class, 'merchant_id', 'order_id'),
            new ManyToManyAssociationField('shippingMethods', ShippingMethodDefinition::class, MerchantShippingMethodDefinition::class, 'merchant_id', 'shipping_method_id'),

        ]);
    }
}
