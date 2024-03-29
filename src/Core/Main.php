<?php

namespace Core;


use Core\API\FormAPI\CustomForm;
use Core\API\FormAPI\SimpleForm;
use Core\Commandes\Admin\Admin;
use Core\Commandes\Admin\Clear;
use Core\Commandes\Admin\Freeze;
use Core\Commandes\Admin\SetShop;
use Core\Commandes\Admin\Tmute;
use Core\Commandes\Admin\Unfreeze;
use Core\Commandes\Player\feed;
use Core\Commandes\Player\Shop;
use Core\Event\Join;
use Core\Event\PlayerMooveEvent;
use Core\Event\PlayerQuitEvents;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener
{
	/**
	 * @var \SQLite3
	 */
	private $db;
	/**
	 * @var mixed[]
	 */
	public $message;
	private $targetPlayer = [];
	public $staffList = [];
	private static $instance;
	public $freeze = [];
	public function onEnable()
	{
		self::$instance = $this;
		@mkdir($this->getDataFolder("Player/"));
		@mkdir($this->getDataFolder("Admin/"));
		$this->NewLogger();
		$this->CommandesLoaderPlayer();
		$this->CommandesLoaderAdmin();
		$this->LoadEvent();
		$this->LoadConfig();
		$this->db = new \SQLite3($this->getDataFolder() . "Modération.db");
		$this->db->exec("CREATE TABLE IF NOT EXISTS mutePlayers(player TEXT PRIMARY KEY, muteTime INT, reason TEXT, staff TEXT);");

		$this->db->exec("CREATE TABLE IF NOT EXISTS banPlayers(player TEXT PRIMARY KEY, banTime INT, reason TEXT, staff TEXT);");
	}
	public function NewLogger()
	{
		$this->getServer()->getLogger()->info("Core Station c'est load correctement");
	}

	public function LoadEvent()
	{
		$this->getServer()->getPluginManager()->registerEvents(new Join($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new PlayerMooveEvent($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new PlayerQuitEvents($this), $this);

	}
	public function CommandesLoaderPlayer()
	{
		$this->getServer()->getCommandMap()->register("feed", new Feed("feed", $this));
		$this->getServer()->getCommandMap()->register("shop", new Shop("shop", $this));
	}

	public function CommandesLoaderAdmin()
	{
		$this->getServer()->getCommandMap()->register("admin", new Admin("admin", $this));
		$this->getServer()->getCommandMap()->register("clear", new Clear("clear", $this));
		$this->getServer()->getCommandMap()->register("setshop", new SetShop("setshop", $this));
		$this->getServer()->getCommandMap()->register("tmute", new Tmute("Tmute", $this));
		$this->getServer()->getCommandMap()->register("freeze", new Freeze("freeze", $this));
		$this->getServer()->getCommandMap()->register("unfreeze", new Unfreeze("unfreeze", $this));
	}
	public function LoadConfig()
	{
	}
	public function openPlayerListUI($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = new SimpleForm(function (Player $player, $data = null){
			$target = $data;
			if($target === null){
				return true;
			}
			$this->targetPlayer[$player->getName()] = $target;
			$this->openTbanUI($player);
		});
		$form->setTitle("Liste des joueurs ");
		$form->setContent("choisis un Joueur");
		foreach($this->getServer()->getOnlinePlayers() as $online){
			$form->addButton($online->getName(), -1, "", $online->getName());
		}
		$form->sendToPlayer($player);
		return $form;
	}

	public function openTbanUI($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = new CustomForm(function (Player $player, array $data = null){
			$result = $data[0];
			if($result === null){
				return true;
			}
			if(isset($this->targetPlayer[$player->getName()])){
				if($this->targetPlayer[$player->getName()] == $player->getName()){
					$player->sendMessage("tu ne peut pas te ban toi même; ");
					return true;
				}
				$now = time();
				$day = ($data[1] * 86400);
				$hour = ($data[2] * 3600);
				if($data[3] > 1){
					$min = ($data[3] * 60);
				} else {
					$min = 60;
				}
				$banTime = $now + $day + $hour + $min;
				$banInfo = $this->db->prepare("INSERT OR REPLACE INTO banPlayers (player, banTime, reason, staff) VALUES (:player, :banTime, :reason, :staff);");
				$banInfo->bindValue(":player", $this->targetPlayer[$player->getName()]);
				$banInfo->bindValue(":banTime", $banTime);
				$banInfo->bindValue(":reason", $data[4]);
				$banInfo->bindValue(":staff", $player->getName());
				$banInfo->execute();
				$target = $this->getServer()->getPlayerExact($this->targetPlayer[$player->getName()]);
				if($target instanceof Player){
					$target->kick(str_replace(["{day}", "{hour}", "{minute}", "{reason}", "{staff}"], [$data[1], $data[2], $data[3], $data[4], $player->getName()], $this->message["KickBanMessage"]));
				}
				$this->getServer()->broadcastMessage(str_replace(["{player}", "{day}", "{hour}", "{minute}", "{reason}", "{staff}"], [$this->targetPlayer[$player->getName()], $data[1], $data[2], $data[3], $data[4], $player->getName()], $this->message["BroadcastBanMessage"]));
				unset($this->targetPlayer[$player->getName()]);

			}
		});
		$list[] = $this->targetPlayer[$player->getName()];
		$form->setTitle(TextFormat::BOLD . "§dSTARS§r §3TEMP BAN");
		$form->addDropdown("\nCible", $list);
		$form->addSlider("Jour/s", 0, 30, 1);
		$form->addSlider("Heure/s", 0, 24, 1);
		$form->addSlider("Minute/s", 0, 60, 5);
		$form->addInput("Raison");
		$form->sendToPlayer($player);
		return $form;
	}

	public function openTcheckUI($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = new SimpleForm(function (Player $player, $data = null){
			if($data === null){
				return true;
			}
			$this->targetPlayer[$player->getName()] = $data;
			$this->openInfoUI($player);
		});
		$banInfo = $this->db->query("SELECT * FROM banPlayers;");
		$array = $banInfo->fetchArray(SQLITE3_ASSOC);
		if (empty($array)) {
			$player->sendMessage("Pas de joueurs Bannis");
			return true;
		}
		$form->setTitle("Liste des joueurs ");
		$form->setContent("Choisis Le Joueur");
		$banInfo = $this->db->query("SELECT * FROM banPlayers;");
		$i = -1;
		while ($resultArr = $banInfo->fetchArray(SQLITE3_ASSOC)) {
			$j = $i + 1;
			$banPlayer = $resultArr['player'];
			$form->addButton(TextFormat::BOLD . "$banPlayer", -1, "", $banPlayer);
			$i = $i + 1;
		}
		$form->sendToPlayer($player);
		return $form;
	}

	public function openInfoUI($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = new SimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return true;
			}
			switch($result){
				case 0:
					$banplayer = $this->targetPlayer[$player->getName()];
					$banInfo = $this->db->query("SELECT * FROM banPlayers WHERE player = '$banplayer';");
					$array = $banInfo->fetchArray(SQLITE3_ASSOC);
					if (!empty($array)) {
						$this->db->query("DELETE FROM banPlayers WHERE player = '$banplayer';");
						$player->sendMessage(str_replace(["{player}"], [$banplayer], $this->message["UnBanPlayer"]));
					}
					unset($this->targetPlayer[$player->getName()]);
					break;
			}
		});
		$banPlayer = $this->targetPlayer[$player->getName()];
		$banInfo = $this->db->query("SELECT * FROM banPlayers WHERE player = '$banPlayer';");
		$array = $banInfo->fetchArray(SQLITE3_ASSOC);
		if (!empty($array)) {
			$banTime = $array['banTime'];
			$reason = $array['reason'];
			$staff = $array['staff'];
			$now = time();
			if($banTime < $now){
				$banplayer = $this->targetPlayer[$player->getName()];
				$banInfo = $this->db->query("SELECT * FROM banPlayers WHERE player = '$banplayer';");
				$array = $banInfo->fetchArray(SQLITE3_ASSOC);
				if (!empty($array)) {
					$this->db->query("DELETE FROM banPlayers WHERE player = '$banplayer';");
					$player->sendMessage(str_replace(["{player}"], [$banplayer], $this->message["AutoUnBanPlayer"]));
				}
				unset($this->targetPlayer[$player->getName()]);
				return true;
			}
			$remainingTime = $banTime - $now;
			$day = floor($remainingTime / 86400);
			$hourSeconds = $remainingTime % 86400;
			$hour = floor($hourSeconds / 3600);
			$minuteSec = $hourSeconds % 3600;
			$minute = floor($minuteSec / 60);
			$remainingSec = $minuteSec % 60;
			$second = ceil($remainingSec);
		}
		$form->setTitle(TextFormat::BOLD . $banPlayer);
		$form->setContent(str_replace(["{day}", "{hour}", "{minute}", "{second}", "{reason}", "{staff}"], [$day, $hour, $minute, $second, $reason, $staff], $this->message["InfoUIContent"]));
		$form->addButton("Unban Le Joueur ");
		$form->sendToPlayer($player);
		return $form;
	}

	/**
	 * @return Main
	 */
	public static function getInstance() : Main
	{
		return self::$instance;
	}

	public function openPlayerMuteListUI($player){
		$form = new SimpleForm(function (Player $player, $data = null){
			$target = $data;
			if($target === null){
				return true;
			}
			$this->targetPlayer[$player->getName()] = $target;
			$this->openTmuteUI($player);
			return true;
		});
		$form->setTitle("Liste des Joueurs ");
		$form->setContent("Chosis un joueur ");
		foreach($this->getServer()->getOnlinePlayers() as $online){
			$form->addButton($online->getName(), -1, "", $online->getName());
		}
		$form->sendToPlayer($player);
		return $form;
	}
	public function openTmuteUI($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = new CustomForm(function (Player $player, array $data = null){
			$result = $data[0];
			if($result === null){
				return true;
			}
			if(isset($this->targetPlayer[$player->getName()])){
				if($this->targetPlayer[$player->getName()] == $player->getName()){
					$player->sendMessage("tu ne peut pas te mute");
					return true;
				}
				$now = time();
				$day = ($data[1] * 86400);
				$hour = ($data[2] * 3600);
				if($data[3] > 1){
					$min = ($data[3] * 60);
				} else {
					$min = 60;
				}
				$muteTime = $now + $day + $hour + $min;
				$muteInfo = $this->db->prepare("INSERT OR REPLACE INTO mutePlayers (player, muteTime, reason, staff) VALUES (:player, :muteTime, :reason, :staff);");
				$muteInfo->bindValue(":player", $this->targetPlayer[$player->getName()]);
				$muteInfo->bindValue(":muteTime", $muteTime);
				$muteInfo->bindValue(":reason", $data[4]);
				$muteInfo->bindValue(":staff", $player->getName());
				$muteInfo->execute();
				$target = $this->getServer()->getPlayerExact($this->targetPlayer[$player->getName()]);
				if($target instanceof Player){
					$target->sendMessage(str_replace(["{day}", "{hour}", "{minute}", "{reason}", "{staff}"], [$data[1], $data[2], $data[3], $data[4], $player->getName()], $this->message["MuteMessage"]));
				}
				$this->getServer()->broadcastMessage(str_replace(["{player}", "{day}", "{hour}", "{minute}", "{reason}", "{staff}"], [$this->targetPlayer[$player->getName()], $data[1], $data[2], $data[3], $data[4], $player->getName()], $this->message["BroadcastMuteMessage"]));
				unset($this->targetPlayer[$player->getName()]);

			}
			return true;
		});
		$list[] = $this->targetPlayer[$player->getName()];
		$form->setTitle(TextFormat::BOLD . "TEMPORARY MUTE");
		$form->addDropdown("\nTarget", $list);
		$form->addSlider("Day/s", 0, 30, 1);
		$form->addSlider("Hour/s", 0, 24, 1);
		$form->addSlider("Minute/s", 0, 60, 5);
		$form->addInput("Reason");
		$form->sendToPlayer($player);
		return $form;
	}

	public function openTcheckMuteUI($player){
		$form = new SimpleForm(function (Player $player, $data = null){
			if($data === null){
				return true;
			}
			$this->targetPlayer[$player->getName()] = $data;
			$this->openInfoMuteUI($player);
		});
		$muteInfo = $this->db->query("SELECT * FROM mutePlayers;");
		$array = $muteInfo->fetchArray(SQLITE3_ASSOC);
		if (empty($array)) {
			$player->sendMessage("Il n'y a pas de Joueurs Mutes");
			return true;
		}
		$form->setTitle("Liste des Joueurs");
		$form->setContent("Choisi un Joueur ");
		$muteInfo = $this->db->query("SELECT * FROM mutePlayers;");
		$i = -1;
		while ($resultArr = $muteInfo->fetchArray(SQLITE3_ASSOC)) {
			$j = $i + 1;
			$mutePlayer = $resultArr['player'];
			$form->addButton(TextFormat::BOLD . "$mutePlayer", -1, "", $mutePlayer);
			$i = $i + 1;
		}
		$form->sendToPlayer($player);
		return $form;
	}

	public function openInfoMuteUI($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = new SimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null){
				return true;
			}
			switch($result){
				case 0:
					$muteplayer = $this->targetPlayer[$player->getName()];
					$muteInfo = $this->db->query("SELECT * FROM mutePlayers WHERE player = '$muteplayer';");
					$array = $muteInfo->fetchArray(SQLITE3_ASSOC);
					if (!empty($array)) {
						$this->db->query("DELETE FROM mutePlayers WHERE player = '$muteplayer';");
						$player->sendMessage(str_replace(["{player}"], [$muteplayer], $this->message["UnMutePlayer"]));
					}
					unset($this->targetPlayer[$player->getName()]);
					break;
			}
			return true;
		});
		$mutePlayer = $this->targetPlayer[$player->getName()];
		$muteInfo = $this->db->query("SELECT * FROM mutePlayers WHERE player = '$mutePlayer';");
		$array = $muteInfo->fetchArray(SQLITE3_ASSOC);
		if (!empty($array)) {
			$muteTime = $array['muteTime'];
			$reason = $array['reason'];
			$staff = $array['staff'];
			$now = time();
			if($muteTime < $now){
				$muteplayer = $this->targetPlayer[$player->getName()];
				$muteInfo = $this->db->query("SELECT * FROM mutePlayers WHERE player = '$muteplayer';");
				$array = $muteInfo->fetchArray(SQLITE3_ASSOC);
				if (!empty($array)) {
					$this->db->query("DELETE FROM mutePlayers WHERE player = '$muteplayer';");
					$player->sendMessage(str_replace(["{player}"], [$muteplayer], $this->message["AutoUnMutePlayer"]));
				}
				unset($this->targetPlayer[$player->getName()]);
				return true;
			}
			$remainingTime = $muteTime - $now;
			$day = floor($remainingTime / 86400);
			$hourSeconds = $remainingTime % 86400;
			$hour = floor($hourSeconds / 3600);
			$minuteSec = $hourSeconds % 3600;
			$minute = floor($minuteSec / 60);
			$remainingSec = $minuteSec % 60;
			$second = ceil($remainingSec);
		}
		$form->setTitle(TextFormat::BOLD . $mutePlayer);
		$form->setContent(str_replace(["{day}", "{hour}", "{minute}", "{second}", "{reason}", "{staff}"], [$day, $hour, $minute, $second, $reason, $staff], $this->message["InfoUIContent"]));
		$form->addButton("Unmute le Joueur ");
		$form->sendToPlayer($player);
		return $form;
	}

	public function onPlayerChat(PlayerChatEvent $event){
		$player = $event->getPlayer();
		$muteplayer = $player->getName();
		$muteInfo = $this->db->query("SELECT * FROM mutePlayers WHERE player = '$muteplayer';");
		$array = $muteInfo->fetchArray(SQLITE3_ASSOC);
		if (!empty($array)) {
			$muteTime = $array['muteTime'];
			$reason = $array['reason'];
			$staff = $array['staff'];
			$now = time();
			if($muteTime > $now){
				$remainingTime = $muteTime - $now;
				$day = floor($remainingTime / 86400);
				$hourSeconds = $remainingTime % 86400;
				$hour = floor($hourSeconds / 3600);
				$minuteSec = $hourSeconds % 3600;
				$minute = floor($minuteSec / 60);
				$remainingSec = $minuteSec % 60;
				$second = ceil($remainingSec);
				$event->setCancelled();
				$player->sendMessage(str_replace(["{day}", "{hour}", "{minute}", "{second}", "{reason}", "{staff}"], [$day, $hour, $minute, $second, $reason, $staff]), "s§dYou are muted by §b{staff} §dfor §b{day} §dday/s, §b{hour} §dhour/s, §b{minute} §dminute/s. \n§dReason: §b{reason}");
			} else {
				$this->db->query("DELETE FROM mutePlayers WHERE player = '$muteplayer';");
			}
		}
		if(isset($this->staffList[$player->getName()])){
			unset($this->staffList[$player->getName()]);
		}
	}



}
