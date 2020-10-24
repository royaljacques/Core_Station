<?php
namespace Core\Commandes\Admin;

use Core\API\FormAPI\CustomForm;
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
	private $targetPlayer = [];

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
			$this->StaffForm($player);
		}
		return true;
	}


	public function StaffForm($player)
	{
		$form = new SimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return true;
			}
			switch($result) {
				case 0:
					$this->BanForm($player);
					break;
				case 1:
					$this->MuteForm($player);
					break;
				case 2:
					$this->openGamemodeUi($player);
					break;
				case 3:
					$this->VanishUI($player);
					break;
				case 4:
					$this->Teleportui($player);
					break;
			}
			return true;
		});
		$form->setTitle("Staff Ui");
		$form->addButton("TbanUi");
		$form->addButton("tMute");
		$form->addButton("Gamemode ");
		$form->addButton("Vanish ");
		$form->addButton("Téléport ");


		$form->sendToPlayer($player);
	}
	public function MuteForm($player)
	{
		$form = new SimpleForm(function (Player $player, int $data = null) {
			$result = $data;
			if ($result === null) {
				return true;
			}
			switch ($result) {
				case 0:
					Main::getInstance()->openPlayerMuteListUI($player);
					break;
				case 1:
					Main::getInstance()->openTcheckMuteUI($player);
					break;
			}
			return true;
		});
		$form->addButton("Tmute");
		$form->addButton("TCheckmute");
		$form->sendToPlayer($player);
}
	public function BanForm($player)
	{
		$form = new SimpleForm(function (Player $player, int $data = null) {
			$result = $data;
			if ($result === null) {
				return true;
			}
			switch ($result) {
				case 0:
					Main::getInstance()->openPlayerListUI($player);
					break;
				case 1:
					Main::getInstance()->openTcheckUI($player);
					break;
			}
			return true;
		});
		$form->addButton("Tban");
		$form->addButton("TcheckBan");
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
		$form->addButton("Gamemode 2");
		$form->addButton("Gamemode 3");
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

		$form->addButton("§eVANISH §aON\n§7§oTap To Enable");

		$form->addButton("§eVANISH §cOFF\n§7§oGap To Disable");


		$form->sendToPlayer($player);

		return true;

	}
	public function Teleportui($player){
		$api = Main::getInstance()->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = new SimpleForm(function (Player $player, $data = null){
			$target = $data;
			if($target === null){
				return true;
			}
			$this->targetPlayer[$player->getName()] = $target;
			$this->OnTeleport($player);
			return true;
		});
		$form->setTitle("Choisis un joeur");
		$form->setContent("Liste des Joueurs");
		foreach(Main::getInstance()->getServer()->getOnlinePlayers() as $online){
			$form->addButton($online->getName(), -1, "", $online->getName());
		}
		$form->sendToPlayer($player);
		return $form;
	}

	public function OnTeleport(Player $player)
	{
		if(isset($this->targetPlayer[$player->getName()])){
			$target = Main::getInstance()->getServer()->getPlayerExact($this->targetPlayer[$player->getName()]);
			$target = $target->getPlayer();
			$player->teleport($target);
			$player->sendMessage("Tu a bien été tp vers ". $target->getName());
		}
		return true;
	}
}