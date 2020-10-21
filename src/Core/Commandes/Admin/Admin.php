<?php
namespace Core\Commandes\Admin;

use Core\API\FormAPI\SimpleForm;
use Core\Form\TbanUi;
use Core\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\entity\Entity;
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
					$player->hasPermission("Modo");
					$this->ModoForm($player);
					break;
				case 1:
					$player->hasPermission("Op");
					$this->OpForm($player);
					break;

			}
			return true;
		});
		$form->setTitle("Index des Staffs ");
		$form->addButton("§1§l✶ Modérateur ✶");
		$form->addButton("§1§l✶ Op ✶");
		$form->sendToPlayer($player);

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
					Main::getInstance()->openPlayerListUI($player);
					break;
				case 1:
					$this->plugin->openTcheckUI($player);
					break;
				case 2:
					$this->openGamemodeUi($player);
			}
			return true;
		});
		$form->setTitle("Modérateur");
		$form->addButton("TbanUi");
		$form->addButton("TcheckUi");
		$form->addButton("Gamemode ");
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
					Main::getInstance()->openPlayerListUI($player);
					break;
				case 1:
					Main::getInstance()->openTcheckUI($player);
					break;
			}
			return true;
		});
		$form->setTitle("Modérateur");
		$form->addButton("TbanUi");
		$form->addButton("TcheckUi");
		$form->sendToPlayer($player);
	}

	public function openGamemodeUi(Player $player)
	{
		$form = new SimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return true;
			}
			switch($result){
				case 0:

					$player->setGamemode(0);

					$player->sendMessage("§aSucces! §fYour Gamemode Has Been Changed To §cSurvival");

					$player->addTitle("§cSurvival", "§fYour Gamemode Is Survival");

					break;

				case 1:

					$player->setGamemode(1);

					$player->sendMessage("§aSucces! §fYour Gamemode Has Been Changed To §aCreative");

					$player->addTitle("§aCreative", "§fYour Gamemode Is Creative");

					break;

				case 2:

					$player->setGamemode(2);

					$player->sendMessage("§aSucces! §fYour Gamemode Has Been Changed To §bAdventure");

					$player->addTitle("§bAdventure", "§fYour Gamemode Is Adventure");

					break;

				case 3:

					$player->setGamemode(3);

					$player->sendMessage("§aSucces! §fYour Gamemode Has Been Changed To §eSpecator");

					$player->addTitle("§eSpectator", "§fYour Gamemode Is Spectator");

					break;
			}
			return true;
		});
		$form->setTitle("Gamemode");
		$form->addButton("Gamemode 1");
		$form->addButton("gamemode 2");
		$form->addButton("gamemode 3");
		$form->sendToPlayer($player);

	}
	public function VanishUI($player){


		$form = new SimpleForm(function (Player $player, int $data = null) {

			$result = $data;

			if($result === null){

				return true;

			}

			switch($result){

				case 0:

					$player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true);

					$player->setNameTagVisible(false);

					$player->addTitle("§aVanish", "§fHas Been Enable");

					break;

				case 1:

					$player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);

					$player->setNameTagVisible(true);

					$player->addTitle("§cVanish", "§fHas Been Disable");

					break;



			}

		});

		$form->setTitle("§b§lVanish");

		$form->addButton("§eVANISH §aON\n§7§oTap To Enable",0,"textures/ui/check");

		$form->addButton("§eVANISH §cOFF\n§7§oGap To Disable",0,"textures/ui/cancel");


		$form->sendToPlayer($player);

		return true;

	}

}