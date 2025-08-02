<?php
namespace Masaar\Breezeevo\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\Product;

class Category implements ArgumentInterface
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return CategoryModel|null
     */
    public function getCurrentCategory(): ?CategoryModel
    {
        return $this->registry->registry('current_category');
    }

    /**
     * @return Product|null
     */
    public function getCurrentProduct(): ?Product
    {
        return $this->registry->registry('current_product');
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
            $categories = $product->getCategoryCollection()
                ->addAttributeToSelect('*')
                ->addIsActiveFilter()
                ->setOrder('level', 'desc');

            if ($categories->getSize()) {
                $grouped = [];
                foreach ($categories as $cat) {
                    $parent = $cat->getParentCategory();
                    if (!$parent || !$parent->getIsActive() || $parent->getLevel() < 2) {
                        continue;
                    }

                    $siblings = $parent->getChildrenCategories();
                    $siblingCount = is_array($siblings) ? count($siblings) : $siblings->count();
                    if ($siblingCount === 0) {
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
            $children = $category->getChildrenCategories();
            $childCount = is_array($children) ? count($children) : $children->count();

            if ($childCount === 0) {
                $parentCategory = $category->getParentCategory();
                if ($parentCategory && $parentCategory->getId()) {
                    $children = $parentCategory->getChildrenCategories();
                }
            }

            if (is_iterable($children)) {
                foreach ($children as $child) {
                    if (!$child->getIsActive()) {
                        continue;
                    }
                    $subcategories[] = [
                        'item' => $child,
                        'active' => $child->getId() == $category->getId(),
                    ];
                }
            }
        }

        return $subcategories;
    }
}
