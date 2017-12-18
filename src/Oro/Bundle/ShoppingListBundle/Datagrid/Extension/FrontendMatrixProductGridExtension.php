<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductListMatrixFormAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderFormProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderProvider;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class FrontendMatrixProductGridExtension extends AbstractExtension
{
    const SUPPORTED_GRID = 'frontend-product-search-grid';
    //const MATRIX_FORM_TYPE_COLUMN_NAME = 'matrixFormType';
    const MATRIX_FORM_COLUMN_NAME = 'matrixForm';
    const PRODUCT_PRICES_COLUMN_NAME = 'productPrices';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ShoppingListManager */
    private $shoppingListManager;

    /** @var MatrixGridOrderFormProvider */
    private $matrixGridOrderFormProvider;

    /** @var ProductListMatrixFormAvailabilityProvider */
    private $productListMatrixFormAvailabilityProvider;

    /** @var ProductVariantAvailabilityProvider */
    private $productVariantAvailabilityProvider;

    /** @var FrontendProductPricesProvider */
    private $frontendProductPricesProvider;

    /** @var MatrixGridOrderProvider */
    private $matrixGridOrderProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ShoppingListManager $shoppingListManager
     * @param MatrixGridOrderFormProvider $matrixGridOrderFormProvider
     * @param ProductListMatrixFormAvailabilityProvider $productListMatrixFormAvailabilityProvider
     * @param ProductVariantAvailabilityProvider $productVariantAvailabilityProvider
     * @param FrontendProductPricesProvider $frontendProductPricesProvider
     * @param MatrixGridOrderProvider $matrixGridOrderProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ShoppingListManager $shoppingListManager,
        MatrixGridOrderFormProvider $matrixGridOrderFormProvider,
        ProductListMatrixFormAvailabilityProvider $productListMatrixFormAvailabilityProvider,
        ProductVariantAvailabilityProvider $productVariantAvailabilityProvider,
        FrontendProductPricesProvider $frontendProductPricesProvider,
        MatrixGridOrderProvider $matrixGridOrderProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->shoppingListManager = $shoppingListManager;
        $this->matrixGridOrderFormProvider = $matrixGridOrderFormProvider;
        $this->productListMatrixFormAvailabilityProvider = $productListMatrixFormAvailabilityProvider;
        $this->productVariantAvailabilityProvider = $productVariantAvailabilityProvider;
        $this->frontendProductPricesProvider = $frontendProductPricesProvider;
        $this->matrixGridOrderProvider = $matrixGridOrderProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return static::SUPPORTED_GRID === $config->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        /** @var ResultRecord[] $rows */
        $rows = $result->getData();
        $productRepository = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);
        $shoppingList = $this->shoppingListManager->getForCurrentUser();

        foreach ($rows as $row) {
            $productId = $row->getValue('id');

            /** @var Product $product */
            $product = $productRepository->find($productId);

            $matrixFormData = [
                'type' => 'none',
            ];

            if ($product && $this->productListMatrixFormAvailabilityProvider->isMatrixFormAvailable($product)) {
                $matrixFormData['type'] = $this->productListMatrixFormAvailabilityProvider
                    ->getAvailableMatrixFormType($product);
                $matrixFormData['totals'] = [
                    'quantity' => $this->matrixGridOrderProvider->getTotalQuantity($product),
                    'price' => $this->matrixGridOrderProvider->getTotalPriceFormatted($product),
                ];

                $simpleProducts = $this->productVariantAvailabilityProvider
                    ->getSimpleProductsByVariantFields($product);

                if ($matrixFormData['type'] === 'inline') {
                    $formHtml = $this->matrixGridOrderFormProvider->getMatrixOrderFormHtml($product, $shoppingList);
                    $matrixFormData['form'] = $formHtml;

                    $form = $this->matrixGridOrderFormProvider->getMatrixOrderFormView($product, $shoppingList);
                    $matrixFormData['rows'][] = count($form['rows']);
                    $matrixFormData['rows'][] = count($form['rows'][0]['columns']);
                }

                $row->setValue(
                    self::PRODUCT_PRICES_COLUMN_NAME,
                    $this->frontendProductPricesProvider->getByProducts($simpleProducts)
                );
            }

            $row->setValue(self::MATRIX_FORM_COLUMN_NAME, $matrixFormData);
        }

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::PRODUCT_PRICES_COLUMN_NAME => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                ]
            ]
        );

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::MATRIX_FORM_COLUMN_NAME => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                ]
            ]
        );
    }
}
