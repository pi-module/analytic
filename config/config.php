<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt New BSD License
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
        [
            'title' => _a('Image'),
            'name'  => 'image',
        ],
    ],
    'item'     => [
        // Admin
        'admin_perpage' => [
            'category'    => 'admin',
            'title'       => _a('Perpage'),
            'description' => '',
            'edit'        => 'text',
            'filter'      => 'number_int',
            'value'       => 25,
        ],
        // Panel
        'panel_admin'   => [
            'category'    => 'panel',
            'title'       => _a('Panel admin'),
            'description' => '',
            'edit'        => 'text',
            'filter'      => 'string',
            'value'       => 'panel',
        ],
        // Image
        'image_minw'    => [
            'category'    => 'image',
            'title'       => _t('Min Image width (upload)'),
            'description' => _t('This config can override media module value'),
            'edit'        => 'text',
            'filter'      => 'string',
            'value'       => '',
        ],
        'image_minh'    => [
            'category'    => 'image',
            'title'       => _t('Min Image height (upload)'),
            'description' => _t('This config can override media module value'),
            'edit'        => 'text',
            'filter'      => 'string',
            'value'       => '',
        ],
        'image_quality' => [
            'category'    => 'image',
            'title'       => _a('Image quality'),
            'description' => _a('Between 0 to 100 and support both of JPG and PNG, default is 75. This config can override media module value'),
            'edit'        => 'text',
            'filter'      => 'string',
            'value'       => 75,
        ],

        'image_size'      => [
            'category'    => 'image',
            'title'       => _a('Image Size'),
            'description' => '',
            'edit'        => 'text',
            'filter'      => 'number_int',
            'value'       => 1000000,
        ],
        'image_extension' => [
            'category'    => 'image',
            'title'       => _a('Image Extension'),
            'description' => '',
            'edit'        => 'text',
            'filter'      => 'string',
            'value'       => 'jpg,jpeg,png,gif',
        ],
        'image_largew'    => [
            'category'    => 'image',
            'title'       => _a('Large Image width'),
            'description' => '',
            'edit'        => 'text',
            'filter'      => 'number_int',
            'value'       => 1200,
        ],
        'image_largeh'    => [
            'category'    => 'image',
            'title'       => _a('Large Image height'),
            'description' => '',
            'edit'        => 'text',
            'filter'      => 'number_int',
            'value'       => 1200,
        ],
        'image_itemw'     => [
            'category'    => 'image',
            'title'       => _a('Item Image width'),
            'description' => '',
            'edit'        => 'text',
            'filter'      => 'number_int',
            'value'       => 800,
        ],
        'image_itemh'     => [
            'category'    => 'image',
            'title'       => _a('Item Image height'),
            'description' => '',
            'edit'        => 'text',
            'filter'      => 'number_int',
            'value'       => 800,
        ],
        'image_mediumw'   => [
            'category'    => 'image',
            'title'       => _a('Medium Image width'),
            'description' => '',
            'edit'        => 'text',
            'filter'      => 'number_int',
            'value'       => 500,
        ],
        'image_mediumh'   => [
            'category'    => 'image',
            'title'       => _a('Medium Image height'),
            'description' => '',
            'edit'        => 'text',
            'filter'      => 'number_int',
            'value'       => 500,
        ],
        'image_thumbw'    => [
            'category'    => 'image',
            'title'       => _a('Thumb Image width'),
            'description' => '',
            'edit'        => 'text',
            'filter'      => 'number_int',
            'value'       => 250,
        ],
        'image_thumbh'    => [
            'category'    => 'image',
            'title'       => _a('Thumb Image height'),
            'description' => '',
            'edit'        => 'text',
            'filter'      => 'number_int',
            'value'       => 250,
        ],
    ],
];