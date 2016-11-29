<?php

namespace TCG\Voyager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @todo: Refactor this class by using something like MenuBuilder Helper.
 */
class Menu extends Model
{
    protected $table = 'menus';

    public function items()
    {
        return $this->hasMany('MenuItem');
    }

    /**
     * Display menu.
     *
     * @param string      $menuName
     * @param string|null $type
     * @param array       $options
     *
     * @return string
     */
    public static function display($menuName, $type = null, $options = [])
    {
        // GET THE MENU
        $menu = static::where('name', '=', $menuName)->first();
        $menuItems = [];

        if (isset($menu->id)) {
            // GET THE ROOT MENU ITEMS
            $menuItems = MenuItem::where('menu_id', '=', $menu->id)
                ->where('parent_id', '=', null)
                ->orderBy('order', 'ASC')
                ->get();
        }

        // Convert options array into object
        $options = (object) $options;

        switch ($type) {
            case 'admin':
                return self::buildAdminOutput($menuItems, '', $options);

            case 'admin_menu':
                return self::buildAdminMenuOutput($menuItems, '', $options, request());

            case 'bootstrap':
                return self::buildBootstrapOutput($menuItems, '', $options, request());
        }

        return empty($type)
            ? self::buildOutput($menuItems, '', $options, request())
            : self::buildCustomOutput($menuItems, $type, $options, request());
    }

    /**
     * Create bootstrap menu.
     *
     * @param \Illuminate\Support\Collection|array $menuItems
     * @param string                               $output
     * @param object                               $options
     * @param \Illuminate\Http\Request             $request
     *
     * @return string
     */
    public static function buildBootstrapOutput($menuItems, $output, $options, Request $request)
    {
        if (empty($output)) {
            $output = '<ul class="nav navbar-nav">';
        } else {
            $output .= '<ul class="dropdown-menu">';
        }

        foreach ($menuItems as $item) {
            $li_class = '';
            $a_attrs = '';
            if ($request->is(ltrim($item->url, '/')) || $item->url == '/' && $request->is('/')) {
                $li_class = ' class="active"';
            }

            $children_menu_items = MenuItem::where('parent_id', '=', $item->id)->orderBy('order', 'ASC')->get();
            if (count($children_menu_items) > 0) {
                if ($li_class != '') {
                    $li_class = rtrim($li_class, '"').' dropdown"';
                } else {
                    $li_class = ' class="dropdown"';
                }
                $a_attrs = 'class="dropdown-toggle" ';
            }
            $icon = '';
            if (isset($options->icon) && $options->icon == true) {
                $icon = '<i class="'.$item->icon_class.'"></i>';
            }
            $styles = '';
            if (isset($options->color) && $options->color == true) {
                $styles = ' style="color:'.$item->color.'"';
            }

            if (isset($options->background) && $options->background == true) {
                $styles = ' style="background-color:'.$item->color.'"';
            }
            $output .= '<li'.$li_class.'><a '.$a_attrs.' href="'.$item->url.'" target="'.$item->target.'"'.$styles.'>'.$icon.'<span>'.$item->title.'</span></a>';

            if (count($children_menu_items) > 0) {
                $output = self::buildBootstrapOutput($children_menu_items, $output, $options, $request);
            }
            $output .= '</li>';
        }

        $output .= '</ul>';

        return $output;
    }

    /**
     * Create custom menu based on supplied view.
     *
     * @param \Illuminate\Support\Collection|array $menuItems
     * @param string                               $view
     * @param object                               $options
     * @param \Illuminate\Http\Request             $request
     *
     * @return string
     */
    public static function buildCustomOutput($menuItems, $view, $options, Request $request)
    {
        return view()->exists($view)
            ? view($view)->with('items', $menuItems)->render()
            : self::buildOutput($menuItems, '', $options, $request);
    }

