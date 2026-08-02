<?php
/**
 * TbGridView class file.
 * @author Antonio Ramirez <ramirez.cobos@gmail.com>
 * @author Christoffer Niska <ChristofferNiska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package bootstrap.widgets
 */

Yii::import('zii.widgets.grid.CGridView');

/**
 * Bootstrap Zii grid view.
 */
class TbGridView extends CGridView
{
    /**
     * @var string|array the table style.
     * Valid values are TbHtml::GRID_TYPE_STRIPED, TbHtml::GRID_TYPE_BORDERED, TbHtml::GRID_TYPE_CONDENSED and/or
     * TbHtml::GRID_TYPE_HOVER.
     * Defaults to array(TbHtml::GRID_TYPE_STRIPED, TbHtml::GRID_TYPE_HOVER) when not set.
     */
    public $type;
    /**
     * @var array the configuration for the pager.
     * Defaults to <code>array('class'=>'ext.bootstrap.widgets.TbPager')</code>.
     */
    public $pager = array('class' => 'bootstrap.widgets.TbPager');
    /**
     * @var string the URL of the CSS file used by this grid view.
     * Defaults to false, meaning that no CSS will be included.
     */
    public $cssFile = false;
    /**
     * @var string the template to be used to control the layout of various sections in the view.
     * .table-responsive wraps the table in a horizontally-scrollable box —
     * without it, a wide grid (many columns) forces its flex column wider
     * than intended instead of scrolling, bleeding into whatever sits next
     * to it (e.g. the Operations sidebar in column2.php). row-fluid/span6
     * (BS2) replaced with BS5 flex utilities for the pager+summary line.
     */
    public $template = "<div class=\"table-responsive\">{items}</div>\n<div class=\"d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2\">{pager}{summary}</div>";

    /**
     * Initializes the widget.
     */
    public function init()
    {
        parent::init();
        if (empty($this->type)) {
            // Every admin grid gets zebra striping + row hover by default so
            // the CRUD screens read consistently instead of each view having
            // to opt in with its own 'type' => 'striped hover'. A view can
            // still override this by setting its own 'type' explicitly.
            $this->type = array(TbHtml::GRID_TYPE_STRIPED, TbHtml::GRID_TYPE_HOVER);
        }
        if (is_string($this->type)) {
            $this->type = explode(' ', $this->type);
        }
        $classes = array('table');
        // BS5 renamed table-condensed to table-sm; every other
        // TbHtml::GRID_TYPE_* value (striped/bordered/hover) is unchanged.
        foreach ($this->type as $type) {
            $classes[] = 'table-' . ($type === 'condensed' ? 'sm' : $type);
        }
        $classes = implode(' ', $classes);
        if (isset($this->itemsCssClass)) {
            $this->itemsCssClass .= ' ' . $classes;
        } else {
            $this->itemsCssClass = $classes;
        }
    }

    /**
     * Creates column objects and initializes them.
     */
    protected function initColumns()
    {
        foreach ($this->columns as $i => $column) {
            if (is_array($column) && !isset($column['class'])) {
                $this->columns[$i]['class'] = 'bootstrap.widgets.TbDataColumn';
            }
        }
        parent::initColumns();
    }

    /**
     * Creates a column based on a shortcut column specification string.
     * @param mixed $text the column specification string
     * @return \TbDataColumn|\CDataColumn the column instance
     * @throws CException if the column format is incorrect
     */
    protected function createDataColumn($text)
    {
        if (!preg_match('/^([\w\.]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
            throw new CException(Yii::t(
                'zii',
                'The column must be specified in the format of "Name:Type:Label", where "Type" and "Label" are optional.'
            ));
        }
        $column = new TbDataColumn($this);
        $column->name = $matches[1];
        if (isset($matches[3]) && $matches[3] !== '') {
            $column->type = $matches[3];
        }
        if (isset($matches[5])) {
            $column->header = $matches[5];
        }
        return $column;
    }
}
