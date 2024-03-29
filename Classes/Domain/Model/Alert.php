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

use Madj2k\CoreExtended\Domain\Model\AbstractCaptcha;
use Madj2k\FeRegister\Domain\Model\FrontendUser;

/**
 * Class Alert
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Alert extends AbstractCaptcha
{

    /**
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     */
    protected ?FrontendUser $frontendUser = null;


    /**
     * @var \RKW\RkwAlerts\Domain\Model\Project|null
     */
    protected ?Project $project = null;



    /**
     * Returns the frontendUser
     *
     * @return \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     */
    public function getFrontendUser():? FrontendUser
    {
        return $this->frontendUser;
    }


    /**
     * Sets the frontendUser
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @return void
     */
    public function setFrontendUser(\Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser): void
    {
        $this->frontendUser = $frontendUser;
    }


    /**
     * Returns the project
     *
     * @return \RKW\RkwAlerts\Domain\Model\Project $project
     */
    public function getProject():? Project
    {
        return $this->project;
    }


    /**
     * Sets the project
     *
     * @param \RKW\RkwAlerts\Domain\Model\Project $project
     * @return void
     */
    public function setProject(\RKW\RkwAlerts\Domain\Model\Project $project): void
    {
        $this->project = $project;
    }

}
