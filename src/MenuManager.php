<?php

namespace Cupparis\Menus;

use Cupparis\Acl\Contracts\AclContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
/*
 *
 use Cupparis\Menu\Models\Menu;
use Cupparis\Menu\Models\MenuItem;
use Cupparis\Menu\Models\AppVar;
*/

class MenuManager {

    //protected $acl = null;
    // Array dei menu di un user
    protected $menu_data = array();
    protected $page_paths = array();

    protected $models_namespace;

    protected $acl;

    protected $menuBuilder = null;
    protected $menuChecker = null;

    protected $breadCrumbs = "";
    protected $breadCrumbsLevel = 0;
    protected $breadCrumbsTitle = "";
    protected $breadCrumbsBuilded = 0;
    protected $breadCrumbsResolved = [];




    /**
     * Loads Session and configuration options.
     *
     * @return  void
     */
    public function __construct(AclContract $acl) {//AclManager $acl) {
        // Save the config in the object
        $this->acl = $acl;
        $this->menu_data = array();

        $this->models_namespace = Config::get('app.models_namespace',"\\App\\Models") . "\\";

        $menuBuilderClass = Config::get('menus.builder_class',null);
        if ($menuBuilderClass && class_exists($menuBuilderClass) && method_exists($menuBuilderClass,'buildMenus')) {
            $this->menuBuilder = new $menuBuilderClass();
        }

        $menuCheckerClass = Config::get('menus.checker_class',null);
        if ($menuCheckerClass && class_exists($menuCheckerClass)) {
            $this->menuChecker = new $menuCheckerClass();
        }
    }

    public function getMenuData($menuId = null,$force = false,$getEmpty = false) {

        if (!$this->menu_data || $force) {
            $this->setMenuData($force);
        }

        $menuIds = array_keys($this->menu_data);

        if ($menuId) {
            return [$menuId => $this->getSingleMenuData($menuId,$getEmpty,$force)];
        }

        //menu === null li prendo tutti
        $menuData = array();
        foreach ($menuIds as $currentMenuId) {
            $menuData[$currentMenuId] = $this->getSingleMenuData($currentMenuId,$getEmpty,$force);
        }

        return $menuData;
    }


    public function getSingleMenuData($menuId, $getEmpty = false, $force = false) {
        if (Session::has('menu_data.'.$menuId) && !$force) {
            //Check rebuild menu
            $rebuild_menu_session_value = Session::get('REBUILD_MENU_'.$menuId, null);
            $appVarModelName = $this->models_namespace . 'AppVar';
            $rebuild_menu_db_value = $appVarModelName::find('REBUILD_MENU')->value;

            if ($rebuild_menu_db_value < $rebuild_menu_session_value) {
                return Session::get('menu_data.'.$menuId,array());
            }
        }

        $menuArrayBuilded = array();

        $menuArray = array_get($this->menu_data,$menuId,[]);

        $titolo = $this->getLangField('titolo',$menuArray);
        $menuArrayBuilded = $menuArray;
        $menuArrayBuilded["id"] = $menuId;
        $menuArrayBuilded["titolo"] = $titolo ? $titolo : $menuId;
        $menuArrayBuilded["icon"] = array_get($menuArray,'icon','fa-bar-chart-o');
        $menuArrayBuilded["path"] = array_get($menuArray,'path','javascript:;');

        $menuArrayBuilded["items"] = array();
        $items = array_get($menuArray,'items',[]);
        foreach ($items as $menuItemId => $menuItem) {
            $menuItemBuilded = $this->getSingleMenuItem($menuId,$menuItemId,$menuItem);
            if ($menuItemBuilded) {
                $menuArrayBuilded["items"][$menuItemId] = $menuItemBuilded;
            }
        }



        if (!$getEmpty && count($menuArrayBuilded['items']) < 1) {
            $menuArrayBuilded = array();
        }

        Session::put('REBUILD_MENU_'.$menuId, Carbon::now()->toDateTimeString());
        Session::put('menu_data.'.$menuId, $menuArrayBuilded);
        return $menuArrayBuilded;


    }

