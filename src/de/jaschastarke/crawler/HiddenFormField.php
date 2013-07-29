<?php

namespace de\jaschastarke\crawler;

use Symfony\Component\DomCrawler\Field\FormField;

class HiddenFormField extends FormField {
    public function __construct(\DOMNode $node = null, $name = null, $value = null) {
        if ($node != null) {
            parent::__construct($node);
        }
        if ($name != null) {
            $this->setName($name);
        }
        if ($value != null) {
            $this->setValue($value);
        }
    }
    public function setName($name) {
        $this->name = $name;
    }
    public function setValue($value) {
        $this->value = $value;
    }
    
    protected function initialize() {
        $this->disabled = $this->node->hasAttribute('disabled');
        $this->value = $this->node->getAttribute('value');
    }

    public function isDisabled() {
        return $this->disabled;
    }
}