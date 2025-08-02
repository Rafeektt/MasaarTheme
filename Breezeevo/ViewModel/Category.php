<?php
namespace Masaar\Breezeevo\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;

class Category implements ArgumentInterface
{
    protected $registry;
    protected $logger;

    public function __construct(Registry $registry, LoggerInterface $logger)
    {
        $this->registry = $registry;
        $this->logger = $logger;
    }

    public function getCurrentCategory(): ?CategoryModel
    {
        $category = $this->registry->registry('current_category');
        $this->logger->debug('ViewModel: Current Category ID: ' . ($category ? $category->getId() : 'N/A'));
        return $category;
    }

    public function getCurrentProduct(): ?Product
    {
        $product = $this->registry->registry('current_product');
        $this->logger->debug('ViewModel: Current Product ID: ' . ($product ? $product->getId() : 'N/A'));
        return $product;
    }

    public function getSubcategories(): array
    {
        $subcategories = [];
        $category = $this->getCurrentCategory();
        $product = $this->getCurrentProduct();

        if ($product && $product->getId()) {
            $this->logger->debug('ViewModel: Processing for Product.');
            $categories = $product->getCategoryCollection()
                ->addAttributeToSelect('*')
                ->addIsActiveFilter()
                ->setOrder('level', 'desc');

            $this->logger->debug('ViewModel: Product Category Collection Size: ' . $categories->getSize());

            $grouped = [];
            foreach ($categories as $cat) {
                $parent = $cat->getParentCategory();
                $this->logger->debug('ViewModel: Product Category: ' . $cat->getName() . ' (ID: ' . $cat->getId() . ')');
                $this->logger->debug('ViewModel: Parent Category: ' . ($parent ? $parent->getName() . ' (ID: ' . $parent->getId() . ')' : 'N/A'));

                if (!$parent || !$parent->getIsActive() || $parent->getLevel() < 2) {
                    continue;
                }

                $siblings = $parent->getChildrenCategories();
                $grouped[$parent->getId()] = [
                    'parent' => $parent,
                    'siblings' => $siblings,
                    'currentCatId' => $cat->getId()
                ];
            }

            foreach ($grouped as $group) {
                $activeSub = null;
                $otherSubs = [];

                foreach ($group['siblings'] as $subcategory) {
                    if (!$subcategory->getIsActive()) continue;
                    if ($subcategory->getId() == $group['currentCatId']) {
                        $activeSub = $subcategory;
                    } else {
                        $otherSubs[] = $subcategory;
                    }
                }
                if ($activeSub) {
                    $subcategories[] = ['item' => $activeSub, 'active' => true];
                }
                foreach ($otherSubs as $item) {
                    $subcategories[] = ['item' => $item, 'active' => false];
                }
            }
        } elseif ($category && $category->getId()) {
            $this->logger->debug('ViewModel: Processing for Category.');
            $children = $category->getChildrenCategories();

            if (!$children->count()) {
                $parentCategory = $category->getParentCategory();
                $this->logger->debug('ViewModel: No direct children. Using parent category: ' . ($parentCategory ? $parentCategory->getName() : 'N/A'));
                if ($parentCategory && $parentCategory->getId()) {
                    $children = $parentCategory->getChildrenCategories();
                }
            }

            foreach ($children as $child) {
                if (!$child->getIsActive()) continue;
                $subcategories[] = [
                    'item' => $child,
                    'active' => $child->getId() == $category->getId()
                ];
            }
        } else {
            $this->logger->debug('ViewModel: No category or product found in registry.');
        }

        $this->logger->debug('ViewModel: Final Subcategories Count: ' . count($subcategories));
        return $subcategories;
    }
}
