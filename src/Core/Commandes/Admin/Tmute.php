<?php

namespace Core\Commandes\Admin;

use Core\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class Tmute extends PluginCommand
{
	public function __construct(string $name, Main $plugin)
	{
		parent::__construct($name, $plugin);
		$this->setPermission("Guide");
		$this->setAliases(["/tm"]);
		$this->setUsage("/tmute");
		$this->setDescription("Temp Mute un joueur ");
		$this->plugin = $plugin;
	}
	public function execute(CommandSender $player, string $commandLabel, array $args): bool
	{
		if (!$this->testPermission($player)){
			return true;
		}
		if (!$player instanceof Player){
			$player->sendMessage(TextFormat::GOLD . "tu ne peux pas utiliser le /Tmute de la console ");
			return true;
		}
		if(!$player->hasPermission("Guide")) {
			$player->sendMessage("tu n'as pas la permission d'utiliser cette commande ");
		}else{
			Main::getInstance()->openPlayerMuteListUI($player);
		}
		return true;
	}
}