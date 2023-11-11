<?php

namespace RKW\RkwAlerts\Hooks;

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

use TYPO3\CMS\Core\SingletonInterface;

/**
 * DataHandler
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DataHandler implements SingletonInterface
{

    /**
     * processDatamap_postProcessFieldArray
     * Prevent copying alert send status
     *
     * @param string $status
     * @param string $table
     * @param string $id
     * @param array $fieldArray
     * @param object $reference
     * @return void
     */
    public function processDatamap_postProcessFieldArray(
        string $status,
        string $table,
        string $id,
        array &$fieldArray,
        object $reference
    ): void {

        if (
            $table === 'pages'
            && $status === 'new'
            && $fieldArray['tx_rkwalerts_send_status']
        ) {
            $fieldArray['tx_rkwalerts_send_status'] = 0;
        }

    }

}
