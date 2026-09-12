<?php
/**
 * Copyright © 2015 Magebees. All rights reserved.
 */

namespace Magebees\Productfeed\Model;

class Feed extends \Magento\Framework\Model\AbstractModel
{
    protected $_category;
    protected $_templates;
    public function __construct(
        \Magento\Catalog\Model\Category $category,
        \Magebees\Productfeed\Model\Templates $templates,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        $this->_category = $category;
        $this->_templates = $templates;
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
        $this->_init('Magebees\Productfeed\Model\ResourceModel\Feed');
    }
    public function getTemplateType()
    {
        return [
            ['value' => 'xml', 'label' => __('XML')],
            ['value' => 'csv', 'label' => __('CSV')],
           
        ];
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
            
                $parent_html = '<div class="mapping level-'.$parent_category->getLevel().'" data-level="'.$parent_category->getLevel().'">';
                if ($parent_category->hasChildren()) {
                    $parent_html .= '<div class="toggle open"></div>';
                }
                $parent_html .= '<div class="name">';
                $parent_html .= '<input type="checkbox" class="mapping-checkbox" name="categories['.$category->getId().']" value="'.$category_mapping_arr[$category->getId()].'" id="categories['.$category->getId().'] data-parent="'.$parent_category->getParentId().'" data-id="'.$category->getId().'"><label for="categories['.$category->getId().']">'.$category->getName().'(ID: "'.$category->getId().' )"</label>
			</div><div class="clear"></div></div>';
                $mapping_content .= $parent_html.$Categories_list;
            //$mapping_content .= $parent_html;
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
                if (($category->getLevel()=="1") || ($category->getLevel()=="2")) {
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
        
        <?php }
        $html .= '<div class="name">';
            ?>
        
        
        <?php
        if (isset($category_mapping_arr[$category->getId()])) {
            $html .= '<input type="checkbox" class="mapping-checkbox" name="categories['.$category->getId().']" value="'.$category_mapping_arr[$category->getId()].'" id="categories['.$category->getId().']" data-parent="'.$category->getParentId().'" data-id="'.$category->getId().'" checked><label for="categories['.$category->getId().']">'.$category->getName().' ('.$category->getProductCount().' )</label>';
        ?>
        <?php
        } else {
            $html .= '<input type="checkbox" class="mapping-checkbox" name="categories['.$category->getId().']" id="categories['.$category->getId().']" data-parent="'.$category->getParentId().'" data-id="'.$category->getId().'"><label for="categories['.$category->getId().']">'.$category->getName().' ( '.$category->getProductCount().' )</label>';
            ?>
        
            <?php
        }
        $html .= '</div><div class="clear"></div></div>';
        ?>
        
        
        
        <?php
            } elseif ($class == "cat-list") {
                $html .= '<div class="mapping level-'.$category->getLevel().'"data-level="'.$category->getLevel().'" style="display:none;"><div class="toggle empty"></div><div class="name">';
                ?>
        
                <?php
                if (isset($category_mapping_arr[$category->getId()])) {
                    $html .= '<input type="checkbox" class="mapping-checkbox" name="categories['.$category->getId().']" value="'.$category_mapping_arr[$category->getId()].'" id="categories['.$category->getId().']"
		data-parent="'.$category->getParentId().'" data-id="'.$category->getId().'" checked
		><label for="categories['.$category->getId().']">'.$category->getName().' ('.$category->getProductCount().')</label>';
                ?>
        
                <?php
                } else {
                    $html .= '<input type="checkbox" class="mapping-checkbox" name="categories['.$category->getId().']" id="categories['.$category->getId().']"
		data-parent="'.$category->getParentId().'" data-id="'.$category->getId().'"><label for="categories['.$category->getId().']"
		>'.$category->getName().'( '.$category->getProductCount().' )</label>';
                    ?>
        
                    <?php
                }
                $html .='</div><div class="clear"></div></div>';        ?>
        
        
                <?php
            }
        
            $subcats = $category->getChildren();
            if ($subcats != '') {
                $html .= $this->getCategorieslist($category->getId(), true, $category_mapping_arr);
            }
        }
        return $html;
    }
    public function csvProductRow($product, $csv_content)
    {
        $column_count = count(array_filter($csv_content['name']));
        $row = [];
        
        if ($column_count>0) {
            for ($i=0; $i<=$column_count; $i++) {
                if (isset($csv_content['type'][$i])) {
                    $type = $csv_content['type'][$i];
                    switch ($type) {
                        case 'attribute':
                            if ($product->getData($csv_content['attr'][$i])!="") {
                                $value = $product->getData($csv_content['attr'][$i]);
                                array_push($row, $value);
                            } else {
                                array_push($row, $product[$csv_content['attr'][$i]]);
                            }
                            break;
                        case 'category':
                            break;
                        case 'text':
                            array_push($row, $product[$csv_content['txt'][$i]]);
                            break;
                        case 'images':
                            array_push($row, $product[$csv_content['images'][$i]]);
                            break;
                    }
                }
            }
            return $row;
        } else {
            return $row;
        }
    }
    public function isJSON($string)
    {
            return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
}
