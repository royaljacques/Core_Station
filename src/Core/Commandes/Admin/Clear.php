<?php

namespace Core\Commandes\Admin;

use Core\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;

class Clear extends PluginCommand
{
	/**
	 * @var Main
	 */
	private $plugin;

	public function __construct(string $name,Main $plugin)
	{
		parent::__construct($name, $plugin);
		$this->setPermission("Staff");
		$this->setAliases(["/cr"]);
		$this->setUsage("/clear");
		$this->setDescription("suprimer tous l'inventaire d'un joueur");
		$this->plugin = $plugin;
	}
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool
	{
		if (isset($args[0])) {
			if ($sender->hasPermission("Staff")) {
				if (Main::getInstance()->getServer()->getPlayer($args[0]) != null) {
					$player = Main::getInstance()->getServer()->getPlayer($args[0]);
					$player->getArmorInventory()->clearAll();
					$player->getInventory()->clearAll();
					$sender->sendMessage("vous avez bien clear l'inventaire de " . $player->getName());
					$player->sendMessage("Ton inventaire vient d'être suprimer par  " . $sender->getName() . ".");
					return true;
				} else {
					$sender->sendMessage("je ne croit pas que la commande a été bien notée ");
					return true;
				}
			} else {
				$sender->sendMessage("tu n'as pas la permission , pourquoi éssaye tu ?");
				return true;
			}
		} else {
			$sender->sendMessage("/clear <Player>");
			return true;
		}
	}
}