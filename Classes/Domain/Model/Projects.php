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
 * Class Projects
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Projects extends \RKW\RkwProjects\Domain\Model\Projects
{


    /**
     * txRkwalertsEnableAlerts
     *
     * @var integer
     */
    protected $txRkwalertsEnableAlerts = 0;


    /**
     * Returns the txRkwalertsEnableAlerts
     *
     * @return integer
     */
    public function getTxRkwalertsEnableAlerts()
    {
        return $this->txRkwalertsEnableAlerts;
    }

    /**
     * Sets the txRkwalertsEnableAlerts
     *
     * @param integer $txRkwalertsEnableAlerts
     * @return void
     */
    public function setTxRkwalertsEnableAlerts($txRkwalertsEnableAlerts)
    {
        $this->txRkwalertsEnableAlerts = $txRkwalertsEnableAlerts;
    }

}