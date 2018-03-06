<?php
/**
 * Created by PhpStorm.
 * User: conghoan
 * Date: 02/08/2017
 * Time: 13:54
 */

namespace App\Colombo;


use App\Helpers\PhpUri;
use Carbon\Carbon;

class FilterHelper
{
    public static $time_list = [
        '1' => 'Today',
        '2' => 'Yesterday',
        '3' => 'This week',
        '4' => 'Last week',
        '5' => 'This month',
        '6' => 'Last month'

    ];

    public static function getStartEndTime($date_range){
        $dates = explode(' - ', $date_range);
        $start_date = date('Y-m-d 00:00:00', strtotime($dates[0]));
        $end_date = date('Y-m-d 23:59:59', strtotime($dates[1]));
        return [
            'start' => $start_date,
            'end' => $end_date
        ];
    }
    public static function parseIdDomain($sites){
        $sites = explode(",",$sites);
        $ids = [];
        $domains = [];
        foreach ($sites as $site){
            if(strpos($site, ".")){
                $domains[] = PhpUri::parse($site)->getUniDomain();
            }else{
                $ids[] = intval($site);
            }
        }
        return [$ids, $domains];
    }
}