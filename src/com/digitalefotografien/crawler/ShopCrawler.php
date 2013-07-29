<?php

namespace com\digitalefotografien\crawler;

abstract class ShopCrawler extends GenericCrawler { 
    private $source = null;
    
    public function setSource($url) {
        $this->source = $url;
    }
    
    protected $product_urls = array();
    public function filterProductUrls($cb) {
        $this->startTime("Loading Product-Urls...");
        if (is_file($this->source)) {
            $this->product_urls = $cb($fp = fopen($this->source, 'r'));
            fclose($fp);
        } else {
            $this->product_urls = $cb($this->client->request('GET', $this->source));
        }
        $this->endTime(count($this->product_urls));
    }
}
