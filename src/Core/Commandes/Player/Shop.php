<?php

namespace Core\Commandes\Player;

use Core\Main;
use Core\Task\ShopTask;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

class Shop extends PluginCommand
{
	/**
	 * @var Main
	 */
	private $plugin;

	public function __construct(string $name, Main $plugin)
	{
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
	}
	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if ($sender instanceof Player){
			$this->plugin->getScheduler()->scheduleRepeatingTask(new ShopTask($this->plugin, $sender), 20);
		} else{
			$sender->sendMessage("tu ne peut pas utiliser de la console bg ");
		}
	}
}