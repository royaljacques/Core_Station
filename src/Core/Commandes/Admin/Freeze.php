<?php

namespace Core\Commandes\Admin;

use Core\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Freeze extends PluginCommand
{
	/**
	 * @var Main
	 */
	private $plugin;

	public function __construct(string $name, Main $plugin)
	{
		parent::__construct($name, $plugin);
		$this->setPermission("Staff");
		$this->setAliases(["/f"]);
		$this->setUsage("/freeze");
		$this->setDescription("Freeze un joueur ");
		$this->plugin = $plugin;
	}

	public function execute(CommandSender $player, string $commandLabel, array $args)

	{
		if($player instanceof Player){
			if($player->hasPermission('Staff')){
				if(!isset($args[0])){
					$player->sendMessage(TextFormat::RED . ' Usage: /freeze <player>');
				}else{
					$target = Server::getInstance()->getPlayer($args[0]);
					if(is_null($target)){
						$player->sendMessage(TextFormat::RED . ' Le joueur spécifié n\'est pas en ligne.');
					}else {
						if (isset($this->plugin->freeze[$target->getName()])) {
							$player->sendMessage('§4Ce joueur est déjà freeze.');
						} else {
							$this->plugin->freeze[$target->getName()] = true;
							Server::getInstance()->broadcastMessage(TextFormat::BLUE . ' Le joueur ' . TextFormat::GOLD . $target->getName() . TextFormat::BLUE . ' a été Freeze par ' . TextFormat::GOLD . $player->getName());
							$player->sendMessage('§2Le joueur a bien été Freeze.');
							$target->sendMessage('§1Vous avez été Freeze par §e' . $player->getName());
						}
					}
				}
			}else{
				$player->sendMessage(TextFormat::RED . ' Vous n\'avez pas la permission d\'utiliser cette commande.');
			}
		}else{
			$player->sendMessage(TextFormat::RED . ' Veuillez utiliser cette commande en jeu.');
		}
	}
}