<?php

namespace MxcDropshipInnocigs\Commands;

use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class StockUpdateCommand extends ShopwareCommand
{
    protected $log;

    /*
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mxcdsi:updatestock')
            ->setDescription('Update stock information from InnoCigs')
            /*  ->addArgument(
                  'filepath',
                  InputArgument::REQUIRED,
                  'Path to file to read data from.'
              )*/
            ->setHelp(<<<EOF
The <info>%command.name%</info> updates stock information from Innocigs.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Updating stock information from InnoCigs ...</info>');

        try {
        } catch (Throwable $e) {
            $output->writeln('<merror>' . $e->getMessage() . '</merror>');
        }
        $output->writeln('<info>Done</info>');
    }
}