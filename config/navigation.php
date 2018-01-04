<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt New BSD License
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