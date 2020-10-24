<?php

namespace Core\Commandes\Admin;

use Core\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Unfreeze extends PluginCommand
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
		$this->setDescription("unFreeze un joueur ");
		$this->plugin = $plugin;
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if($player instanceof Player){
			if($player->hasPermission('Staff')){
				if(!isset($args[0])){
					$player->sendMessage(TextFormat::RED . ' Usage: /unfreeze <player>');
				}else{
					$target = Server::getInstance()->getPlayer($args[0]);
					if(is_null($target)){
						$player->sendMessage(TextFormat::RED . 'Ce joueur n\'est pas connecté.');
					}else{
						if(isset($this->plugin->freeze[$target->getName()])){
							unset($this->plugin->freeze[$target->getName()]);
							Server::getInstance()->broadcastMessage(TextFormat::BLUE . ' Le joueur ' . TextFormat::GOLD . $target->getName() . TextFormat::BLUE . ' a été Unfreeze par ' . TextFormat::GOLD . $player->getName());
							$player->sendMessage('§2Le joueur a bien été Unfreeze.');
							$target->sendMessage('§1Vous avez été Unfreeze par §e' . $player->getName());
						}else{
							$player->sendMessage('§4Ce joueur n\'est pas Freeze.');
						}
					}
				}
			}else{
				$player->sendMessage(TextFormat::RED . 'Vous n\'avez pas la permission  d\'utiliser cette commande.');
			}
		}else{ $player->sendMessage(TextFormat::RED . 'Veuillez utiliser cette commande en jeu.'); }
	}
}