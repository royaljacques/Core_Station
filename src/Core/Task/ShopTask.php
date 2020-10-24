<?php


namespace Core\Task;

use Core\Main;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class ShopTask extends Task
{


	private $tp;
	private $sender;
	private $timer = 5;

	public function __construct(Main $plugin, Player $sender)
	{
		$this->tp = $plugin;
		$this->sender = $sender;
	}
	public function onRun(int $currentTick)
	{
		$config = new Config($this->tp->getDataFolder() . "Admin/" . "shop.yml", Config::YAML);

		$sender = $this->sender;
		$this->timer--;
		$sender->sendPopup(TextFormat::GRAY ."Teleportation : " . $this->timer);
		if ($this->timer <= 0){
			if ($config->exists($sender->getName())) {
				$pos = explode("_", $config->get($sender->getName()));
				$x = (int)$pos[0];
				$y = (int)$pos[1];
				$z = (int)$pos[2];
				$level = $this->tp->getServer()->getLevelByName($pos[3]);
				$sender->teleport(new Vector3($x, $y, $z, $level));
				$sender->sendMessage("tu as été téléporter au shop ");
				$this->tp->getScheduler()->cancelTask($this->getTaskId());
				$sender->getLevel()->addSound(new EndermanTeleportSound($sender));
			} else {
				$sender->sendMessage("pas de spawn défini");
			}
			$this->tp->getScheduler()->cancelTask($this->getTaskId());
		}
	}
}