<?php
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\Recipe\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\Dir;

class InstallData implements InstallDataInterface
{

    protected $_systemStore;

    protected $_mediaDirectory;

    protected $_moduleReader;

    protected $_directoryList;

    protected $_file;

    protected $images = [
        'gluten.png',
        'milk.png',
        'nut.png',
        'paleo.png',
        'raw.png',
        'vegan.png',
        'vegetarian.png',
        'wheat-free.png',
        'recipe-image-coming-soon.png'
        ];

    /**
     * InstallData constructor.
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param Filesystem $filesystem
     * @param Filesystem\DirectoryList $directoryList
     * @param Filesystem\Driver\File $file
     * @param Dir\Reader $moduleReader
     */
    public function __construct(
        \Magento\Store\Model\System\Store $systemStore,
        Filesystem $filesystem,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Module\Dir\Reader $moduleReader
    ) {
        $this->_directoryList = $directoryList;
        $this->_file = $file;
        $this->_moduleReader = $moduleReader;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->_systemStore = $systemStore;
    }

    private function addMediaFiles($images)
    {
        $baseModulePath = $this->_moduleReader->getModuleDir(Dir::MODULE_VIEW_DIR, 'Mageside_Recipe');
        $this->_mediaDirectory->create('recipe');
        foreach ($images as $image) {
            $imagePath = $baseModulePath . '/frontend/web/images/filters/' . $image;
            $imageDestination = $this->_mediaDirectory->getAbsolutePath() . 'recipe/' . $image;
            if ($imagePath && $imageDestination) {
                $this->_file->copy($imagePath, $imageDestination);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $installer = $setup;

        $installer
            ->getConnection()
            ->insert(
                $installer->getTable('rating_entity'),
                ['entity_code' => \Mageside\Recipe\Model\Review::RECIPE_CODE]
            );
        $installer
            ->getConnection()
            ->insert(
                $installer->getTable('review_entity'),
                ['entity_code' => \Mageside\Recipe\Model\Review::RECIPE_CODE]
            );
        $entityId = $installer
            ->getConnection()
            ->lastInsertId(
                $installer->getTable('rating_entity')
            );

        $bind = ['rating_code' => 'Recipe Rating', 'position' => 0, 'entity_id' => $entityId];
        $installer->getConnection()
            ->insert(
                $installer->getTable('rating'),
                $bind
            );

        //Fill table rating/rating_option
        $ratingId = $installer->getConnection()->lastInsertId($installer->getTable('rating'));
        $optionData = [];
        for ($i = 1; $i <= 5; $i++) {
            $optionData[] = ['rating_id' => $ratingId, 'code' => (string)$i, 'value' => $i, 'position' => $i];
        }
        $installer->getConnection()->insertMultiple($installer->getTable('rating_option'), $optionData);

        $table = $installer->getTable('rating_store');
        $insert = [];
        foreach ($this->_systemStore->getStoreCollection() as $store) {
            $insert[] = ['rating_id' => $ratingId, 'store_id' => (int)$store->getId()];
        }
        $installer->getConnection()->insertMultiple($table, $insert);

        $connection = $installer->getConnection();

        $filters = [
            ['meal_type', 'Meal Type'],
            ['all_diets', 'All Diets'],
            ['seasons', 'Seasons'],
        ];
        $connection->insertArray(
            $installer->getTable('ms_recipe_filter'),
            ['code', 'type'],
            $filters
        );

        $optionsMeal = [
            [
                'slug' => 'breakfast',
                'label' => 'Breakfast',
                'option_image' => '',
                'filter' => 'meal_type'
            ],
            [
                'slug' => 'starter',
                'label' => 'Starter',
                'option_image' => '',
                'filter' => 'meal_type'
            ],
            [
                'slug' => 'main_meal',
                'label' => 'Main Meal',
                'option_image' => '',
                'filter' => 'meal_type'
            ],
            [
                'slug' => 'dessert',
                'label' => 'Dessert',
                'option_image' => '',
                'filter' => 'meal_type'
            ],
            [
                'slug' => 'sides_and_snacks',
                'label' => 'Sides And Snacks',
                'option_image' => '',
                'filter' => 'meal_type'
            ],
            [
                'slug' => 'dressing_and_sauces',
                'label' => 'Dressing And Sauces',
                'option_image' => '',
                'filter' => 'meal_type'
            ],
            [
                'slug' => 'drinks_and_cocktails',
                'label' => 'Drinks And Cocktails',
                'option_image' => '',
                'filter' => 'meal_type'
            ],
            [
                'slug' => 'vegetarians',
                'label' =>'Vegetarians',
                'option_image' => 'vegetarian.png',
                'filter' => 'all_diets'
            ],
            [
                'slug' => 'vegans',
                'label' =>'Vegans',
                'option_image' => 'vegan.png',
                'filter' => 'all_diets'
            ],
            [
                'slug' => 'gluten_free_diets',
                'label' =>'Gluten-free Diets',
                'option_image' => 'gluten.png',
                'filter' => 'all_diets'
            ],
            [
                'slug' => 'wheat_free_diets',
                'label' =>'Wheat-free Diets',
                'option_image' => 'wheat-free.png',
                'filter' => 'all_diets'
            ],
            [
                'slug' => 'dairy_free_diets',
                'label' =>'Dairy-free Diets',
                'option_image' => 'milk.png',
                'filter' => 'all_diets'
            ],
            [
                'slug' => 'nut_free_diets',
                'label' =>'Nut-free Diets',
                'option_image' => 'nut.png',
                'filter' => 'all_diets'
            ],
            [
                'slug' => 'raw',
                'label' =>'Raw',
                'option_image' => 'raw.png',
                'filter' => 'all_diets'
            ],
            [
                'slug' => 'paleo',
                'label' =>'Paleo',
                'option_image' => 'paleo.png',
                'filter' => 'all_diets'
            ],
            [
                'slug' => 'spring',
                'label' =>'Spring',
                'option_image' => '',
                'filter' => 'seasons'
            ],
            [
                'slug' => 'summer',
                'label' =>'Summer',
                'option_image' => '',
                'filter' => 'seasons'
            ],
            [
                'slug' => 'autumn',
                'label' =>'Autumn',
                'option_image' => '',
                'filter' => 'seasons'
            ],
            [
                'slug' => 'winter',
                'label' =>'Winter',
                'option_image' => '',
                'filter' => 'seasons'
            ]
        ];

        $select = $connection->select();
        $select->from(
            $installer->getTable('ms_recipe_filter'),
            ['code', 'id']
        );
        $filtersSaved = $connection->fetchAssoc($select);

        $filterOptionData = [];
        foreach ($optionsMeal as $option) {
            if (isset($filtersSaved[$option['filter']])) {
                $filterOptionData[] = [
                    'filter_id'     => $filtersSaved[$option['filter']]['id'],
                    'label'         => $option['label'],
                    'option_image'  => $option['option_image'],
                    'slug'          => $option['slug'],
                ];
            }
        }
        $connection->insertArray(
            $installer->getTable('ms_recipe_filter_options'),
            ['filter_id', 'label', 'option_image', 'slug'],
            $filterOptionData
        );

        $this->addMediaFiles($this->images);

        $setup->endSetup();
    }
}
