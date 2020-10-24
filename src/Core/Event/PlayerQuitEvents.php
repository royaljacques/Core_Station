<?php

namespace Core\Event;

use Core\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;

class PlayerQuitEvents implements Listener {

    private $c;

    public function __construct(Main $c){
        $this->c = $c;
    }
    public function onQuit(PlayerQuitEvent $e){
        $player = $e->getPlayer();
        if(isset($this->c->freeze[$player->getName()])){
            $player->setBanned(true);
        }
    }
}
