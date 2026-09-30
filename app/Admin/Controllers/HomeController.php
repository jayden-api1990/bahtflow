<?php

namespace App\Admin\Controllers;

use App\Admin\Metrics\Examples;
use App\Http\Controllers\Controller;
use Dcat\Admin\Http\Controllers\Dashboard;
use Illuminate\Support\Facades\DB;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Linkbox;
use Dcat\Admin\Widgets\MediaList;
use Dcat\Admin\Widgets\Callout;
use Dcat\Admin\Widgets\Alert;
use Dcat\Admin\Widgets\ListGroup;
use Dcat\Admin\Widgets\InfoList;
use Dcat\Admin\Widgets\CoverCard;

class HomeController extends Controller
{
    public function index(Content $content)
        {

            $content =  $content
                ->row(function (Row $row) {

                })
                ->row(function (Row $row) {

                });

            return $content;
        }
}
