<?php
namespace Core\Commandes\Admin;

use Core\API\FormAPI\SimpleForm;
use Core\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Admin extends PluginCommand
{
	/**
	 * @var Plugin
	 */
	private $plugin;

	public function __construct(string $name, Plugin $plugin)
	{
		parent::__construct($name, $plugin);
		$this->setPermission("Staff");
		$this->setAliases(["/ad"]);
		$this->setUsage("/feed");
		$this->setDescription("Ouvrire l'interface Admins");
		$this->plugin = $plugin;
	}
	public function execute(CommandSender $player, string $commandLabel, array $args): bool
	{
		if (!$this->testPermission($player)){
			return true;
		}
		if (!$player instanceof Player){
			$player->sendMessage(TextFormat::GOLD . "tu ne peux pas utiliser le /Admin de la console ");
			return true;
		}
		if(!$player->hasPermission("Staff")) {
			$player->sendMessage("tu n'as pas la permission d'utiliser cette commande ");
		}else{
			$this->AdminIndexForm($player);
		}
		return true;
	}

	public function AdminIndexForm($player){
		Server::getInstance()->getPluginManager()->getPlugin("EconomyAPI");
		$form = new SimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return true;
			}
			switch($result) {
				case 0:
					$player->hasPermission("Guide");
					$this->GuideForm($player);
					break;
				case 1:
					$player->hasPermission("Modo");
					$this->ModoForm($player);
					break;
				case 2:
					$player->hasPermission("Super_Modo");
					$this->SuperModoForm($player);
					break;
				case 3:
					$player->hasPermission("Op");
					$this->OpForm($player);
					break;

			}
			return true;
		});
		$form->setTitle("Index des Staffs ");
		$form->addButton("§1§l✶ Guides ✶");
		$form->addButton("§1§l✶ Modérateur ✶");
		$form->addButton("§1§l✶ Super-modérateur ✶");
		$form->addButton("§1§l✶ Op ✶");
		$form->sendToPlayer($player);

	}

	public function GuideForm($player)
	{
	}

	public function ModoForm($player)
	{
		$form = new SimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return true;
			}
			switch($result) {
				case 0:
					$this->plugin->openPlayerListUI($player);
					break;
				case 1:
					$this->plugin->openTcheckUI($player);
					break;
			}
			return true;
		});
		$form->setTitle("Modérateur");
		$form->addButton("TbanUi");
		$form->addButton("TcheckUi");
		$form->sendToPlayer($player);
	}

	public function SuperModoForm($player)
	{
		$form = new SimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return true;
			}
			switch($result) {
				case 0:
					$this->plugin->openPlayerListUI($player);
					break;
				case 1:
					$this->plugin->openTcheckUI($player);
					break;
			}
			return true;
		});
		$form->setTitle("Modérateur");
		$form->addButton("TbanUi");
		$form->addButton("TcheckUi");
		$form->sendToPlayer($player);
	}

	public function OpForm($player)
	{
		$form = new SimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return true;
			}
			switch($result) {
				case 0:
					$this->plugin->openPlayerListUI($player);
					break;
				case 1:
					$this->plugin->openTcheckUI($player);
					break;
			}
			return true;
		});
		$form->setTitle("Modérateur");
		$form->addButton("TbanUi");
		$form->addButton("TcheckUi");
		$form->sendToPlayer($player);
	}

}