<?php

namespace Core\Commandes\Admin;
use Core\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\utils\Config;

class SetShop extends PluginCommand
{
	/**
	 * @var Main
	 */
	private $plugin;

	public function __construct(string $name, Main $plugin)
	{
		parent::__construct($name, $plugin);
		parent::__construct($name, $plugin);
		$this->setPermission("op");
		$this->setAliases(["/ss"]);
		$this->setUsage("/setshop");
		$this->setDescription("définir le shop");
		$this->plugin = $plugin;
	}
	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if ($sender instanceof Player){
			$config = new Config($this->plugin->getDataFolder() . "Admin/" . "shop.yml", Config::YAML);
			$config->set($sender->getName(), "{$sender->getX()}_{$sender->getY()}_{$sender->getZ()}_{$sender->getLevel()->getName()}");
			$config->save();
			$sender->sendMessage("le spawn du shop a été corectement mis ");
		} else{
			$sender->sendMessage("tu ne peut pas utiliser de la console je te rappel ");
		}
	}
}