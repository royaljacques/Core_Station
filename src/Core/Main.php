<?php

namespace Core;

use Core\Commandes\Player\feed;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener
{
	private $instance;
	public function onEnable()
	{
		@mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder("Player/"));
		@mkdir($this->getDataFolder("Reward"));
		$this->NewLogger();
		$this->CommandesLoader();
	}

	/**
	 * @return mixed
	 */
	public function getInstance()
	{
		return $this->instance;
	}

	public function NewLogger()
	{
		$this->getServer()->getLogger()->info("Core Station c'est load correctement");
	}

	public function CommandesLoader()
	{
		$this->getServer()->getCommandMap()->register("feed", new Feed("feed", $this));
	}

}