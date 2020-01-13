<?php

namespace Gecche\Cupparis\Menus;

use Cupparis\Acl\Contracts\AclContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Support\Arr;
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

    protected $config = null;

    protected $menu_data = [];
    protected $page_paths = [];

    protected $models_namespace;

    protected $user;
    protected $userResolver = null;

    protected $menuBuilder = null;

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
    public function __construct(callable $userResolver) {//AclManager $acl) {
        // Save the config in the object

        $this->userResolver = $userResolver;
        $this->config = Config::get('menus',[]);
        $this->menu_data = [];

        $this->models_namespace = Config::get('app.models_namespace',"\\App\\Models") . "\\";


    }


    public function setMenuBuilder(MenuBuilder $menuBuilder) {

        $this->menuBuilder = $menuBuilder;

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
        $menuData = [];
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
                return Session::get('menu_data.'.$menuId,[]);
            }
        }


        $menuArray = Arr::get($this->menu_data,$menuId,[]);

        $menuArrayBuilded = $menuArray;
        $menuArrayBuilded["id"] = $menuId;
        $menuArrayBuilded["titolo"] = Arr::get($menuArray,'titolo',$menuId);
        $menuArrayBuilded["icon"] = Arr::get($menuArray,'icon',Arr::get($this->config,'default-icon'));
        $menuArrayBuilded["path"] = Arr::get($menuArray,'path',Arr::get($this->config,'default-path'));

        $menuArrayBuilded["items"] = [];
        $items = Arr::get($menuArray,'items',[]);
        foreach ($items as $menuItemId => $menuItem) {
            $menuItemBuilded = $this->getSingleMenuItem($menuId,$menuItemId,$menuItem);
            if ($menuItemBuilded) {
                $menuArrayBuilded["items"][$menuItemId] = $menuItemBuilded;
            }
        }



        if (!$getEmpty && count($menuArrayBuilded['items']) < 1) {
            $menuArrayBuilded = [];
        }

        Session::put('REBUILD_MENU_'.$menuId, Carbon::now()->toDateTimeString());
        Session::put('menu_data.'.$menuId, $menuArrayBuilded);
        return $menuArrayBuilded;


    }

    public function getSingleMenuItem($menuId, $menuItemId, $menuItemArray) {


        if (!$this->checkMenuItemPermission($menuItemArray)) {
            return [];
        }

        $menuItemArrayBuilded = [];
        $menuItemArrayBuilded["nome"] = Arr::get($menuItemArray,'nome',$menuItemId);
        $menuItemArrayBuilded["icon"] = Arr::get($menuItemArray,'icon',Arr::get($this->config,'default-icon'));
        $menuItemArrayBuilded["path"] = Arr::get($menuItemArray,'path',Arr::get($this->config,'default-path'));

        $menuItemArrayBuilded["sub_items"] = [];

        foreach (Arr::get($menuItemArray,'items',[]) as $menuSubItemKey => $menuSubItemArray) {
            $subMenuItemBuilded = $this->getSingleMenuItem($menuId,$menuSubItemKey,$menuSubItemArray);
            if ($subMenuItemBuilded) {
                $menuItemArrayBuilded["sub_items"][$menuSubItemKey] = $subMenuItemBuilded;
            }
        }

        return $menuItemArrayBuilded;

    }


    protected function checkMenuItemPermission($menuItemArray) {


        $permission = Arr::get($menuItemArray,'permission',null);
        if (!$permission) {
            return true;
        }
        $permissionArray = explode('|',$permission);
        $permissionName = Arr::pull($permissionArray,0);

        $arguments = [];



        foreach ($permissionArray as $argument) {

            $argumentArray = explode(':',$argument);
            if (count($argumentArray) < 2) {
                $arguments[] = $argument;
                continue;
            }
            list($modelClass,$modelId) = $argumentArray;
            $arguments[] = $modelClass::find($modelId);

        }


        return $this->user->can($permissionName,$arguments);

    }

    public function setMenuData($force = false) {

        $sessionMenuData = Session::get('menu_data',false);
        if ($sessionMenuData && !$force) {
            $this->menu_data = $sessionMenuData;
            return;
        }

        $menuData = Arr::get($this->config,'menu_data',[]);


        $dynamicMenuData = [];
        if ($this->menuBuilder) {
            $dynamicMenuData = $this->menuBuilder->buildMenus();
        }

        $menuData = array_merge($menuData,$dynamicMenuData);

        $this->menu_data = $menuData;

        $appVarModelName = $this->models_namespace . 'AppVar';
        $appVar = $appVarModelName::initializeVar('REBUILD_MENU',Carbon::now()->toDateTimeString());
        Session::put('menu_data',$menuData);

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
                $path = Arr::get($menu,'path','');
                if ($this->checkActivePath($requestPath,$path)) {
                    $this->breadCrumbsLevel = 1;
                    $breadCrumbsTitle = Arr::get($menu,'titolo','');
                    $breadCrumbs = $menuKey;
                }
            }


            foreach (Arr::get($menu,'items',[]) as $menuItemKey => $menuItem) {

                if ($this->breadCrumbsLevel < 2) {
                    $levelPath = Arr::get($menuItem, 'path', '');

                    if ($this->checkActivePath($requestPath, $levelPath)) {
                        $this->breadCrumbsLevel = 2;

                        $breadCrumbs = $menuKey .'.' . $menuItemKey;
                        $breadCrumbsTitle = Arr::get($menuItem,'nome','');


                    }
                }


                foreach (Arr::get($menuItem,'sub_items',[]) as $menuSubItemKey => $menuSubItem) {

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
                        $menuBreadCrumbed = Arr::get($this->menu_data,$breadCrumbLevel,[]);
                        if (count($menuBreadCrumbed) <= 0) {
                            break;
                        }
                        $breadCrumbsResolved[] = [
                            'link' => Arr::get($menuBreadCrumbed,'path',Arr::get($this->config,'default-path')),
                            'title' => Arr::get($menuBreadCrumbed,'titolo',$breadCrumbLevel),
                        ];
                        continue;
                    }
                    $menuBreadCrumbed = Arr::get(Arr::get($menuBreadCrumbed,'items',[]),$breadCrumbLevel,[]);
                    if (count($menuBreadCrumbed) <= 0) {
                        break;
                    }
                    $breadCrumbsResolved[] = [
                        'link' => Arr::get($menuBreadCrumbed,'path',Arr::get($this->config,'default-path')),
                        'title' => Arr::get($menuBreadCrumbed,'nome',$breadCrumbLevel),
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
            $levelPath = Arr::get($menuItem, 'path', '');

            if ($this->checkActivePath($requestPath, $levelPath)) {
                $this->breadCrumbsLevel = $level;

                $breadCrumbs = $prefixKey;
                $breadCrumbsTitle = Arr::get($menuItem,'nome',"");


            }
        }
        foreach (Arr::get($menuItem,'sub_items',[]) as $menuSubItemKey => $menuSubItem) {

            $breadCrumbsRecursive = $this->_getBreadCrumbsLevel($menuSubItemKey, $menuSubItem, $prefixKey, $level+1,$requestPath);
            if ($breadCrumbsRecursive) {
                $breadCrumbs = $breadCrumbsRecursive;
                $breadCrumbsTitle = Arr::get($menuSubItem,'nome',"");
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


    /**
     * Resolve the user from the user resolver.
     *
     * @return mixed
     */
    protected function resolveUser()
    {
        return call_user_func($this->userResolver);
    }
}
