<?php

namespace Core;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener
{
	public function onEnable()
	{
		@mkdir($this->getDataFolder());

	}
}