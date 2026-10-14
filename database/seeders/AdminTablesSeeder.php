<?php

namespace Database\Seeders;

use Dcat\Admin\Models;
use Illuminate\Database\Seeder;
use DB;

class AdminTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // base tables
        Models\Menu::truncate();
        Models\Menu::insert(
            [
                [
                    "id" => 1,
                    "parent_id" => 0,
                    "order" => 1,
                    "title" => "Index",
                    "icon" => "feather icon-bar-chart-2",
                    "uri" => "/",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => NULL
                ],
                [
                    "id" => 2,
                    "parent_id" => 0,
                    "order" => 9,
                    "title" => "Admin",
                    "icon" => "feather icon-settings",
                    "uri" => "",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 3,
                    "parent_id" => 2,
                    "order" => 10,
                    "title" => "Users",
                    "icon" => "",
                    "uri" => "auth/users",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 4,
                    "parent_id" => 2,
                    "order" => 11,
                    "title" => "Roles",
                    "icon" => "",
                    "uri" => "auth/roles",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 5,
                    "parent_id" => 2,
                    "order" => 12,
                    "title" => "Permission",
                    "icon" => "",
                    "uri" => "auth/permissions",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 6,
                    "parent_id" => 2,
                    "order" => 13,
                    "title" => "Menu",
                    "icon" => "",
                    "uri" => "auth/menu",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 7,
                    "parent_id" => 2,
                    "order" => 14,
                    "title" => "Extensions",
                    "icon" => "",
                    "uri" => "auth/extensions",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 8,
                    "parent_id" => 0,
                    "order" => 2,
                    "title" => "Member",
                    "icon" => "feather icon-users",
                    "uri" => "",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 9,
                    "parent_id" => 8,
                    "order" => 3,
                    "title" => "Member manage",
                    "icon" => "feather icon-user",
                    "uri" => "member-user",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 16,
                    "parent_id" => 2,
                    "order" => 15,
                    "title" => "Operation Log",
                    "icon" => "fa-file-text-o",
                    "uri" => "auth/operation-logs",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 17,
                    "parent_id" => 2,
                    "order" => 16,
                    "title" => "System Log",
                    "icon" => "fa-file-text-o",
                    "uri" => "auth/system-log-viewer",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 18,
                    "parent_id" => 0,
                    "order" => 4,
                    "title" => "Merchant",
                    "icon" => "fa-shopping-bag",
                    "uri" => NULL,
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 17:11:59",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 19,
                    "parent_id" => 18,
                    "order" => 5,
                    "title" => "merchant",
                    "icon" => "fa-align-justify",
                    "uri" => "merchant",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 17:11:59",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 20,
                    "parent_id" => 18,
                    "order" => 6,
                    "title" => "merchant-credit",
                    "icon" => "fa-money",
                    "uri" => "merchant-credit",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 17:11:59",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 21,
                    "parent_id" => 18,
                    "order" => 7,
                    "title" => "merchant-credit-log",
                    "icon" => "fa-list",
                    "uri" => "merchant-credit-log",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 17:11:59",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 22,
                    "parent_id" => 18,
                    "order" => 8,
                    "title" => "merchant-api-ip",
                    "icon" => "fa-shield",
                    "uri" => "merchant-api-ip",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-29 17:11:59",
                    "updated_at" => "2026-09-29 17:22:42"
                ],
                [
                    "id" => 23,
                    "parent_id" => 18,
                    "order" => 9,
                    "title" => "payment-order",
                    "icon" => "fa-list-alt",
                    "uri" => "payment-order",
                    "extension" => "",
                    "show" => 1,
                    "created_at" => "2026-09-30 11:51:18",
                    "updated_at" => "2026-09-30 11:51:18"
                ]
            ]
        );

        Models\Permission::truncate();
        Models\Permission::insert(
            [
                [
                    "id" => 1,
                    "name" => "Auth management",
                    "slug" => "auth-management",
                    "http_method" => "",
                    "http_path" => "",
                    "order" => 1,
                    "parent_id" => 0,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => NULL
                ],
                [
                    "id" => 2,
                    "name" => "Users",
                    "slug" => "users",
                    "http_method" => "",
                    "http_path" => "/auth/users*",
                    "order" => 2,
                    "parent_id" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => NULL
                ],
                [
                    "id" => 3,
                    "name" => "Roles",
                    "slug" => "roles",
                    "http_method" => "",
                    "http_path" => "/auth/roles*",
                    "order" => 3,
                    "parent_id" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => NULL
                ],
                [
                    "id" => 4,
                    "name" => "Permissions",
                    "slug" => "permissions",
                    "http_method" => "",
                    "http_path" => "/auth/permissions*",
                    "order" => 4,
                    "parent_id" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => NULL
                ],
                [
                    "id" => 5,
                    "name" => "Menu",
                    "slug" => "menu",
                    "http_method" => "",
                    "http_path" => "/auth/menu*",
                    "order" => 5,
                    "parent_id" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => NULL
                ],
                [
                    "id" => 6,
                    "name" => "Extension",
                    "slug" => "extension",
                    "http_method" => "",
                    "http_path" => "/auth/extensions*",
                    "order" => 6,
                    "parent_id" => 1,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => NULL
                ],
                [
                    "id" => 7,
                    "name" => "Member",
                    "slug" => "member",
                    "http_method" => "",
                    "http_path" => "/member-user*",
                    "order" => 7,
                    "parent_id" => 0,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => NULL
                ],
                [
                    "id" => 14,
                    "name" => "Operation Log",
                    "slug" => "operation-log",
                    "http_method" => "",
                    "http_path" => "/auth/operation-logs*",
                    "order" => 8,
                    "parent_id" => 0,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => "2026-09-29 17:40:11"
                ],
                [
                    "id" => 15,
                    "name" => "System Log",
                    "slug" => "system-log",
                    "http_method" => "",
                    "http_path" => "/auth/system-log-viewer*",
                    "order" => 9,
                    "parent_id" => 0,
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => "2026-09-29 17:40:11"
                ],
                [
                    "id" => 16,
                    "name" => "Merchant manage",
                    "slug" => "merchant.manage",
                    "http_method" => "",
                    "http_path" => "/merchant*,/merchant-credit*,/merchant-credit-log*,/merchant-api-ip*",
                    "order" => 10,
                    "parent_id" => 0,
                    "created_at" => "2026-09-29 17:11:59",
                    "updated_at" => "2026-09-29 17:40:11"
                ]
            ]
        );

        Models\Role::truncate();
        Models\Role::insert(
            [
                [
                    "id" => 1,
                    "name" => "Administrator",
                    "slug" => "administrator",
                    "created_at" => "2026-09-29 09:09:31",
                    "updated_at" => "2026-09-29 09:09:31"
                ]
            ]
        );

        Models\Setting::truncate();
		Models\Setting::insert(
			[
                [
                    "id" => 1,
                    "group_name" => "layout_config",
                    "slug" => "locale",
                    "value" => "zh_CN",
                    "created_at" => "2026-09-29 17:19:15",
                    "updated_at" => "2026-09-29 17:19:29"
                ],
                [
                    "id" => 2,
                    "group_name" => "layout_config",
                    "slug" => "menu_style",
                    "value" => "default_menu",
                    "created_at" => "2026-09-29 17:19:15",
                    "updated_at" => "2026-09-29 17:19:15"
                ],
                [
                    "id" => 3,
                    "group_name" => "layout_config",
                    "slug" => "horizontal_menu",
                    "value" => "",
                    "created_at" => "2026-09-29 17:19:15",
                    "updated_at" => "2026-09-29 17:19:15"
                ],
                [
                    "id" => 4,
                    "group_name" => "layout_config",
                    "slug" => "sidebar_collapsed",
                    "value" => "",
                    "created_at" => "2026-09-29 17:19:15",
                    "updated_at" => "2026-09-29 17:19:15"
                ],
                [
                    "id" => 5,
                    "group_name" => "layout_config",
                    "slug" => "dark_mode_switch",
                    "value" => "",
                    "created_at" => "2026-09-29 17:19:15",
                    "updated_at" => "2026-09-29 17:19:15"
                ],
                [
                    "id" => 6,
                    "group_name" => "layout_config",
                    "slug" => "full_screen",
                    "value" => "",
                    "created_at" => "2026-09-29 17:19:15",
                    "updated_at" => "2026-09-29 17:19:15"
                ],
                [
                    "id" => 7,
                    "group_name" => "layout_config",
                    "slug" => "show_locale_switch",
                    "value" => "1",
                    "created_at" => "2026-09-29 17:19:15",
                    "updated_at" => "2026-09-29 17:19:15"
                ],
                [
                    "id" => 8,
                    "group_name" => "layout_config",
                    "slug" => "show_help",
                    "value" => "",
                    "created_at" => "2026-09-29 17:19:15",
                    "updated_at" => "2026-09-29 17:19:15"
                ],
                [
                    "id" => 9,
                    "group_name" => "layout_config",
                    "slug" => "show_notification",
                    "value" => "",
                    "created_at" => "2026-09-29 17:19:15",
                    "updated_at" => "2026-09-29 17:19:15"
                ],
                [
                    "id" => 10,
                    "group_name" => "layout_config",
                    "slug" => "body_class",
                    "value" => "[]",
                    "created_at" => "2026-09-29 17:19:15",
                    "updated_at" => "2026-09-29 17:19:15"
                ],
                [
                    "id" => 11,
                    "group_name" => "layout_config",
                    "slug" => "color",
                    "value" => "default",
                    "created_at" => "2026-09-29 17:19:29",
                    "updated_at" => "2026-09-29 17:19:29"
                ],
                [
                    "id" => 12,
                    "group_name" => "layout_config",
                    "slug" => "sidebar_style",
                    "value" => "light",
                    "created_at" => "2026-09-29 17:19:29",
                    "updated_at" => "2026-09-29 17:19:29"
                ],
                [
                    "id" => 13,
                    "group_name" => "layout_config",
                    "slug" => "navbar_color",
                    "value" => "",
                    "created_at" => "2026-09-29 17:19:29",
                    "updated_at" => "2026-09-29 17:19:29"
                ],
                [
                    "id" => 14,
                    "group_name" => "layout_config",
                    "slug" => "home_url",
                    "value" => "http://localhost:8000",
                    "created_at" => "2026-09-29 17:19:29",
                    "updated_at" => "2026-09-29 17:19:29"
                ],
                [
                    "id" => 15,
                    "group_name" => "layout_config",
                    "slug" => "iframe_tabs",
                    "value" => "0",
                    "created_at" => "2026-09-29 17:19:29",
                    "updated_at" => "2026-09-29 17:19:29"
                ]
            ]
		);

		Models\Extension::truncate();
		Models\Extension::insert(
			[

            ]
		);

		Models\ExtensionHistory::truncate();
		Models\ExtensionHistory::insert(
			[

            ]
		);

        // pivot tables
        DB::table('admin_permission_menu')->truncate();
		DB::table('admin_permission_menu')->insert(
			[

            ]
		);

        DB::table('admin_role_menu')->truncate();
        DB::table('admin_role_menu')->insert(
            [
                [
                    "role_id" => 1,
                    "menu_id" => 18,
                    "created_at" => "2026-09-29 17:11:59",
                    "updated_at" => "2026-09-29 17:11:59"
                ],
                [
                    "role_id" => 1,
                    "menu_id" => 19,
                    "created_at" => "2026-09-29 17:11:59",
                    "updated_at" => "2026-09-29 17:11:59"
                ],
                [
                    "role_id" => 1,
                    "menu_id" => 20,
                    "created_at" => "2026-09-29 17:11:59",
                    "updated_at" => "2026-09-29 17:11:59"
                ],
                [
                    "role_id" => 1,
                    "menu_id" => 21,
                    "created_at" => "2026-09-29 17:11:59",
                    "updated_at" => "2026-09-29 17:11:59"
                ],
                [
                    "role_id" => 1,
                    "menu_id" => 22,
                    "created_at" => "2026-09-29 17:11:59",
                    "updated_at" => "2026-09-29 17:11:59"
                ],
                [
                    "role_id" => 1,
                    "menu_id" => 23,
                    "created_at" => "2026-09-30 11:51:18",
                    "updated_at" => "2026-09-30 11:51:18"
                ]
            ]
        );

        DB::table('admin_role_permissions')->truncate();
        DB::table('admin_role_permissions')->insert(
            [
                [
                    "role_id" => 1,
                    "permission_id" => 16,
                    "created_at" => "2026-09-29 17:11:59",
                    "updated_at" => "2026-09-29 17:11:59"
                ]
            ]
        );

        // finish
    }
}
