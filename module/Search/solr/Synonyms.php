<?php

namespace Search\Solr;

class Synonyms {
    
    public static $synonyms = array(
            'steakhouse' => 'steak house',
            'sea food' => 'seafood',
            'americano' => 'american',
            'b-b-q' => 'bbq',
            'b.b.q.' => 'bbq',
            'vegans' => 'vegan',
            'steak' => 'steaks',
            'teahouse' =>'tea house',
            'happyhour' =>'happy hour',
            'mexicano' =>'mexican',
            'mexicana' =>'mexican',
            'mexi' =>'mexican',
            'bagel' =>'bagels',
            'bars' =>'bar',
            'desserts' =>'dessert',
            'drinks' =>'beverages',
            'hotdog' =>'hot dogs',
            'hotdogs' =>'hot dogs',
            'hot dog' =>'hot dogs',
            'natural' =>'organic',
            'pizze' =>'pizza',
            'pubs' =>'pub',
            'vegetable' =>'vegetarian',
            'vegetables' =>'vegetarian',
    );
    
    public static function applySynonyFilter($text) {
        $key = trim(strtolower($text));
        if(isset(self::$synonyms[$key])){
          return self::$synonyms[$key];  
        }
        return $key;
    }
}