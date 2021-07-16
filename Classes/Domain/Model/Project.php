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

/**
 * Class Project
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Project extends \RKW\RkwProjects\Domain\Model\Projects
{

    /**
     * txRkwalertsEnableAlerts
     *
     * @var bool
     */
    protected $txRkwalertsEnableAlerts = false;


    /**
     * Returns the txRkwalertsEnableAlerts
     *
     * @return bool
     */
    public function getTxRkwalertsEnableAlerts(): bool
    {
        return $this->txRkwalertsEnableAlerts;
    }

    /**
     * Sets the txRkwalertsEnableAlerts
     *
     * @param bool $txRkwalertsEnableAlerts
     * @return void
     */
    public function setTxRkwalertsEnableAlerts(bool $txRkwalertsEnableAlerts): void
    {
        $this->txRkwalertsEnableAlerts = $txRkwalertsEnableAlerts;
    }

}