<?php

namespace Core;

use Core\API\FormAPI\CustomForm;
use Core\API\FormAPI\SimpleForm;
use Core\Commandes\Admin\Admin;
use Core\Commandes\Player\feed;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
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

	public $staffList = [];
	public $targetPlayer = [];


	public function onEnable()
	{
		@mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder("Player/"));
		@mkdir($this->getDataFolder("Reward"));
		$this->NewLogger();
		$this->CommandesLoaderPlayer();
		$this->CommandesLoaderAdmin();
		$this->LoadConfig();
		$this->db = new \SQLite3($this->getDataFolder() . "TempBanUI.db");
		$this->db->exec("CREATE TABLE IF NOT EXISTS banPlayers(player TEXT PRIMARY KEY, banTime INT, reason TEXT, staff TEXT);");
	}


	public function NewLogger()
	{
		$this->getServer()->getLogger()->info("Core Station c'est load correctement");
	}

	public function CommandesLoaderPlayer()
	{
		$this->getServer()->getCommandMap()->register("feed", new Feed("feed", $this));
	}

	public function CommandesLoaderAdmin()
	{
		$this->getServer()->getCommandMap()->register("admin", new Admin("admin", $this));
	}

	public function LoadConfig()
	{
		$this->message = (new Config($this->getDataFolder() . "Message.yml", Config::YAML, array(
			"BroadcastBanMessage" => "§b{player} §dhas been banned by §b{staff} §dfor §b{day} §dday/s, §b{hour} §dhour/s, §b{minute} §dminute/s. §dReason: §b{reason}",
			"KickBanMessage" => "§dYou are banned by §b{staff} §dfor §b{day} §dday/s, §b{hour} §dhour/s, §b{minute} §dminute/s. \n§dReason: §b{reason}",
			"LoginBanMessage" => "§dYou are still banned for §b{day} §dday/s, §b{hour} §dhour/s, §b{minute} §dminute/s, §b{second} §dsecond/s. \n§dReason: §b{reason} \n§dBanned by: §b{staff}",
			"BanMyself" => "§cYou can't ban yourself",
			"BanModeOn" => "§bBan mode on",
			"BanModeOff" => "§cBan mode off",
			"NoBanPlayers" => "§bNo ban players",
			"UnBanPlayer" => "§b{player} has been unban",
			"AutoUnBanPlayer" => "§b{player} has been auto unban. Ban time already done",
			"BanListTitle" => "§lBAN PLAYER LIST",
			"BanListContent" => "Choose player",
			"PlayerListTitle" => "§lPLAYER LIST",
			"PlayerListContent" => "Choose player",
			"InfoUIContent" => "§dInformation: \nDay: §b{day} \n§dHour: §b{hour} \n§dMinute: §b{minute} \n§dSecond: §b{second} \n§dReason: §b{reason} \n§dBanned by: §b{staff}\n\n\n",
			"InfoUIUnBanButton" => "Unban Player",
		)))->getAll();
	}

	public function openPlayerListUI($player){
		$form = new SimpleForm(function (Player $player, $data = null){
			$target = $data;
			if($target === null){
				return true;
			}
			$this->targetPlayer[$player->getName()] = $target;
			$this->openTbanUI($player);
		});
		$form->setTitle($this->message["PlayerListTitle"]);
		$form->setContent($this->message["PlayerListContent"]);
		foreach($this->getServer()->getOnlinePlayers() as $online){
			$form->addButton($online->getName(), -1, "", $online->getName());
		}
		$form->sendToPlayer($player);
		return $form;
	}

	public function openTbanUI($player){
		$form = new CustomForm(function (Player $player, array $data = null){
			$result = $data[0];
			if($result === null){
				return true;
			}
			if(isset($this->targetPlayer[$player->getName()])){
				if($this->targetPlayer[$player->getName()] == $player->getName()){
					$player->sendMessage($this->message["BanMyself"]);
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
		$form->setTitle(TextFormat::BOLD . "TEMPORARY BAN");
		$form->addDropdown("\nTarget", $list);
		$form->addSlider("Day/s", 0, 30, 1);
		$form->addSlider("Hour/s", 0, 24, 1);
		$form->addSlider("Minute/s", 0, 60, 5);
		$form->addInput("Reason");
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
			$player->sendMessage($this->message["NoBanPlayers"]);
			return true;
		}
		$form->setTitle($this->message["BanListTitle"]);
		$form->setContent($this->message["BanListContent"]);
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
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
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
		$form->addButton($this->message["InfoUIUnBanButton"]);
		$form->sendToPlayer($player);
		return $form;
	}

	public function onPlayerLogin(PlayerPreLoginEvent $event){
		$player = $event->getPlayer();
		$banplayer = $player->getName();
		$banInfo = $this->db->query("SELECT * FROM banPlayers WHERE player = '$banplayer';");
		$array = $banInfo->fetchArray(SQLITE3_ASSOC);
		if (!empty($array)) {
			$banTime = $array['banTime'];
			$reason = $array['reason'];
			$staff = $array['staff'];
			$now = time();
			if($banTime > $now){
				$remainingTime = $banTime - $now;
				$day = floor($remainingTime / 86400);
				$hourSeconds = $remainingTime % 86400;
				$hour = floor($hourSeconds / 3600);
				$minuteSec = $hourSeconds % 3600;
				$minute = floor($minuteSec / 60);
				$remainingSec = $minuteSec % 60;
				$second = ceil($remainingSec);
				$player->close("", str_replace(["{day}", "{hour}", "{minute}", "{second}", "{reason}", "{staff}"], [$day, $hour, $minute, $second, $reason, $staff], $this->message["LoginBanMessage"]));
			} else {
				$this->db->query("DELETE FROM banPlayers WHERE player = '$banplayer';");
			}
		}
		if(isset($this->staffList[$player->getName()])){
			unset($this->staffList[$player->getName()]);
		}
	}

}
