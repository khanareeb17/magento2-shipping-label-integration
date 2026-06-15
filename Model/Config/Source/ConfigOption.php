<?php

namespace AkStackPro\ShippingLabelIntegration\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use AkStackPro\ShippingLabelIntegration\Model\Request\Builder;


class ConfigOption implements OptionSourceInterface
{

    /**
     * @var Builder
     */
    private $builder;

    /**
     * UpdateInventoryCommand constructor.
     *
     * @param Builder $builder The request builder.
     */
    public function __construct(
        Builder $builder,
    ) {
        $this->builder = $builder;
    }

    public function toOptionArray()
    {
        $response = $this->builder->endiciaRatesSelect();
        $optionArray = [];

        if (!is_array($response)) {
            return $optionArray;
        }

        foreach ($response as $key => $item) {
            // Replace underscores with blank spaces and hyphens with vertical bars
            $modifiedString = str_replace(['_', '-'], [' ', ' | '], $item);

            // Capitalize the first letter of each word
            $modifiedString = ucwords($modifiedString);

            $optionArray[] = [
                'value' => $item,
                'label' => $modifiedString,
            ];
        }

        return $optionArray;
    }
}