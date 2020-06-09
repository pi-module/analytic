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

namespace Module\Analytic\Form;

use Pi;
use Laminas\InputFilter\InputFilter;

class UserFilter extends InputFilter
{
    public function __construct($option = [])
    {
        // Main image
        $this->add(
            [
                'name'     => 'document_images',
                'required' => true,
            ]
        );
    }
}