    /**
     * Create default menu.
     *
     * @param \Illuminate\Support\Collection|array $menuItems
     * @param string                               $output
     * @param object                               $options
     * @param \Illuminate\Http\Request             $request
     *
     * @return string
     */
    public static function buildOutput($menuItems, $output, $options, Request $request)
    {
        if (empty($output)) {
            $output = '<ul>';
        } else {
            $output .= '<ul>';
        }

        foreach ($menuItems as $item) {
            $li_class = '';
            $a_attrs = '';
            if ($request->is(ltrim($item->url, '/')) || $item->url == '/' && $request->is('/')) {
                $li_class = ' class="active"';
            }

            $children_menu_items = MenuItem::where('parent_id', '=', $item->id)->orderBy('order', 'ASC')->get();

            $icon = '';
            if (isset($options->icon) && $options->icon == true) {
                $icon = '<i class="'.$item->icon_class.'"></i>';
            }

            $styles = '';
            if (isset($options->color) && $options->color == true) {
                $styles = ' style="color:'.$item->color.'"';
            }

            if (isset($options->background) && $options->background == true) {
                $styles = ' style="background-color:'.$item->color.'"';
            }

            $output .= '<li'.$li_class.'><a href="'.$item->url.'" target="'.$item->target.'"'.$styles.'>'.$icon.'<span>'.$item->title.'</span></a>';

            if (count($children_menu_items) > 0) {
                $output = self::buildOutput($children_menu_items, $output, $options, $request);
            }

            $output .= '</li>';
        }

        $output .= '</ul>';

        return $output;
    }

    /**
     * Create admin menu.
     *
     * @param \Illuminate\Support\Collection|array $menuItems
     * @param string                               $output
     * @param object                               $options
     * @param \Illuminate\Http\Request             $request
     *
     * @return string
     */
    public static function buildAdminMenuOutput($menuItems, $output, $options, Request $request)
    {
        $output .= '<ul class="nav navbar-nav">';

        foreach ($menuItems as $item) {
            $li_class = '';
            $a_attrs = '';
            $collapse_id = '';
            if ($request->is(ltrim($item->url, '/'))) {
                $li_class = ' class="active"';
            }

            $children_menu_items = MenuItem::where('parent_id', '=', $item->id)->orderBy('order', 'ASC')->get();

            if (count($children_menu_items) > 0) {
                if ($li_class != '') {
                    $li_class = rtrim($li_class, '"').' dropdown"';
                } else {
                    $li_class = ' class="dropdown"';
                }
                $collapse_id = Str::slug($item->title, '-').'-dropdown-element';
                $a_attrs = 'data-toggle="collapse" href="#'.$collapse_id.'"';
            } else {
                $a_attrs = 'href="'.$item->url.'"';
            }

            $output .= '<li'.$li_class.'><a '.$a_attrs.' target="'.$item->target.'">'
                .'<span class="icon '.$item->icon_class.'"></span>'
                .'<span class="title">'.$item->title.'</span></a>';


            if (count($children_menu_items) > 0) {
                // Add tag for collapse panel
                $output .= '<div id="'.$collapse_id.'" class="panel-collapse collapse"><div class="panel-body">';
                $output = self::buildAdminMenuOutput($children_menu_items, $output, [], $request);
                $output .= '</div></div>';      // close tag of collapse panel
            }

            $output .= '</li>';
        }

        return $output; // TODO: Check if is missing a closing ul tag!!
    }

    /**
     * Build admin menu.
     *
     * @param \Illuminate\Support\Collection|array $menuItems
     * @param string                               $output
     * @param object                               $options
     *
     * @return string
     */
    public static function buildAdminOutput($menuItems, $output, $options)
    {
        $output .= '<ol class="dd-list">';

        foreach ($menuItems as $item) {
            $output .= '<li class="dd-item" data-id="'.$item->id.'">';
            $output .= '<div class="pull-right item_actions">';
            $output .= '<div class="btn-sm btn-danger pull-right delete" data-id="'.$item->id.'"><i class="voyager-trash"></i> Delete</div>';
            $output .= '<div class="btn-sm btn-primary pull-right edit" data-id="'.$item->id.'" data-title="'.$item->title.'" data-url="'.$item->url.'" data-target="'.$item->target.'" data-icon_class="'.$item->icon_class.'" data-color="'.$item->color.'"><i class="voyager-edit"></i> Edit</div>';
            $output .= '</div>';
            $output .= '<div class="dd-handle">'.$item->title.' <small class="url">'.$item->url.'</small></div>';

            $children_menu_items = MenuItem::where('parent_id', '=', $item->id)->orderBy('order', 'ASC')->get();

            if (count($children_menu_items) > 0) {
                $output = self::buildAdminOutput($children_menu_items, $output, $options);
            }

            $output .= '</li>';
        }

        $output .= '</ol>';

        return $output;
    }
}