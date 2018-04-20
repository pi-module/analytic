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

use Pi\Form\Form as BaseForm;

class UserForm extends BaseForm
{
    public function __construct($name = null, $option = [])
    {
        $this->option = $option;
        parent::__construct($name);
    }

    public function getInputFilter()
    {
        if (!$this->filter) {
            $this->filter = new UserFilter($this->option);
        }
        return $this->filter;
    }

    public function init()
    {
        // Document images
        $this->add([
            'name'    => 'document_images',
            'type'    => 'Module\Media\Form\Element\Media',
            'options' => [
                'label'                    => __('Document images'),
                'required' => true,
            ],
        ]);
        // Save
        $this->add([
            'name'       => 'submit',
            'type'       => 'submit',
            'attributes' => [
                'value' => __('Save'),
                'class' => 'btn btn-primary',
            ],
        ]);
    }
}