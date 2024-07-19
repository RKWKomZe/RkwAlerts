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

use Madj2k\CoreExtended\Utility\GeneralUtility;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Utility\FrontendUserUtility;
use RKW\RkwAlerts\Domain\Model\Project;
use RKW\RkwAlerts\Domain\Repository\ProjectRepository;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
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


    /**
     * @param array        $parameters
     * @param string       $table
     * @param int          $pageId
     * @param array        $additionalConstraints
     * @param array        $fieldList
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    public function modifyQuery(
        array        $parameters,
        string       $table,
        int          $pageId,
        array        $additionalConstraints,
        array        $fieldList,
        QueryBuilder $queryBuilder
    ) {


        // check if we want to modify the query -> check for table, route, module
        if (
            !is_null(GeneralUtility::_GP('route'))
            && GeneralUtility::_GP('route') == '/module/web/list'
            && !is_null(GeneralUtility::_GP('search_field'))
            && !is_null(GeneralUtility::_GP('table'))
            && GeneralUtility::_GP('table') == 'tx_rkwalerts_domain_model_alert')
        {

            $searchField = strtolower(GeneralUtility::_GP(('search_field')));

            // Either: check if term is email
            if (FrontendUserUtility::isEmailValid($searchField)) {

                /** @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository $frontendUserRepository */
                $frontendUserRepository = GeneralUtility::makeInstance(FrontendUserRepository::class);

                $frontendUser = $frontendUserRepository->findByEmail($searchField)->getFirst();

                if ($frontendUser instanceof FrontendUser) {

                    //search for elements that have a relation to this category
                    $queryBuilder->resetQueryPart('where');
                    $queryBuilder->andWhere($queryBuilder->expr()->in('frontend_user', [$frontendUser->getUid()]));
                }

            } else if (!empty($searchField)) {
                // Or: Check is given term is a project name

                // @toDo: Issue by any reason: Too few arguments to function TYPO3\CMS\Extbase\Persistence\Repository::__construct(), 0 passed in /var/www/html/public/typo3/sysext/core/Classes/Utility/GeneralUtility.php on line 3492 and exactly 1 expected
                /** @var \RKW\RkwAlerts\Domain\Repository\ProjectRepository $projectRepository */
           //     $projectRepository = GeneralUtility::makeInstance(ProjectRepository::class);


                /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
                $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
                /** @var \RKW\RkwAlerts\Domain\Repository\ProjectRepository $projectRepository */
                $projectRepository = $objectManager->get(ProjectRepository::class);

                $project = $projectRepository->findOneByNameOrShortName($searchField);

                if ($project instanceof Project) {

                    //search for elements that have a relation to this category
                    $queryBuilder->resetQueryPart('where');
                    $queryBuilder->andWhere($queryBuilder->expr()->in('project', [$project->getUid()]));
                }

            }


        }

    }



}
