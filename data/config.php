<?php
return [
    'cacheTimestamp' => 1487547552,
    'database' => [
        'driver' => 'pdo_mysql',
        'dbname' => 'espocrm',
        'user' => 'root',
        'password' => '',
        'port' => '',
        'host' => 'localhost'
    ],
    'useCache' => true,
    'recordsPerPage' => 20,
    'recordsPerPageSmall' => 5,
    'applicationName' => 'EspoCRM',
    'version' => '4.4.1',
    'timeZone' => 'Europe/Moscow',
    'dateFormat' => 'DD.MM.YYYY',
    'timeFormat' => 'HH:mm',
    'weekStart' => 1,
    'thousandSeparator' => '',
    'decimalMark' => '.',
    'exportDelimiter' => ';',
    'currencyList' => [
        0 => 'RUB'
    ],
    'defaultCurrency' => 'RUB',
    'baseCurrency' => 'RUB',
    'currencyRates' => [
        
    ],
    'outboundEmailIsShared' => true,
    'outboundEmailFromName' => 'T800',
    'outboundEmailFromAddress' => 'no-reply@espocrm.dmigus.com',
    'smtpServer' => '',
    'smtpPort' => '25',
    'smtpAuth' => false,
    'smtpSecurity' => '',
    'smtpUsername' => '',
    'smtpPassword' => '',
    'languageList' => [
        0 => 'en_US',
        1 => 'cs_CZ',
        2 => 'de_DE',
        3 => 'es_ES',
        4 => 'fr_FR',
        5 => 'id_ID',
        6 => 'it_IT',
        7 => 'nl_NL',
        8 => 'tr_TR',
        9 => 'ro_RO',
        10 => 'ru_RU',
        11 => 'pl_PL',
        12 => 'pt_BR',
        13 => 'uk_UA',
        14 => 'vi_VN'
    ],
    'language' => 'ru_RU',
    'logger' => [
        'path' => 'data/logs/espo.log',
        'level' => 'WARNING',
        'rotation' => true,
        'maxFileNumber' => 30
    ],
    'authenticationMethod' => 'Espo',
    'globalSearchEntityList' => [
        0 => 'Account',
        1 => 'Contact',
        2 => 'Lead',
        3 => 'Opportunity'
    ],
    'tabList' => [
        0 => 'Account',
        1 => 'Contact',
        2 => 'Lead',
        3 => 'Opportunity',
        4 => 'Case',
        5 => 'Email',
        6 => 'Calendar',
        7 => 'Meeting',
        8 => 'Call',
        9 => 'Task',
        10 => '_delimiter_',
        11 => 'Document',
        12 => 'Campaign',
        13 => 'KnowledgeBaseArticle',
        14 => 'Stream',
        15 => 'User'
    ],
    'quickCreateList' => [
        0 => 'Account',
        1 => 'Contact',
        2 => 'Lead',
        3 => 'Opportunity',
        4 => 'Meeting',
        5 => 'Call',
        6 => 'Task',
        7 => 'Case',
        8 => 'Email'
    ],
    'exportDisabled' => false,
    'assignmentEmailNotifications' => false,
    'assignmentEmailNotificationsEntityList' => [
        0 => 'Lead',
        1 => 'Opportunity',
        2 => 'Task',
        3 => 'Case'
    ],
    'assignmentNotificationsEntityList' => [
        0 => 'Meeting',
        1 => 'Call',
        2 => 'Task',
        3 => 'Email'
    ],
    'portalStreamEmailNotifications' => true,
    'streamEmailNotificationsEntityList' => [
        0 => 'Case'
    ],
    'emailMessageMaxSize' => 10,
    'notificationsCheckInterval' => 10,
    'disabledCountQueryEntityList' => [
        0 => 'Email'
    ],
    'maxEmailAccountCount' => 2,
    'followCreatedEntities' => false,
    'b2cMode' => false,
    'restrictedMode' => false,
    'theme' => 'HazyblueVertical',
    'massEmailMaxPerHourCount' => 100,
    'personalEmailMaxPortionSize' => 10,
    'inboundEmailMaxPortionSize' => 20,
    'authTokenLifetime' => 0,
    'authTokenMaxIdleTime' => 120,
    'userNameRegularExpression' => '[^a-z0-9\\-@_\\.\\s]',
    'addressFormat' => 1,
    'displayListViewRecordCount' => true,
    'dashboardLayout' => [
        0 => (object) [
            'name' => 'My Espo',
            'layout' => [
                0 => (object) [
                    'id' => 'default-activities',
                    'name' => 'Activities',
                    'x' => 2,
                    'y' => 2,
                    'width' => 2,
                    'height' => 2
                ],
                1 => (object) [
                    'id' => 'default-stream',
                    'name' => 'Stream',
                    'x' => 0,
                    'y' => 0,
                    'width' => 2,
                    'height' => 4
                ],
                2 => (object) [
                    'id' => 'default-tasks',
                    'name' => 'Tasks',
                    'x' => 2,
                    'y' => 0,
                    'width' => 2,
                    'height' => 2
                ]
            ]
        ]
    ],
    'calendarEntityList' => [
        0 => 'Meeting',
        1 => 'Call',
        2 => 'Task'
    ],
    'activitiesEntityList' => [
        0 => 'Meeting',
        1 => 'Call'
    ],
    'historyEntityList' => [
        0 => 'Meeting',
        1 => 'Call',
        2 => 'Email'
    ],
    'isInstalled' => true,
    'siteUrl' => 'http://espocrm.dmigus.com',
    'passwordSalt' => '21b03f9295073fcb',
    'cryptKey' => '9582a330976000bfd5976e949d43d017'
];
?>