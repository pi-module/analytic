<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt New BSD License
 */

/**
 * @author Hossein Azizabadi <azizabadi@faragostaresh.com>
 */

return [
    'category' => [
        [
            'title' => _a('Admin'),
            'name'  => 'admin',
        ],
        [
            'title' => _a('Panel'),
            'name'  => 'panel',
        ],
    ],
    'item'     => [
        // Admin
        'admin_perpage'            => [
            'category'    => 'admin',
            'title'       => _a('Perpage'),
            'description' => '',
            'edit'        => 'text',
            'filter'      => 'number_int',
            'value'       => 25,
        ],
        // Panel
        'panel_admin'             => [
            'category'    => 'panel',
            'title'       => _a('Panel admin'),
            'description' => '',
            'edit'        => 'text',
            'filter'      => 'string',
            'value'       => 'panel',
        ],
    ],
];