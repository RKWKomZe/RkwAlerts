<?php

namespace RKW\RkwAlerts\Domain\Model;
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

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class FrontendUser
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendUser extends \Madj2k\FeRegister\Domain\Model\FrontendUser
{

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\RKW\RkwAlerts\Domain\Model\Alert>|null
     */
    protected ?ObjectStorage $txRkwalertsAlerts = null;

    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct();

        // Do not remove the next line: It would break the functionality
        $this->initStorageObjects();
    }


    /**
     * Initializes all ObjectStorage properties
     * Do not modify this method!
     * It will be rewritten on each save in the extension builder
     * You may modify the constructor of this class instead
     *
     * @return void
     */
    protected function initStorageObjects()
    {
        $this->txRkwalertsAlerts = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }



    /**
     * Adds a txRkwalertsAlerts for the feUsers
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert
     * @return void
     * @api
     */
    public function addTxRkwalertsAlerts (Alert $txRkwalertsAlert): void
    {
        $this->txRkwalertsAlerts->attach($txRkwalertsAlert);
    }


    /**
     * Removes a txRkwalertsAlerts for the feUsers
     *
     * @param \RKW\RkwAlerts\Domain\Model\Alert $txRkwalertsAlert
     * @return void
     * @api
     */
    public function removeTxRkwalertsAlerts (Alert $txRkwalertsAlert): void
    {
        $this->txRkwalertsAlerts->detach($txRkwalertsAlert);
    }


    /**
     * Returns the txRkwalertsAlerts for the feUsers
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\RKW\RkwAlerts\Domain\Model\Alert>
     * @api
     */
    public function getTxRkwalertsAlerts (): ObjectStorage
    {
        return $this->txRkwalertsAlerts;
    }


    /**
     * Sets the txRkwalertsAlerts for the feUsers
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\RKW\RkwAlerts\Domain\Model\Alert> $txRkwalertsAlerts
     * @return void
     * @api
     */
    public function setTxRkwalertsAlerts (ObjectStorage $txRkwalertsAlerts): void
    {
        $this->txRkwalertsAlerts = $txRkwalertsAlerts;
    }


}

