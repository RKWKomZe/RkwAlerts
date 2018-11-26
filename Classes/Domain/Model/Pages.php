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
 * Class Pages
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Pages extends \RKW\RkwProjects\Domain\Model\Pages
{


    /**
     * txRkwalertsSendStatus
     *
     * @var integer
     */
    protected $txRkwalertsSendStatus = 0;


    /**
     * Returns the txRkwalertsSendStatus
     *
     * @return integer
     */
    public function getTxRkwalertsSendStatus()
    {
        return $this->txRkwalertsSendStatus;
    }

    /**
     * Sets the txRkwalertsSendStatus
     *
     * @param integer $txRkwalertsSendStatus
     * @return void
     */
    public function setTxRkwalertsSendStatus($txRkwalertsSendStatus)
    {
        $this->txRkwalertsSendStatus = $txRkwalertsSendStatus;
    }

}