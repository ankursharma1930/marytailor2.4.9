<?php
/**
 * @package     Plumrocket_Bestsellers
 * @copyright   Copyright (c) 2019 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Bestsellers\Console\Command;

use Plumrocket\Bestsellers\Model\BestsellersReport;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshBestsellers extends Command
{
    /**
     * @var \Plumrocket\Bestsellers\Model\BestsellersReport
     */
    private $bestsellersReport;

    /**
     * RefreshBestsellers constructor.
     *
     * @param \Plumrocket\Bestsellers\Model\BestsellersReport $bestsellersReport
     */
    public function __construct(
        BestsellersReport $bestsellersReport
    ) {
        $this->bestsellersReport = $bestsellersReport;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('plumrocket:bestsellers:refresh')
            ->setDescription('Refresh Bestsellers Statistics');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->bestsellersReport->refresh();
            $output->writeln('<info>Refreshed Bestsellers statistics successfully refreshed.</info>');
            return 0;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 1;
        }
    }
}
