<?php
namespace Masaar\Breezeevo\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;

class Category implements ArgumentInterface
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Registry $registry
     * @param LoggerInterface $logger
     */
    public function __construct(Registry $registry, LoggerInterface $logger)
    {
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * @return CategoryModel|null
     */
    public function getCurrentCategory(): ?CategoryModel
    {
        $category = $this->registry->registry('current_category');
        $this->logger->debug('ViewModel: Current Category ID: ' . ($category ? $category->getId() : 'N/A'));
        return $category;
    }

    /**
     * @return Product|null
     */
    public function getCurrentProduct(): ?Product
    {
        $product = $this->registry->registry('current_product');
        $this->logger->debug('ViewModel: Current Product ID: ' . ($product ? $product->getId() : 'N/A'));
        return $product;
    }

    /**
     * @return array
     */
    public function getSubcategories(): array
    {
        $subcategories = [];
        $category = $this->getCurrentCategory();
        $product = $this->getCurrentProduct();

        if ($product && $product->getId()) {
            $this->logger->debug('ViewModel: Processing for Product.');
            $categories = $product->getCategoryCollection()
                ->addAttributeToSelect('*')')
                ->addIsActiveFilter()
                ->setOrder('level', 'desc');

            $this->logger->debug('ViewModel: Product Category Collection Size: ' . $categories->getSize());

            if ($categories->getSize()) {
                $grouped = [];
                foreach ($categories as $cat) {
                    $parent = $cat->getParentCategory();
                    $this->logger->debug('ViewModel: Product Category: ' . $cat->getName() . ' (ID: ' . $cat->getId() . ')');
                    $this->logger->debug('ViewModel: Parent Category for Product Category: ' . ($parent ? $parent->getName() . ' (ID: ' . $parent->getId() . ', Level: ' . $parent->getLevel() . ')' : 'N/A'));

                    if (!$parent || !$parent->getIsActive() || $parent->getLevel() < 2) {
                        $this->logger->debug('ViewModel: Skipping product category due to parent conditions.');
                        continue;
                    }

                    $siblings = $parent->getChildrenCategories();
                    $siblingCount = is_array($siblings) ? count($siblings) : $siblings->count();
                    $this->logger->debug('ViewModel: Siblings Count for Product Category Parent: ' . $siblingCount);

                    if ($siblingCount === 0) {
                        $this->logger->debug('ViewModel: Skipping product category due to no siblings.');
                        continue;
                    }

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
                        if (!$subcategory->getIsActive()) {
                            $this->logger->debug('ViewModel: Skipping sibling subcategory (not active): ' . $subcategory->getName());
                            continue;
                        }
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
            }
        } elseif ($category && $category->getId()) {
            $this->logger->debug('ViewModel: Processing for Category.');
            $children = $category->getChildrenCategories();
            $childCount = is_array($children) ? count($children) : $children->count();
            $this->logger->debug('ViewModel: Direct Children Count for Category: ' . $childCount);

            if ($childCount === 0) {
                $parentCategory = $category->getParentCategory();
                $this->logger->debug('ViewModel: No direct children. Checking Parent Category: ' . ($parentCategory ? $parentCategory->getName() . ' (ID: ' . $parentCategory->getId() . ')' : 'N/A'));
                if ($parentCategory && $parentCategory->getId()) {
                    $children = $parentCategory->getChildrenCategories();
                    $this->logger->debug('ViewModel: Parent Children Count: ' . (is_array($children) ? count($children) : $children->count()));
                }
            }

            if (is_iterable($children)) {
                error_log('ViewModel: Iterating through children categories.');
                foreach ($children as $child) {
                    error_log('ViewModel: Child Category ID: ' . $child->getId() . ', Name: ' . $child->getName() . ', Is Active: ' . ($child->getIsActive() ? 'Yes' : 'No'));
                    if (!$child->getIsActive()) {
                        $this->logger->debug('ViewModel: Skipping child category (not active): ' . $child->getName());
                        continue;
                    }
                    $subcategories[] = [
                        'item' => $child,
                        'active' => $child->getId() == $category->getId(),
                    ];
                }
            } else {
                error_log('ViewModel: Children is not iterable.');
            }
        } else {
            error_log('ViewModel: No current category found or product context not applicable.');
        }

        error_log('ViewModel: Final Subcategories Array Size: ' . count($subcategories));
        $this->logger->debug('ViewModel: Final Subcategories Array Size: ' . count($subcategories));
        return $subcategories;
    }
}
