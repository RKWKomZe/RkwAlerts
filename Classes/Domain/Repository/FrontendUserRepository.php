<?php

namespace RKW\RkwAlerts\Domain\Repository;

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
 * Class FrontendUserRepository
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendUserRepository extends \RKW\RkwRegistration\Domain\Repository\FrontendUserRepository
{

    /**
     * Finds users which have the given username OR email-address
     *
     * @param string $input
     * @return \RKW\RkwRegistration\Domain\Model\FrontendUser
     */
    public function findOneByEmailOrUsername($input)
    {

        $query = $this->createQuery();
        $result = $query->matching(
            $query->logicalOr(
                $query->equals('email', $input),
                $query->equals('username', $input)
            )
        )->setLimit(1)
            ->execute();

        return $result->getFirst();
    }

}