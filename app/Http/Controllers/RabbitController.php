<?php

namespace App\Http\Controllers;

use App\Jobs\CrawlRabbitJob;
use App\Listeners\SendLinkToQueueListener;
use Openbuildings\Spiderling\Page;

class RabbitController extends Controller
{
    public function crawl()
    {
        $selectors = [
            [
                'field' => 'name',
                'dom'   => 'h1 > span',
            ],
            [
                'field' => 'rating',
                'dom'   => '.product_meta div.star-rating > span > strong.rating',
            ],
            [
                'field' => 'product_code',
                'dom'   => 'span.sku_wrapper > span',
            ],
            [
                'field'     => 'product_brand',
                'dom'       => 'div.product-brands > a > img',
                'getBy'     => 'attribute',
                'attribute' => 'alt',
            ],
            [
                'field' => 'category',
                'dom'   => 'span.posted_in > a',
            ],
            [
                'field' => 'price',
                'dom'   => '.product_meta .woocommerce-Price-amount.amount',
            ],
            [
                'field' => 'sub_description',
                'dom'   => 'div.short-description > p:nth-child(1)',
            ],
            [
                'field'     => 'description',
                'dom'       => '.wpb_column.vc_column_container.vc_col-sm-8 .vc_column-inner .wpb_wrapper',
                'getBy'     => 'attribute',
                'attribute' => 'html',
            ],
            [
                'field'     => 'picture',
                'dom'       => 'li > a > img',
                'getBy'     => 'attribute',
                'findBy'    => 'all',
                'attribute' => 'src',
            ],
        ];
        $context = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
        for ($i = 2; $i <= 10; $i++) {
            $url = 'http://donghohaitrieu.com/product-sitemap' . $i . '.xml';
            $xml = file_get_contents($url, false, $context);
            $xml = simplexml_load_string($xml);
            $x = 1;
            foreach ($xml->children() as $xl) {
                $link = trim(str_replace('\n', '', (string)$xl->loc));
                //event(new SendLinkToQueueListener($link, $selectors));
                dispatch(new CrawlRabbitJob($link, $selectors));
                //dispatch(new CrawlRabbitJob($link, $selectors));
                echo "Ban event xong trang ".$i." link ". $x++ ."\n";
            }
        }
    }
}