    public function getSingleMenuItem($menuId, $menuItemId, $menuItemArray) {


        if (!$this->checkMenuItemPermission($menuId, $menuItemId, $menuItemArray)) {
            return array();
        }

        $menuItemArrayBuilded = array();
        $nome = $this->getLangField('nome',$menuItemArray);
        $menuItemArrayBuilded["nome"] = $nome ? $nome : $menuItemId;
        $menuItemArrayBuilded["nome_it"] = $nome ? $nome : $menuItemId;
        $menuItemArrayBuilded["icon"] = array_get($menuItemArray,'icon','fa-bar-chart-o');
        $menuItemArrayBuilded["path"] = array_get($menuItemArray,'path','javascript:;');

        $menuItemArrayBuilded["sub_items"] = array();

        foreach (array_get($menuItemArray,'items',[]) as $menuSubItemKey => $menuSubItemArray) {
            $subMenuItemBuilded = $this->getSingleMenuItem($menuId,$menuSubItemKey,$menuSubItemArray);
            if ($subMenuItemBuilded) {
                $menuItemArrayBuilded["sub_items"][$menuSubItemKey] = $subMenuItemBuilded;
            }
        }

        return $menuItemArrayBuilded;

    }


    protected function checkMenuItemPermission($menuId, $menuItemId, $menuItemArray) {

        $methodPermissionName = 'check'.studly_case($menuId).studly_case($menuItemId);
        if ($this->menuChecker && method_exists($this->menuChecker,$methodPermissionName)) {
            return $this->$methodPermissionName();
        }

        $permission = array_get($menuItemArray,'permission',null);
        if (!$permission) {
            return true;
        }

        $resourceId = array_get($menuItemArray,'resource_id',null);
        return $this->acl->check($permission,$resourceId);

    }

    public function setMenuData($force = false) {

        $sessionMenuData = Session::get('menu_data',false);
        if ($sessionMenuData && !$force) {
            $this->menu_data = $sessionMenuData;
            return;
        }

        $menuData = Config::get('menus.menu_data',[]);


        $dynamicMenuData = array();
        if ($this->menuBuilder) {

            $dynamicMenuData = $this->menuBuilder->buildMenus();
        }

        $menuData = array_merge($menuData,$dynamicMenuData);

        $this->menu_data = $menuData;

        $appVarModelName = $this->models_namespace . 'AppVar';
        $appVar = $appVarModelName::initializeVar('REBUILD_MENU',Carbon::now()->toDateTimeString());
        Session::put('menu_data',$menuData);

    }


    protected function getLangfield($field,$array,$default = null) {

        $localeField = $field . '_' . app()->getLocale();

        if (array_key_exists($localeField,$array)) {
            return $array[$localeField];
        }

        if (array_key_exists($field,$array)) {
            return $array[$field];
        }

        if (array_key_exists($default,$array)) {
            return $array[$default];
        }

        return $default;

    }

