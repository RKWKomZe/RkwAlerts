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
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

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
     *
     *
     * @param $status
     * @param $table
     * @param $id
     * @param $fieldArray
     * @param $reference
     * @return void
     */
    function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, &$reference)
    {

        if ($table === 'pages' && $status === 'new') {

            if ($fieldArray['tx_rkwalerts_send_status']) {

                // do not copy that flag!
                $fieldArray['tx_rkwalerts_send_status'] = 0;
            }

        }
    }

}
