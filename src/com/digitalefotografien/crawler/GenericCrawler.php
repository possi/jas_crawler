<?php

namespace com\digitalefotografien\crawler;
use de\jaschastarke\crawler\CurlClient;

abstract class GenericCrawler {
    public $client = null;
    public function __construct() {
        $this->init();
    }
    protected function init() {
        set_time_limit(0);
        $this->client = new CurlClient();
    }
    abstract public function run();
    
    private $times = array();
    public function startTime($echo = null) {
        $this->times[] = microtime(1);
        if ($echo)
            echo $echo;
    }
    public function endTime($echo = null) {
        $t = (microtime(1) - array_pop($this->times)) * 1000;
        echo ($echo == null ? "" : " " . $echo) . " (".number_format($t, 2)."ms)\n";
    }
    public static function exportHtml($node) {
        if ($node instanceof \Symfony\Component\DomCrawler\Crawler)
            $node = $node->getNode(0);
        return $node->ownerDocument->saveHTML($node);
    }
}
