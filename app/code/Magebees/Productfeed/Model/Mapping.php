<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Model;

class Mapping extends \Magento\Framework\Model\AbstractModel
{
    protected $_category;
    
    public function __construct(
        \Magento\Catalog\Model\Category $category,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        $this->_category = $category;
        parent::__construct($context, $registry);
    }
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Magebees\Productfeed\Model\ResourceModel\Mapping');
    }
    function getCategoryMappingList()
    {
        $categories = $this->_category->getCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('level', 1)
                    ->addAttributeToFilter('is_active', 1);
        $parent_categories = $categories->getData();
        $mapping_content = '';
        if (count($parent_categories)>0) {
            $parent_html = '';

            foreach ($parent_categories as $key => $value) :
                $parent_category = $this->_category->load($value['entity_id']);

                $Categories_list = $this->getCategorieslist($value['entity_id'], true);
            /*if($Categories_list != ''){*/
                $parent_html = '<div class="mapping level-'.$parent_category->getLevel().'" data-level="'.$parent_category->getLevel().'">';
                if ($parent_category->hasChildren()) {
                    $parent_html .= '<div class="toggle open"></div>';
                }
                $parent_html .= '<div class="name">'.$parent_category->getName().'(ID: "'.$parent_category->getId().'")</div><div class="map"><input type="text" data-parent="'.$parent_category->getParentId().'" data-id="'.$parent_category->getId().'" value="" name="mapping['.$parent_category->getId().']" class="mapping-input"></div><div class="clear"></div></div>';
                $mapping_content .= $parent_html.$Categories_list;
            //$mapping_content .= $parent_html;
            /*}*/
            endforeach;
        }
        return $mapping_content;
    }
    function getCategorieslist($parentId, $isChild, $category_mapping_arr)
    {
        $allCats = $this->_category->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('parent_id', ['eq' => $parentId])
                ->addAttributeToSort('position', 'asc');
              
        $class = ($isChild) ? "sub-cat-list" : "cat-list";
        $html = isset($html) ? $html : '';
        foreach ($allCats as $category) {
            if ($category->getLevel() > 2) {
                $lable = '';
                for ($i=2; $i<=$category->getLevel(); $i++) {
                //$lable .= "\t".' -';
                }
            } else {
                $lable = '';
            }
            if ($class == "sub-cat-list") {
                if ($category->getLevel()=="1") {
                    $html .= '<div class="mapping level-'.$category->getLevel().'" data-level="'.$category->getLevel().'">';
                    ?><?php
                } else {
                    $html .= '<div class="mapping level-'.$category->getLevel().'" data-level="'.$category->getLevel().'" style="display:none;">';
                    ?><?php
                }
            
        ?>
        <?php if ($category->hasChildren()) {
                $html .= '<div class="toggle open"></div>';
                ?>
                
                <?php
}
                $html .= '<div class="name">'.$category->getName().'(ID: "'.$category->getId().'")</div><div class="map">';
                ?>
        
        <?php if (isset($category_mapping_arr[$category->getId()])) {
            $html .= '<input type="text" data-parent="'.$category->getParentId().'" data-id="'.$category->getId().'" value="'.$category_mapping_arr[$category->getId()].'" name="mapping['.$category->getId().']" class="mapping-input">';
        ?>
        
        <?php
} else {
    $html .= '<input type="text" data-parent="'.$category->getParentId().'" data-id="'.$category->getId().'" value="" name="mapping['.$category->getId().']" class="mapping-input">';
}
        $html .= '</div><div class="clear"></div></div>';
            } elseif ($class == "cat-list") {
                $html .= '<div class="mapping level-'.$category->getLevel().'" data-level="'.$category->getLevel().'" style="display:none;"> <div class="toggle empty"></div><div class="name">'.$category->getName().' (ID: "'.$category->getId().'")</div>';
            
                ?>
                <?php
                $html .= '<div class="map">';
                if ($category_mapping_arr[$category->getId()]!="") {
                    $html .= '<input type="text" data-parent="'.$category->getParentId().'" data-id="'.$category->getId().'" value="'.$category_mapping_arr[$category->getId()].'" name="mapping['.$category->getId().'?>]" class="mapping-input">';
                ?>
        
                <?php
                } else {
                    $html .= '<input type="text" data-parent="'.$category->getParentId().'" data-id="'.$category->getId().'" value="" name="mapping['.$category->getId().']" class="mapping-input">';
                }
                $html .= '</div><div class="clear"></div></div>';
                ?>
        
                <?php
            }
        
            $subcats = $category->getChildren();
            if ($subcats != '') {
                $html .= $this->getCategorieslist($category->getId(), true, $category_mapping_arr);
            }
        }
        return $html;
    }
    public function isJSON($string)
    {
            return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
}
