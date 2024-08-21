<?php
declare(strict_types = 1);

return [
    \RKW\RkwAlerts\Domain\Model\Project::class => [
        'tableName' => 'tx_rkwprojects_domain_model_projects',
    ],
    \RKW\RkwAlerts\Domain\Model\Page::class => [
        'tableName' => 'pages',
        'properties' => [
            'lastUpdated' => [
                'fieldName' => 'lastUpdated'
            ],
        ],
    ],
    \RKW\RkwAlerts\Domain\Model\FrontendUser::class => [
        'tableName' => 'fe_users',
    ],
];
