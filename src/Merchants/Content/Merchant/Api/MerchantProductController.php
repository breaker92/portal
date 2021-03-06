<?php declare(strict_types=1);

namespace Shopware\Production\Merchants\Content\Merchant\Api;

use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Tax\TaxEntity;
use Shopware\Production\Merchants\Content\Merchant\MerchantEntity;
use Shopware\Production\Merchants\Content\Merchant\SalesChannelContextExtension;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class MerchantProductController
{
    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $taxRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $merchantRepository;

    /**
     * @var MediaService
     */
    private $mediaService;

    /**
     * @var NumberRangeValueGeneratorInterface
     */
    private $numberRangeValueGenerator;

    public function __construct(
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $taxRepository,
        EntityRepositoryInterface $customerRepository,
        EntityRepositoryInterface $mediaRepository,
        EntityRepositoryInterface $merchantRepository,
        NumberRangeValueGeneratorInterface $numberRangeValueGenerator,
        MediaService $mediaService
    ) {
        $this->productRepository = $productRepository;
        $this->taxRepository = $taxRepository;
        $this->customerRepository = $customerRepository;
        $this->mediaRepository = $mediaRepository;
        $this->merchantRepository = $merchantRepository;
        $this->numberRangeValueGenerator = $numberRangeValueGenerator;
        $this->mediaService = $mediaService;
    }

    /**
     * @Route(name="merchant-api.merchant.product.read", path="/merchant-api/v{version}/products", methods={"GET"})
     */
    public function getList(SalesChannelContext $context): JsonResponse
    {
        $merchant = SalesChannelContextExtension::extract($context);

        $criteria = new Criteria([$merchant->getId()]);
        $criteria->addAssociation('products');
        $criteria->addAssociation('products.cover');

        $merchant = $this->merchantRepository->search($criteria, $context->getContext())->first();

        $products = [];
        /** @var ProductEntity $product */
        foreach ($merchant->getProducts() as $key => $product) {
            $productData = [
                'id' => $product->getId(),
                'name' => $product->getTranslation('name'),
                'description' => $product->getTranslation('description'),
                'price' => $product->getPrice()->first()->getGross(),
                'tax' => $product->getTax()->getTaxRate(),
            ];

            if ($product->getCover()) {
                $productData['media'] = $product->getCover()->getMedia()->getUrl();
            }
            $products[] = $productData;
        }

        return new JsonResponse([
            'data' => $products
        ]);
    }

    /**
     * @Route(name="merchant-api.merchant.product.create", path="/merchant-api/v1/products", methods={"POST"}, defaults={"csrf_protected"=false})
     */
    public function create(Request $request, SalesChannelContext $context): JsonResponse
    {
        $merchant = SalesChannelContextExtension::extract($context);

        $missingFields = $this->checkForMissingFields($request);

        if ($missingFields) {
            throw new \InvalidArgumentException('The following missing values must be set: ' . implode(', ', $missingFields));
        }

        $productData = [
            'id' => Uuid::randomHex(),
            'name' => $request->request->get('name'),
            'description' => $request->request->get('description'),
            'stock' => $request->request->getInt('stock', 0),
            'productNumber' => $this->numberRangeValueGenerator->getValue('product', $context->getContext(), $context->getSalesChannel()->getId()),
            'price' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => $request->request->get('price'),
                    'net' => $request->request->get('price') / (1 + $request->request->get('tax') / 100),
                    'linked' => true
                ]
            ],
            'visibilities' => [
                [
                    'salesChannelId' => $context->getSalesChannel()->getId(),
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL
                ],
            ],
            'merchants' => [
                [
                    'id' => $merchant->getId()
                ]
            ]
        ];

        $taxEntity = $this->getTaxFromRequest($request, $context);
        $productData['tax'] = ['id' => $taxEntity->getId()];

        if ($request->files->has('media')) {
            $mediaId = $this->createMediaIdByFile($request->files->get('media'), $context);
            $productData['cover'] = ['mediaId' => $mediaId];
        }

        $this->productRepository->create([$productData], Context::createDefaultContext());

        return new JsonResponse(
            ['message' => 'Successfully created product!', 'data' => $productData]
        );
    }

    /**
     * @Route(name="merchant-api.merchant.product.update", path="/merchant-api/v1/products/{productId}", methods={"POST"}, defaults={"csrf_protected"=false})
     */
    public function update(Request $request, string $productId, SalesChannelContext $context): JsonResponse
    {
        $product = $this->getProductFromMerchant($productId, $context);

        if (!$product) {
            throw new NotFoundHttpException(sprintf('Cannot find product by id %s', $productId));
        }

        $productData = [];
        if ($request->request->has('name')) {
            $productData['name'] = $request->request->get('name');
        }

        if ($request->request->has('description')) {
            $productData['description'] = $request->request->get('description');
        }

        if ($request->request->has('tax')) {
            $taxEntity = $this->getTaxFromRequest($request, $context);
            $productData['tax'] = ['id' => $taxEntity->getId()];
            $product->setTax($taxEntity);
        }

        if ($request->request->has('price')) {
            $productData['price'] = [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => $request->request->get('price'),
                    'net' => $request->request->get('price') / (1 + $product->getTax()->getTaxRate()/100),
                    'linked' => true
                ]
            ];
        }


        if ($request->files->has('media')) {
            $mediaId = $this->createMediaIdByFile($request->files->get('media'), $context);
            $productData['cover'] = ['mediaId' => $mediaId];
        }

        if (!$productData) {
            throw new \InvalidArgumentException('No update data was provided.');
        }

        $productData['id'] = $productId;

        $this->productRepository->update([$productData], Context::createDefaultContext());

        return new JsonResponse(
            ['message' => 'Successfully saved product!', 'data' => $productData]
        );
    }

    /**
     * @Route(name="merchant-api.merchant.product.delete", path="/merchant-api/v1/products/{productId}", methods={"DELETE"})
     */
    public function delete(string $productId, SalesChannelContext $context): JsonResponse
    {
        $product = $this->getProductFromMerchant($productId, $context);

        $this->productRepository->delete([['id' => $product->getId()]], $context->getContext());

        return new JsonResponse(
            ['message' => 'Succesfully deleted product!', 'data' => ['id' => $productId]]
        );
    }

    private function getProductFromMerchant(string $productId, SalesChannelContext $context): ProductEntity
    {
        $product = $this->getMerchantFromContext($context)->getProducts()->get($productId);

        if (!$product) {
            throw new NotFoundHttpException(sprintf('Cannot find product by id %s for current merchant.', $productId));
        }

        return $product;
    }

    private function createMediaIdByFile(UploadedFile $uploadedFile, SalesChannelContext $context): string
    {
        $mediaId = Uuid::randomHex();

        $this->mediaRepository->create([['id' => $mediaId]], $context->getContext());

        $mediaFile = new MediaFile(
            $uploadedFile->getPathname(),
            $uploadedFile->getMimeType(),
            'jpg',
            $uploadedFile->getSize()
        );

        $this->mediaService->saveMediaFile($mediaFile, md5(random_bytes(10)), $context->getContext(), null, $mediaId);

        return $mediaId;
    }

    private function getTaxFromRequest(Request $request, SalesChannelContext $context): TaxEntity
    {
        $taxCriteria = new Criteria();
        $taxCriteria->addFilter(new EqualsFilter('taxRate', $request->request->get('tax')));
        /** @var TaxEntity $taxEntity */
        $taxEntity = $this->taxRepository->search($taxCriteria, $context->getContext())->first();

        if (!$taxEntity) {
            throw new NotFoundHttpException(sprintf('Cannot find tax by rate %s', $request->request->get('tax')));
        }

        return $taxEntity;
    }

    private function checkForMissingFields(Request $request): array
    {
        $necessaryFields = [
            'name',
            'description',
            'tax',
            'price',
        ];

        $missingFields = [];
        foreach ($necessaryFields as $necessaryField) {
            if ($request->request->has($necessaryField)) {
                continue;
            }

            $missingFields[] = $necessaryField;
        }

        return $missingFields;
    }

    private function getMerchantFromContext(SalesChannelContext $context): MerchantEntity
    {
        $customerId = $context->getCustomer()->getId();
        $criteria = new Criteria();
        $criteria->addAssociation('products');
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        return $this->merchantRepository->search($criteria, $context->getContext())->first();
    }
}
