<?php

namespace RKW\RkwAlerts\Alerts;

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
 * Class AlertManager
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Rkw Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AlertManager
{

    /**
     * alertRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\AlertRepository
     * @inject
     */
    protected $alertRepository;

    /**
     * pagesRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\PageRepository
     * @inject
     */
    protected $pageRepository;

    /**
     * projectsRepository
     *
     * @var \RKW\RkwAlerts\Domain\Repository\ProjectRepository
     * @inject
     */
    protected $projectRepository = null;



    /**
     * Gets the project of the given page-id and also checks if the page has a project set and
     * alerts are activated for that project
     *
     * @param int $pid The page uid
     * @return \RKW\RkwAlerts\Domain\Model\Project
     */
    public function getSubscribableProjectByPageUid(int $pid)
    {
        /**
         * @var $page \RKW\RkwAlerts\Domain\Model\Page
         * @var $projectTemp \RKW\RkwProjects\Domain\Model\Projects
         * @var $project \RKW\RkwAlerts\Domain\Model\Project
         */
        if (
            ($page = $this->pageRepository->findByIdentifier($pid))
            && ($projectTemp = $page->getTxRkwprojectsProjectUid())
            && ($project = $this->projectRepository->findByIdentifier($projectTemp->getUid()))
            && ($project->getTxRkwAlertsEnableAlerts())
        ) {
            return $project;
        }

        return null;
    }

    /**
     * Checks
     *
     * @param \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser
     * @param \RKW\RkwAlerts\Domain\Model\Project $project
     * @return bool
     */

    public function hasFeUserSubscribedToProject (
        \RKW\RkwRegistration\Domain\Model\FrontendUser $frontendUser,
        \RKW\RkwAlerts\Domain\Model\Project $project
    ): bool {

        if (
            ($frontendUser->getUid())
            && ($project->getUid())
            && ($this->alertRepository->findOneByFrontendUserAndProject($frontendUser, $project))
        ) {
            return true;
        }


        return false;
    }

}