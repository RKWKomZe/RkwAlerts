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
     *
     * @param string $status
     * @param string $table
     * @param int $id
     * @param array $fieldArray
     * @param $reference
     * @return void
     */
    function processDatamap_postProcessFieldArray(string $status, string $table, int $id, array &$fieldArray, &$reference)
    {

        if ($table === 'pages' && $status === 'new') {

            // do not copy that flag!
            if ($fieldArray['tx_rkwalerts_send_status']) {
                $fieldArray['tx_rkwalerts_send_status'] = 0;
            }
        }
    }

}
