<?php
class TransactionCurrency {
    private $name;
    private $value;
    private $category;

    private function __construct($name, $value, $category) {
        $this->name = $name;
        $this->value = $value;
        $this->category = $category;
    }

    public static function createReal($name, $value) {
        return new TransactionCurrency($name, $value, "r");
    }

    public static function createVirtual($name, $value) {
        return new TransactionCurrency($name, $value, "v");
    }

    public function getName() {
        return $this->name;
    }

    public function getValue() {
        return $this->value;
    }

    public function getCategory() {
        return $this->category;
    }
}
?>