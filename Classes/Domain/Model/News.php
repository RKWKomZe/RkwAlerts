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
 * Class News
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class News extends \GeorgRinger\News\Domain\Model\News
{

    /**
     * @var int
     */
    protected int $txRkwalertsSendStatus = 0;


    /**
     * Returns the txRkwalertsSendStatus
     *
     * @return int
     */
    public function getTxRkwalertsSendStatus(): int
    {
        return $this->txRkwalertsSendStatus;
    }

    /**
     * Sets the txRkwalertsSendStatus
     *
     * @param int $txRkwalertsSendStatus
     * @return void
     */
    public function setTxRkwalertsSendStatus(int $txRkwalertsSendStatus): void
    {
        $this->txRkwalertsSendStatus = $txRkwalertsSendStatus;
    }
}
