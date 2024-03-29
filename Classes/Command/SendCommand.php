<?php

namespace RKW\RkwAlerts\Command;
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Madj2k\CoreExtended\Utility\FrontendSimulatorUtility;
use RKW\RkwAlerts\Alerts\AlertManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * class SendCommand
 *
 * Execute on CLI with: 'vendor/bin/typo3 rkw_alerts:send'
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SendCommand extends Command
{

    /**
     * @var \RKW\RkwAlerts\Alerts\AlertManager|null
     */
    protected ?AlertManager $alertManager = null;


    /**
     * @var \TYPO3\CMS\Core\Log\Logger|null
     */
    protected ?Logger $logger = null;


    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this->setDescription('Sends all alerts.')
            ->addOption(
                'filterField',
                'f',
                InputOption::VALUE_REQUIRED,
                'Database-field for selecting relevant pages for alerts.',
                'lastUpdated'
            )
            ->addOption(
                'timeSinceCreation',
                't',
                InputOption::VALUE_REQUIRED,
                'Time since creation (in seconds) for selecting relevant pages for alerts',
                432000
            )
            ->addOption(
                'settingsPid',
                's',
                InputOption::VALUE_REQUIRED,
                'PID to use configuration from.',
                0
            )
            ->addOption(
                'dryRun',
                'd',
                InputOption::VALUE_REQUIRED,
                'Perform a dry run? Then define an email-address to send the test-mails to',
                ''
            );
    }

    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @see \Symfony\Component\Console\Input\InputInterface::bind()
     * @see \Symfony\Component\Console\Input\InputInterface::validate()
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->alertManager = $objectManager->get(AlertManager::class);
    }


    /**
     * Executes the command for showing sys_log entries
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     * @see \Symfony\Component\Console\Input\InputInterface::bind()
     * @see \Symfony\Component\Console\Input\InputInterface::validate()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $filterField = $input->getOption('filterField');
        $timeSinceCreation = $input->getOption('timeSinceCreation');
        $settingsPid = $input->getOption('settingsPid');
        $debugMail = $input->getOption('dryRun');

        if ($debugMail) {
            $io->note('THIS IS A DRY-RUN TO ' . $debugMail);
        }
        $io->note('Using filterField="' . $filterField .
            '" with timeSinceCreation="' . $timeSinceCreation .
            '" and settingsPid="' . $settingsPid . '"'
        );
        $io->newLine();

        $result = 0;
        try {

            // simulate frontend
            FrontendSimulatorUtility::simulateFrontendEnvironment($settingsPid);

            // send alerts
            $this->alertManager->sendNotification($filterField, $timeSinceCreation, $debugMail);

            // reset frontend
            FrontendSimulatorUtility::resetFrontendEnvironment();

        } catch (\Exception $e) {

            $message = sprintf('An unexpected error occurred while trying to update the statistics of e-mails: %s',
                str_replace(array("\n", "\r"), '', $e->getMessage())
            );

            // @extensionScannerIgnoreLine
            $io->error($message);
            $this->getLogger()->log(LogLevel::ERROR, $message);
            $result = 1;
        }

        $io->writeln('Done');
        return $result;

    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger(): \TYPO3\CMS\Core\Log\Logger
    {
        if (!$this->logger instanceof \TYPO3\CMS\Core\Log\Logger) {
            $this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }

        return $this->logger;
    }
}
