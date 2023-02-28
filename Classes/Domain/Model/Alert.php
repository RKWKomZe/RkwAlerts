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

use SJBR\SrFreecap\Validation\Validator\CaptchaValidator;
use Madj2k\FeRegister\Domain\Model\FrontendUser;

/**
 * Class Alert
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright RKW Kompetenzzentrum
 * @package RKW_RkwAlerts
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Alert extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * frontendUser
     *
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     */
    protected ?FrontendUser $frontendUser = null;


    /**
     * project
     *
     * @var \RKW\RkwAlerts\Domain\Model\Project|null
     */
    protected ?Project $project = null;


    /**
     * @var string
     * @validate \SJBR\SrFreecap\Validation\Validator\CaptchaValidator
     */
    protected string $captchaResponse = '';


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


    /**
     * Sets the captchaResponse
     *
     * @param string $captchaResponse
     * @return void
     */
    public function setCaptchaResponse(string $captchaResponse): void {
        $this->captchaResponse = $captchaResponse;
    }


    /**
     * Getter for captchaResponse
     *
     * @return string
     */
    public function getCaptchaResponse(): string {
        return $this->captchaResponse;
    }

}
