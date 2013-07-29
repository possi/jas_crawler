<?php

namespace de\jaschastarke\crawler;
use Symfony\Component\DomCrawler\Crawler as SymfonyCrawler;

class DomCrawler extends SymfonyCrawler {
    protected $uri;
    /**
     * Constructor.
     *
     * @param mixed  $node A Node to use as the base for the crawling
     * @param string $uri  The current URI or the base href value
     *
     * @api
     */
    public function __construct($node = null, $uri = null) {
        $this->uri = $uri;
        parent::__construct($node, $uri);
    }

    public function getUri() {
        return $this->uri;
    }

    public function exportHTML() {
        return $this->n()->ownerDocument->saveHTML($this->n());
    }

    /**
     * Multi-use Iterator
     *
     * Mit übergebenen Clouse, wird die Funktion für jedes Element der Selektion aufgerufen. Im gegenteil zur Each-Funktion
     * hat diese jedoch dann keinen Rückgabewert. Außerdem werden keine DomNodes, sonden wiederrum Crawler-Objekte als
     * Parameter übergeben. Mit return === false, wird der ablauf abgebrochen;
     * Wird kein Closure übergeben, wird ein Iterator zurückgegeben, der
     * @param $cl Closure Optinaler Closure zum Durchlaufen der Elemente.
     * @return \Iterator|null
     */
    public function it(\Closure $closure = null) {
        if ($closure) {
            foreach ($this as $i => $node) {
                if (false === $closure(new staic($node, $this->uri), $i))
                    break;
            }
        } else {
            return new CrawlerIterator($this);
        }
    }

    public function getOffset($offset) {
        return new static($this->getNode($offset), $this->uri);
    }

    /**
     * @return DOMNode
     */
    public function getNode($position) {
        foreach ($this as $i => $node) {
            if ($i == $position) {
                return $node;
            }
        }

        return null;
    }

    /**
     * @return DOMNode
     */
    public function n() {
        return $this->getNode(0);
    }
}
