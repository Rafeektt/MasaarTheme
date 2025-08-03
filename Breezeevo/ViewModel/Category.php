<?php
namespace Masaar\Breezeevo\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\Product;

class Category implements ArgumentInterface
{
    protected Registry $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function getCurrentCategory(): ?CategoryModel
    {
        return $this->registry->registry('current_category');
    }

    public function getCurrentProduct(): ?Product
    {
        return $this->registry->registry('current_product');
    }

    public function getSubcategories(): array
    {
        $subcategories = [];
        $currentCat = null;
        $parentCategory = null;

        // Determine the context: product page or category page
        $product = $this->getCurrentProduct();
        $category = $this->getCurrentCategory();

        if ($product && $product->getId()) {
            // On product page, get the most relevant category for the product
            $categories = $product->getCategoryCollection()
                ->addAttributeToSelect('*')
                ->addIsActiveFilter()
                ->setOrder('level', 'DESC');
            
            if ($categories->count()) {
                $currentCat = $categories->getFirstItem();
                $parentCategory = $currentCat->getParentCategory();
            }

        } elseif ($category && $category->getId()) {
            // On category page
            $currentCat = $category;
            // If the current category has children, it's the parent.
            if ($category->getChildrenCount()) {
                $parentCategory = $category;
            } else {
            // Otherwise, use its parent.
                $parentCategory = $category->getParentCategory();
            }
        }

        // If we have a valid parent category, get its children
        if ($parentCategory && $parentCategory->getId() && $parentCategory->getIsActive()) {
            $children = $parentCategory->getChildrenCategories()->addAttributeToSelect('*')->addIsActiveFilter();

            foreach ($children as $item) {
                $subcategories[] = [
                    'item' => $item,
                    'active' => $currentCat && $item->getId() == $currentCat->getId()
                ];
            }
        }

        return $subcategories;
    }
}
