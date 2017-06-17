<?php
namespace paulosales\w2ui\widgets;

use Yii;
use yii\base\Widget;
use yii\base\InvalidCallException;
use yii\helpers\Url;
use yii\helpers\Html;

/**
 * Menu displays a multi-level menu using w2ui library.
 *
 * The following example shows how to use Toolbar:
 *
 * @author Paulo Rogério Sales Santos <paulosales@gmail.com>
 * @since 1.0.0
 */
class Toolbar extends Widget
{
    public $options = [];

    public $items = [];

    public $name = null;

    public $tooltip = 'top|left';

    public $fixed = true;

    private $id = null;

    /**
     * @var bool whether the labels for menu items should be HTML-encoded.
     */
    public $encodeLabels = true;   

    /**
     * @var bool whether to activate parent menu items when one of the corresponding child menu items is active.
     * The activated parent menu items will also have its CSS classes appended with [[activeCssClass]].
     */
    public $activateParents = false; 

    /**
     * @var bool whether to automatically activate items according to whether their route setting
     * matches the currently requested route.
     * @see isItemActive()
     */
    public $activateItems = true;

    /**
     * @var string the route used to determine if a menu item is active or not.
     * If not set, it will use the route of the current request.
     * @see params
     * @see isItemActive()
     */
    public $route;

    /**
     * Renders the toolbar.
     */
    public function run()
    {
        if($this->name == null) {
            throw new InvalidCallException("Inform the Toolbar's name.");
        }
        $options = $this->options;
        if(!isset($options['id'])) {
            $options['id'] = $this->name;
        }
        $this->id = $options['id'];
        
        if(!isset($options['style'])) {
            $options['style'] = ($this->fixed?'position: fixed; right: 0; left: 0; ':'') . 'padding: 4px; border: 1px solid #dfdfdf; border-radius: 3px';
        }
        echo Html::tag('div', '', $options);

        $view = $this->getView();
        $items = $this->normalizeItems($this->items, $hasActiveChild);
        $view->registerJs(
"jQuery(function() {
    jQuery('#$this->id').w2toolbar({
        name: '$this->name',
        tooltip: '$this->tooltip',
        items: " . $this->renderItems($items) . ",
        onClick: function(e) {
            if(e.subItem && e.subItem.url) {
                window.location = e.subItem.url;
            }
            else if(e.item && e.item.url) {
                window.location = e.item.url;
            }
        }
    });
});");
    }

    protected function renderItems($items, $level = 0) {
        $itemsArray = [];
        foreach($items as $i => $item) {
            $itemsArray[] = $this->renderItem($item, $level);
        }
        $ident = "";
        $identClose = "";
        $ident = str_pad($ident, 12+4*$level, " ");
        $identClose = str_pad($identClose, 8+4*$level, " ");
        return "[\r\n$ident" . implode(",\r\n$ident", $itemsArray) . "\r\n$identClose]";
    }

    protected function renderItem($item, $level) {
        $jsonItem = "{text: '" . $item['label'] . "'";
        if(isset($item['icon'])) {
            $jsonItem .= ", icon: '" . $item['icon'] . "'";
        }
        if(isset($item['type'])) {
            $jsonItem .= ", type: '" . $item['type'] . "'";
        }
        if(isset($item['url'])) {
            $jsonItem .= ", url: '" . Html::encode(Url::to($item['url'])) . "'";
        }
        if(isset($item['items'])) {
            $jsonItem .= ", items: " . $this->renderItems($item['items'], $level+1);
        }
        if(isset($item['tooltip'])) {
            $jsonItem .= ", tooltip: '" . $item['tooltip'] . "'";
        }
        if(isset($item['count'])) {
            $jsonItem .= ", count: " . $item['count'];
        }
        if(isset($item['hidden'])) {
            $jsonItem .= ", hidden: " . ($item['hidden']?'true':'false');
        }
        if(isset($item['disabled'])) {
            $jsonItem .= ", disabled: " . ($item['disabled']?'true':'false');
        }
        if(isset($item['checked'])) {
            $jsonItem .= ", checked: " . ($item['checked']?'true':'false');
        }
        $jsonItem .= "}";
        return $jsonItem;
    }

    /**
     * Normalizes the [[items]] property to remove invisible items and activate certain items.
     * @param array $items the items to be normalized.
     * @param bool $active whether there is an active child menu item.
     * @return array the normalized menu items
     */
    protected function normalizeItems($items, &$active)
    {
        foreach ($items as $i => $item) {
            if (isset($item['visible']) && !$item['visible']) {
                unset($items[$i]);
                continue;
            }
            if (!isset($item['label'])) {
                $item['label'] = '';
            }
            $encodeLabel = isset($item['encode']) ? $item['encode'] : $this->encodeLabels;
            $items[$i]['label'] = $encodeLabel ? Html::encode($item['label']) : $item['label'];
            $hasActiveChild = false;
            if (isset($item['items'])) {
                $items[$i]['items'] = $this->normalizeItems($item['items'], $hasActiveChild);
                if (empty($items[$i]['items']) && $this->hideEmptyItems) {
                    unset($items[$i]['items']);
                    if (!isset($item['url'])) {
                        unset($items[$i]);
                        continue;
                    }
                }
            }
            if (!isset($item['active'])) {
                if ($this->activateParents && $hasActiveChild || $this->activateItems && $this->isItemActive($item)) {
                    $active = $items[$i]['active'] = true;
                } else {
                    $items[$i]['active'] = false;
                }
            } elseif ($item['active'] instanceof Closure) {
                $active = $items[$i]['active'] = call_user_func($item['active'], $item, $hasActiveChild, $this->isItemActive($item), $this);
            } elseif ($item['active']) {
                $active = true;
            }
        }

        return array_values($items);
    }

    /**
     * Checks whether a menu item is active.
     * This is done by checking if [[route]] and [[params]] match that specified in the `url` option of the menu item.
     * When the `url` option of a menu item is specified in terms of an array, its first element is treated
     * as the route for the item and the rest of the elements are the associated parameters.
     * Only when its route and parameters match [[route]] and [[params]], respectively, will a menu item
     * be considered active.
     * @param array $item the menu item to be checked
     * @return bool whether the menu item is active
     */
    protected function isItemActive($item)
    {
        if (isset($item['url']) && is_array($item['url']) && isset($item['url'][0])) {
            $route = Yii::getAlias($item['url'][0]);
            if ($route[0] !== '/' && Yii::$app->controller) {
                $route = Yii::$app->controller->module->getUniqueId() . '/' . $route;
            }
            if (ltrim($route, '/') !== $this->route) {
                return false;
            }
            unset($item['url']['#']);
            if (count($item['url']) > 1) {
                $params = $item['url'];
                unset($params[0]);
                foreach ($params as $name => $value) {
                    if ($value !== null && (!isset($this->params[$name]) || $this->params[$name] != $value)) {
                        return false;
                    }
                }
            }

            return true;
        }

        return false;
    }
}
