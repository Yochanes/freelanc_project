<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
  'id' => 'controlpanel',
  'name' => 'Autorazborka',
  'basePath' => dirname(__DIR__),
  'bootstrap' => ['log'],
  'language' => 'ru-RU',
  'aliases' => [
    '@bower' => '@vendor/bower-asset',
    '@npm' => '@vendor/npm-asset',
  ],
  'components' => [
    'components' => [
      'class' => 'app\assets\AppAsset',
      'assetManager' => [
        'linkAssets' => true
      ],
      'i18n' => [
        'translations' => [
            'app*' => [
                'class' => 'yii\i18n\PhpMessageSource',
                'basePath' => '@app/messages',
                'sourceLanguage' => 'en-US',
                'fileMap' => [
                    'app'       => 'app.php',
                ],
            ],
        ],
    ],
    ],
    'request' => [
      'cookieValidationKey' => md5('xtpfcfqn'),
      'baseUrl' => ''
    ],
    'cache' => [
      'class' => 'yii\caching\FileCache',
    ],
    'user' => [
      'identityClass' => 'app\models\User',
      'enableAutoLogin' => false,
    ],
    'errorHandler' => [
      'errorAction' => 'site/error',
    ],
    'mailer' => [
      'class' => 'yii\swiftmailer\Mailer',
      'useFileTransport' => false,
      'transport' => [
        'class' => 'Swift_SmtpTransport',
        'constructArgs' => ['localhost', 25],
        'plugins' => [
          [
            'class' => 'Swift_Plugins_ThrottlerPlugin',
            'constructArgs' => [20],
          ],
        ],
      ],
    ],
    'log' => [
      'traceLevel' => YII_DEBUG ? 3 : 0,
      'targets' => [
        [
          'class' => 'yii\log\FileTarget',
          'levels' => ['error', 'warning'],
        ],
      ],
    ],
    'db' => $db,
    'urlManager' => [
      'suffix' => '/',
      'enablePrettyUrl' => true,  // Disable r= routes
      'showScriptName' => false,  // Disable index.php
      'baseUrl' => '/',
      'rules' => [
        '' => 'site/index',
        [
          'route' => 'site/support',
          'pattern' =>'/site/support/<id:\d+>',
          'defaults' => ['id' => 0]
        ],
        '/site/question/<id:\d+>' => '/site/question',
        'sitemap.xml' => 'sitemap/index',
        'robots.txt' => 'robots/index',
        'vhod' => 'site/login',
        'prodat' => 'products/sell',
        'prodat/<group_url:\w+>' => 'products/sell',
        'user/<url:\w+>' => 'cabinet/profile',
        'user/<action:\w+>/<url:\w+>' => 'cabinet/<action>',
        'user/<action:\w+>/<group_url:\w+>/<url:\w+>' => 'cabinet/<action>',
        'company/<url:\w+>' => 'cabinet/profile',
        'company/<action:\w+>/<url:\w+>' => 'cabinet/<action>',
        'company/<action:\w+>/<group_url:\w+>/<url:\w+>' => 'cabinet/<action>',
        'controlpanel/<controller:\w+>' => 'controlpanel/<controller>/index',
        'controlpanel/<controller:\w+>/<action:\w+>' => 'controlpanel/<controller>/<action>',
        'controlpanel/<controller:\w+>/<action:\w+>/<url:\w*>' => 'controlpanel/<controller>/<action>',
        '<controller:(actions|cabinet|image|personal|robots|site|sitemap)>' => '<controller>/index',
        '<controller:(actions|cabinet|image|personal|robots|site|sitemap)>/<action:\w+>' => '<controller>/<action>',
        '<controller:(actions|cabinet|image|personal|robots|site|sitemap)>/<action>/<url:\w*>/' => '<controller>/<action>',
        '<controller:(actions|cabinet|image|personal|robots|site|sitemap)>/<action>/<url:\w*>/<id:\w*>' => '<controller>/<action>',
        'katalog/json' => 'catalog/json',
        'katalog/<url:.+>' => 'catalog/index',
        'zapros-na-poisk' => 'request/request',
        'zapros-na-poisk/<group_url:\w+>' => 'request/request',
        'noviy-zapros-na-poisk' => 'request/add',
        'noviy-zapros-na-poisk/<group_url:\w+>' => 'request/add',
        //'<url:.+>' => 'route/index',
        '<url:[^\/\s]+\/\S+\.(jpg|jpeg|png|gif)>' => 'image/vurl',
        '<url:[^\/\s]+\/\S+\/\d+>' => 'products/product',
        '<group_url:\w+>' => 'products/products',
        '<group_url:\w+>/<parameters:.+>' => 'products/products',
      ]
    ],
  ],
  'modules' => [
    'controlpanel' => [
      'class' => 'app\modules\controlpanel\module',
    ],
  ],
  'params' => $params,
];

if (YII_ENV_DEV) {
  // configuration adjustments for 'dev' environment
  $config['bootstrap'][] = 'debug';
  $config['modules']['debug'] = [
    'class' => 'yii\debug\Module',
    // uncomment the following to add your IP if you are not connecting from localhost.
    'allowedIPs' => ['82.207.48.34', '127.0.0.1', '::1'],
  ];

  $config['bootstrap'][] = 'gii';
  $config['modules']['gii'] = [
    'class' => 'yii\gii\Module',
    // uncomment the following to add your IP if you are not connecting from localhost.
    'allowedIPs' => ['82.207.48.34', '127.0.0.1', '::1'],
  ];
}

return $config;
