<?php

namespace com\digitalefotografien\crawler;
use de\jaschastarke\utils\Table;

class ShopExportCrawler extends ShopCrawler {
    public $target_file = "export.csv";

    private $parse_products = null;
    public function setParseProduct($cb) {
        $this->parse_products = $cb;
    }

    public $field_url = 'url';
    public $field_number = 'number';
    
    /**
     * Duplikats-Prüfung auf bereits geparste URLs.
     *
     * Wenn true, werden alle Produkte übersprungen, deren URL bereits geparsed wurde.
     * (berücksichtigt die index.csv)
     */
    public $dup_url = false;
    
    /**
     * Duplikats-Prüfung auf Arikel-Nummer.
     *
     * Wenn true, werden alle Produkte übersprungen, deren Artikel-Nummer bereits geparsed wurde.
     * (berücksichtigt die index.csv)
     */
    public $dup_number = false;

    public function __construct() {
        $this->data = new Table;
        parent::__construct();
    }

    public function run() {
        if (($this->dup_url || $this->dup_number) && $this->data->count() == 0)
            $this->loadData();
    
        foreach ($this->product_urls as $url) {
            if (is_array($url)) {
                $row = $url;
                $url = $row[$this->field_url];
            } else {
                $row = array(
                    $this->field_url => $url,
                );
            }
            if ($this->dup_url && in_array($url, $this->dups['url']))
                continue;
            if ($this->dup_number && isset($row[$this->field_number]) && in_array($row[$this->field_number], $this->dups['number']))
                continue;
            $this->startTime($url);
            $crawler = $this->client->request('GET', $url);
            $t = call_user_func($this->parse_products, $crawler);
            if ($this->dup_number && !isset($row[$this->field_number]) && in_array($t[$this->field_number], $this->dups['number']))
                continue;
            if (!$t) {
                continue;
                //if (!isset($row[$this->field_number]))
                //    $row[$this->field_number] = 'not found';
            } else {
                $row = array_merge($row, $t);
            }
            $this->addProduct($row);
            $this->endTime();
        }
        $this->store();
    }

    private $data = null;
    private $dups = array('number' => array(), 'url' => array());

    protected function loadData() {
        $this->data->readCSV($this->target_file);
        foreach ($this->data as $product) {
            if (isset($product[$this->field_number]))
                $this->dups['number'][] = $product[$this->field_number];
            $this->dups['url'][] = $product[$this->field_url]; 
        }
    }
    
    protected function addProduct($product) {
        $this->data->add($product);
        if (isset($product[$this->field_number]))
            $this->dups['number'][] = $product[$this->field_number];
        $this->dups['url'][] = $product[$this->field_url]; 
    }

    public function store() {
        $this->data->writeCSV($this->target_file);
    }
}
