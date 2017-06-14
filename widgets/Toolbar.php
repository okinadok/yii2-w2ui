<?php
namespace paulosales\w2ui\widgets;

use yii\base\Widget;
use yii\helpers\Html;

/**
 * Menu displays a multi-level menu using w2ui library.
 *
 * The following example shows how to use Toolbar:
 *
 * @author Paulo Rogério Sales Santos <paulosales@gmail.com>
 * @since 1.1.0
 */
class Toolbar extends Widget
{
    public $options = [];
    /**
     * Renders the toolbar.
     */
    public function run()
    {
        $options = $this->options;
        if(!isset($options['style'])) {
            $options['style'] = 'padding: 4px; border: 1px solid #dfdfdf; border-radius: 3px';
        }
        echo Html::tag('div', '', $options);
    }

}
