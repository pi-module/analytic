<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt New BSD License
 */

/**
 * Module meta
 *
 * @author Hossein Azizabadi <azizabadi@faragostaresh.com>
 */

return [
    'admin' => [
        'summary'    => [
            'label'      => _a('Summary'),
            'route'      => 'admin',
            'controller' => 'index',
            'action'     => 'summary',
        ],
        'company' => [
            'label'      => _a('Company'),
            'route'      => 'admin',
            'controller' => 'index',
            'action'     => 'company',
        ],
        'user' => [
            'label'      => _a('User'),
            'route'      => 'admin',
            'controller' => 'index',
            'action'     => 'user',
        ],
    ],
];