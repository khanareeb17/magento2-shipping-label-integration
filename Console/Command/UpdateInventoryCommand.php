<?php

namespace AkStackPro\ShippingLabelIntegration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AkStackPro\ShippingLabelIntegration\Model\Request\Builder;
use AkStackPro\ShippingLabelIntegration\Model\Config;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use AkStackPro\ShippingLabelIntegration\Model\Request\GetOAuthToken;
use AkStackPro\ShippingLabelIntegration\Helper\Data;
use AkStackPro\ShippingLabelIntegration\Model\Services\Tracking;

/**
 * Class UpdateInventoryCommand
 *
 * This command sends a cURL request to Sport South's inventory API.
 *
 * @package AkStackPro\SportSouth\Console\Command
 */
class UpdateInventoryCommand extends Command
{
    /**
     * @var Builder
     */
    private $builder;
   
    /**
     * @var Config
     */
    private $config;

    /**
     * @var GetOAuthToken
     */
    private $oAuthToken;

    /**
     * @var Data
     */
    private $helperData;

    /**
     * @var Tracking
     */
    private $tracking;

    /**
     * UpdateInventoryCommand constructor.
     *
     * @param Builder $builder The request builder.
     * @param Config $config The configuration instance.
     */
    public function __construct(
        Builder $builder,
        Config $config,
        GetOAuthToken $oAuthToken,
        Data $helperData,
        Tracking $tracking
    ) {
        $this->builder = $builder;
        $this->config = $config;
        $this->oAuthToken = $oAuthToken;
        $this->helperData = $helperData;
        $this->tracking = $tracking;
        parent::__construct();
    }

    /**
     * Configures the command.
     */
    protected function configure()
    {
        $this->setName('ups:auth:test');
        $this->setDescription('Send cURL request to Sport South inventory API');
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input The input interface.
     * @param OutputInterface $output The output interface.
     * @return int The exit status.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->config->isActive()) {
            $outputStyle = new OutputFormatterStyle('black', 'yellow', ['blink']);
            $output->getFormatter()->setStyle('fire', $outputStyle);
            
            return $output->writeln('<fire>The module is disabled. Please enable it to perform this action</>');
        }

        $response = $this->builder->endiciaRatesRequestTest();
        //$response = $this->oAuthToken->executeEndicia();
        

        if ($response !== null) {
            $output->writeln('Request Result: ' . json_encode($response));
        } else {
            $output->writeln('Request Failure.');
        }
    }
}
