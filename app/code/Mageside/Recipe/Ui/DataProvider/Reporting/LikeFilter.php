<?php

namespace Mageside\Recipe\Ui\DataProvider\Reporting;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterApplierInterface;

class LikeFilter implements FilterApplierInterface
{

    /**
     * Escape against value
     * @param string $value
     * @return string
     */
    private function escapeAgainstValue($value)
    {
        return preg_replace('/([+\-><\(\)~*\"@]+)/', ' ', $value);
    }

    /**
     * Apply filter
     *
     * @param Collection $collection
     * @param Filter $filter
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function apply(Collection $collection, Filter $filter)
    {
        if (!$collection instanceof AbstractDb) {
            throw new \InvalidArgumentException('Database collection required.');
        }

        if ($filter->getField() == 'title') {
            $collection->getSelect()
                ->where(
                    'IFNULL(at_title_store.meta_value, at_title.meta_value) LIKE ?',
                    $this->escapeAgainstValue($filter->getValue())
                );
        } else {
            $collection->addFieldToFilter($filter->getField(), [$filter->getConditionType() => $filter->getValue()]);
        }
    }
}