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
        $category = $this->getCurrentCategory();
        $product = $this->getCurrentProduct();

        if ($product && $product->getId()) {
            $categories = $product->getCategoryCollection()
                ->addAttributeToSelect('*')
                ->addIsActiveFilter()
                ->setOrder('level', 'desc');

            foreach ($categories as $cat) {
                $parent = $cat->getParentCategory();
                if (!$parent || !$parent->getIsActive() || $parent->getLevel() < 2) {
                    continue;
                }

                $siblings = $parent->getChildrenCategories()->addAttributeToSelect('*');
                foreach ($siblings as $item) {
                    if (!$item->getIsActive()) continue;
                    $subcategories[] = [
                        'item' => $item,
                        'active' => $item->getId() == $cat->getId()
                    ];
                }
            }
        } elseif ($category && $category->getId()) {
            $children = $category->getChildrenCategories()->addAttributeToSelect('*');
            if (!$children->count() && $category->getParentCategory()) {
                $children = $category->getParentCategory()->getChildrenCategories()->addAttributeToSelect('*');
            }

            foreach ($children as $item) {
                if (!$item->getIsActive()) continue;
                $subcategories[] = [
                    'item' => $item,
                    'active' => $item->getId() == $category->getId()
                ];
            }
        }

        return $subcategories;
    }
}
