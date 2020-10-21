<?php

namespace Core\market\database;

use Core\market\Market;

interface Database{

   public function __construct(Market $plugin, string $name);
  
   public function getName(): string;
}