    public function setBreadcrumbs() {
        $this->breadCrumbsBuilded = 1;

        $requestPath = request()->path();
        $menuData = $this->getMenuData(null,true,true);

        $breadCrumbs = "";
        $breadCrumbsTitle = "";

        $trovatoLevel = 0;
        foreach ($menuData as $menuKey => $menu) {


            if ($this->breadCrumbsLevel == 0) {
                $path = array_get($menu,'path','');
                if ($this->checkActivePath($requestPath,$path)) {
                    $this->breadCrumbsLevel = 1;
                    $breadCrumbsTitle = array_get($menu,'titolo','');
                    $breadCrumbs = $menuKey;
                }
            }


            foreach (array_get($menu,'items',[]) as $menuItemKey => $menuItem) {

                if ($this->breadCrumbsLevel < 2) {
                    $levelPath = array_get($menuItem, 'path', '');

                    if ($this->checkActivePath($requestPath, $levelPath)) {
                        $this->breadCrumbsLevel = 2;

                        $breadCrumbs = $menuKey .'.' . $menuItemKey;
                        $breadCrumbsTitle = array_get($menuItem,'nome','');


                    }
                }


                foreach (array_get($menuItem,'sub_items',[]) as $menuSubItemKey => $menuSubItem) {

                    $level = 3;
                    $prefixKey = $menuKey . '.' . $menuItemKey;
                    $breadCrumbsRecursive = $this->_getBreadCrumbsLevel($menuSubItemKey, $menuSubItem, $prefixKey, $level,$requestPath);
                    if ($breadCrumbsRecursive) {
                        $breadCrumbs = $breadCrumbsRecursive['breadcrumbs'];
                        $breadCrumbsTitle = $breadCrumbsRecursive['breadcrumbs_title'];
                    }
                }


            }


            if ($breadCrumbs) {

                $breadCrumbsResolved = [];
                $menuBreadCrumbed = [];
                foreach (explode('.',$this->breadCrumbs) as $key => $breadCrumbLevel) {
                    if ($key == 0) {
                        $menuBreadCrumbed = array_get($this->menu_data,$breadCrumbLevel,[]);
                        if (count($menuBreadCrumbed) <= 0) {
                            break;
                        }
                        $breadCrumbsResolved[] = [
                            'link' => array_get($menuBreadCrumbed,'path','javascript:;'),
                            'title' => array_get($menuBreadCrumbed,'titolo',$breadCrumbLevel),
                        ];
                        continue;
                    }
                    $menuBreadCrumbed = array_get(array_get($menuBreadCrumbed,'items',[]),$breadCrumbLevel,[]);
                    if (count($menuBreadCrumbed) <= 0) {
                        break;
                    }
                    $breadCrumbsResolved[] = [
                        'link' => array_get($menuBreadCrumbed,'path','javascript:;'),
                        'title' => array_get($menuBreadCrumbed,'nome_'.app()->getLocale(),$breadCrumbLevel),
                    ];

                }

                $this->breadCrumbs = $breadCrumbs;
                $this->breadCrumbsTitle = $breadCrumbsTitle;
                $this->breadCrumbsResolved = $breadCrumbsResolved;
            }


        }


    }


    protected function _getBreadCrumbsLevel($menuItemKey,$menuItem,$prefixKey,$level,$requestPath) {

        $breadCrumbs = "";

        $prefixKey = $prefixKey . '.' . $menuItemKey;
        if ($this->breadCrumbsLevel < $level) {
            $levelPath = array_get($menuItem, 'path', '');

            if ($this->checkActivePath($requestPath, $levelPath)) {
                $this->breadCrumbsLevel = $level;

                $breadCrumbs = $prefixKey;
                $breadCrumbsTitle = array_get($menuItem,'nome',"");


            }
        }
        foreach (array_get($menuItem,'sub_items',[]) as $menuSubItemKey => $menuSubItem) {

            $breadCrumbsRecursive = $this->_getBreadCrumbsLevel($menuSubItemKey, $menuSubItem, $prefixKey, $level+1,$requestPath);
            if ($breadCrumbsRecursive) {
                $breadCrumbs = $breadCrumbsRecursive;
                $breadCrumbsTitle = array_get($menuSubItem,'nome',"");
            }
        }

        return ['breadcrumbs' => $breadCrumbs,'breadcrumbs_title' => $breadCrumbsTitle];

    }

    public function getBreadCrumbs() {
        if (!$this->breadCrumbsBuilded) {
            $this->setBreadcrumbs();
        }


        return [
            'breadcrumbs' => $this->breadCrumbs,
            'breadcrumbs_title' => $this->breadCrumbsTitle,
            'breadcrumbs_resolved' => $this->breadCrumbsResolved,
        ];
    }


    public function checkActivePath($requestPath,$path) {
        return ($path && starts_with($requestPath, trim($path,'/')));
    }



}
