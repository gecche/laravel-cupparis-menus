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
                        "path" => "/tab/user",
                        "permission" => "TAB_USER",
                    ],
                "Lingue" =>
                    [
                        "path" => "/adminlang",
                        "permission" => "ADMIN_LANG",
                    ],
                "News" =>
                    [
                        "path" => "/tab/news",
                        "permission" => "TAB_NEWS",
                    ],
                "Pagine" =>
                    [
                        "path" => "/tab/pagina",
                        "permission" => "TAB_PAGINA",
                    ],
                "Newsletter" =>
                    [
                        "path" => "/tab/newsletter",
                        "permission" => "TAB_NEWSLETTER",
                    ],
                "Iscritti alla newsletter" =>
                    [
                        "path" => "/list/newsletter_email",
                        "permission" => "LIST_NEWSLETTER_EMAIL",
                    ],
                "Tickets di supporto" =>
                    [
                        "path" => "/list/ticket",
                        "permission" => "LIST_TICKET",
                    ],
                "Tags" =>
                    [
                        "path" => "/tab/tag",
                        "permission" => "TAB_TAG",
                    ],
                "Faqs" =>
                    [
                        "path" => "/tab/faq",
                        "permission" => "TAB_FAQ",
                    ],
                "Calendario news" =>
                    [
                        "path" => "/calendar/news",
                        "permission" => "LIST_NEWS",
                    ],
                "Logs" =>
                    [
                        "path" => "/tab/log",
                        "permission" => "TAB_LOG",
                    ],
            ],
        ],
        'Superadmin' => [
            'items' => [
                "Roles" => [
                    "path" => "/tab/role",
                    "permission" => "TAB_ROLE",
                ],
                "Menus" => [
                    "path" => "/tab/menu",
                    "permission" => "TAB_MENU",
                ],
                "Menu items" => [
                    "path" => "/tab/menu_item",
                    "permission" => "TAB_MENU_ITEM",
                ],
                "Help" => [
                    "path" => "/test/help",
                    //"permission" => "TEST_HELP",
                ],
//		"HelpCore" => [
//			"path" => "file:///Volumes/MAC_CaseSensitive/htdocs/laravel-5-0/app/../resources/doxygen/help_core/html/index.html",
//            "permission" => "FILE:///VOLUMES/MAC_CASESENSITIVE/HTDOCS/LARAVEL-5-0/APP/../RESOURCES/DOXYGEN/HELP_CORE/HTML/INDEX.HTML",
//		],
//		"HelpCupparis" => [
//			"path" => "file:///Volumes/MAC_CaseSensitive/htdocs/laravel-5-0/app/../resources/jsdoc/index.html",
//            "permission" => "FILE:///VOLUMES/MAC_CASESENSITIVE/HTDOCS/LARAVEL-5-0/APP/../RESOURCES/JSDOC/INDEX.HTML",
//		],
            ],
        ],
        'Main' => [
            'titolo' => 'Menù principale',
            'items' => [
                "Faqs" => [
                    "path" => "/archivio/faq",
                    "permission" => "ARCHIVIO_FAQ",
                ],
                "TicketRequest" => [
                    "nome" => "Richiesta di supporto",
                    "path" => "/insert/ticket",
                    "permission" => "INSERT_TICKET",
                ],

            ],
        ],
    ],


    //standard, add
    'dynamic_menus' => [

    ],
);

