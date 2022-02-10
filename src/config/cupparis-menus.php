<?php

return array(
    /*
      |--------------------------------------------------------------------------
      | Menus
      |--------------------------------------------------------------------------
      |
      | Menu array structure:
         <menuid> => [
            'titolo' => <titolo>, se vuoto prende <menuid>
            'icon' => <icon>, se vuoto null
            'items' =>
                [
                    <menuitemid> =>
                    [
                        'nome' => <nomeitem> se vuoto prende <menuitemid>
                        'path' => "/tab/user" "http://dffd.it",
                        'permission' => <permission> se vuoto null (visibile a tutti)
                                            se l'utente soddisfa il permesso allora l'elemento è visibile.
                                            In ogni caso questa proprietà non è controllata se esiste un metodo
                                            nella classe definita in checkerClass chiamato check<menuid><menuitemid>
                                            che restituisce true|false
                        'resource_id' => <permission> se vuoto null
                        'icon' => <iconitem> se vuoto null
                        'items' => array di sottoelementi con la stessa struttura
                    ],
        ]

      |
     */

    'default-icon' => 'fa-bar-chart-o',
    'default-path' => 'javascript:;',
    /*
     * STRUCTURE
     */

    'menu_data' => [
        'Admin' => [
            'items' => [
                "Users" =>
                    [
                        'nome' => 'Users',
                        "path" => "/manage/user",
                        "vuepath" => "c-manage?cModel=user"
                        //"permission" => "TAB_USER",
                    ],

            ],
        ],
        'Superadmin' => [
            'items' => [

            ],
        ],
        'Main' => [
            'titolo' => 'Menù principale',
            'items' => [
//                [
//                    'nome' => 'Anagrafica',
//                    "path" => "/manage/anagrafica",
//                    "vuepath" => "c-manage?cModel=anagrafica"
//                    //"permission" => "TAB_USER",
//                ],
            ],
        ],
    ],


    //standard, add
    'dynamic_menus' => [

    ],
);

