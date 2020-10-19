<?php

use Core\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class Join implements Listener
{
	/**
	 * @var Main
	 */
	private $plugin;

	public function __construct(Main $plugin)
	{
		$this->plugin = $plugin;
	}
	public function OnJoin(PlayerJoinEvent $e)
	{
		$player = $e->getPlayer();
		if (!$player->hasPlayedBefore())
		{
			$player->sendMessage("Bienvenue dans le serveur");
		}
	}
}