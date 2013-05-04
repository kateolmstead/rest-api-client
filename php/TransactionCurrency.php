<?php
public class TransactionCurrencyCode{
    const FBC = "FBC";
    const USD = "USD";
    const OFD = "OFD";
    const OFF = "OFF";
}

public class TransactionCurrency(){

    private $name;
    private $value;
    private $category;

    private function __construct($name, $value, $category){
        $this->currency_code = $currency_code;
        $this->value = $value;
        $this->category = $category;
    }

    public static function createReal($name, $value){
        return new TransactionCurrency($currency_code, $value, "r");
    }

    public static function createVirtual($name, $value){
        return new TransactionCurrency($currency_name, $value, "v");
    }

    public function getName(){
        return $this->name;
    }

    public function getValue(){
        return $this->value;
    }

    public function getCategory(){
        return $category;
    }
}
?>