<?php

namespace Core\Event;

use Core\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;

class PlayerMooveEvent implements Listener
{

	private $c;

	public function __construct(Main $c)
	{
		$this->c = $c;
	}

	public function onMove(PlayerMoveEvent $e)
	{
		$player = $e->getPlayer();
		if (!$e->isCancelled()) {
			if (isset($this->c->freeze[$player->getName()])) {
				$e->setCancelled();
			}
		}
	}
}