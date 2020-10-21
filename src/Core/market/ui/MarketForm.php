<?php

namespace Core\market\ui;
use Core\Main;
use Core\market\libs\xenialdan\customui\elements\Button;
use Core\market\libs\xenialdan\customui\elements\Dropdown;
use Core\market\libs\xenialdan\customui\elements\Input;
use Core\market\libs\xenialdan\customui\elements\Label;
use Core\market\libs\xenialdan\customui\elements\Slider;
use Core\market\libs\xenialdan\customui\elements\StepSlider;
use Core\market\libs\xenialdan\customui\elements\Toggle;
use Core\market\libs\xenialdan\customui\windows\CustomForm;
use Core\market\libs\xenialdan\customui\windows\ModalForm;
use Core\market\libs\xenialdan\customui\windows\SimpleForm;

use pocketmine\Player;
use pocketmine\item\Item;

class MarketForm{
   private $plugin;
   private $prefix;
   private $lang;
   
   public function __construct(Main $plugin){
      $this->plugin = $plugin;
      $this->prefix = $this->plugin->getPrefix();
      $this->lang = $this->plugin->getLanguage();
   }
   
   public function getPlugin(): Main{
      return $this->plugin;
   }
  
   public function getPrefix(): string{
      return $this->prefix;
   }
   
   public function SearchId(Player $player, string $content = ""): void{
      $form = new CustomForm(
         $this->getPrefix()." SearchId"
      );
      $form->addElement(new Label($content));
      $form->addElement(new Input($this->getPlugin()->getLanguage()->getTranslate("form.searchid.input1.title"), "??"));
      
      $form->setCallable(function ($player, $data){
         if($data == null){
            return;
         }
         $id = explode(" ", $data[1]); 
         if($id[0] == null){
            $text = $this->lang->getTranslate(
               "form.searchid.input1.error1", 
               [$this->lang->getTranslate("form.searchid.input1.title")]
            );
            $this->SearchId($player, $text);
            return;
         }
         if(!is_numeric($id[0])){
            $text = $this->lang->getTranslate(
               "form.searchid.input1.error2",
               [$this->lang->getTranslate("form.searchid.input1.title")]
            );
            $this->SearchId($player, $text);
            return;
         }
         $id = (int) $id[0];
         if(!$this->getPlugin()->isShop($id)){
            $text = $this->lang->getTranslate(
               "form.searchid.input1.error3"
            );
            $this->SearchId($player, $text);
            return;
         }
         
         $this->getPlugin()->getMarketChest()->sendChest($player, "search", $id);
      });
      
      $form->setCallableClose(function (Player $player){
         //??
      });
      $player->sendForm($form);
   }
   
   public function BuyConfirm(Player $player, Item $item): void{
      $shopId = $item->getCustomBlockData()->getInt("id");
      $owner = $item->getCustomBlockData()->getString("owner");
      $description = $item->getCustomBlockData()->getString("description");
      $price = $item->getCustomBlockData()->getInt("price");
      $item->setLore([]);
      $form = new ModalForm(
         $this->getPrefix()." Buy Confirm",
         $this->getPrefix()." ".$this->lang->getTranslate(
            "form.buyconfirm.content",
            [$shopId, $owner, $item->getName(), $item->getCount(), $description, $price]
         ),
         $this->lang->getTranslate("form.buyconfirm.button1"),
         $this->lang->getTranslate("form.buyconfirm.button2")
      );
      $form->setCallable(function ($player, $data) use ($item, $shopId, $owner, $price){
         if(!($data === null)){
            if($data){
               if($this->getPlugin()->getMoneyAPI()->myMoney($player) >= $price){
                  $reduce = (int) $this->getPlugin()->getMoneyAPI()->myMoney($player) - $price;
                  $this->getPlugin()->getMoneyAPI()->setMoney($player, $reduce);
                  $player->getInventory()->addItem($item);
                  $player->sendMessage($this->getPrefix()." ".$this->lang->getTranslate(
                     "form.buyconfirm.complete",
                     [$item->getName(), $item->getCount(), $price]
                  ));
                  if(strtolower(
                     $item->getCustomBlockData()->getString("owner")
                  ) == strtolower(
                     $player->getName()
                  )){
                     $this->getPlugin()->getMoneyAPI()->addMoney($owner, 0);
                     $price = 0;
                  }else{
                     $this->getPlugin()->getMoneyAPI()->addMoney($owner, $price);
                  }
                  $ownerPlayer = $this->getPlugin()->getServer()->getPlayer($owner);
                  if($ownerPlayer instanceof Player){
                     $ownerPlayer->sendMessage($this->getPrefix()." ".$this->lang->getTranslate(
                        "form.buyconfirm.owner.complete",
                        [$player->getName(), $shopId, $price]
                     ));
                  }
                  $this->getPlugin()->removeShop($shopId);
               }else{
                  $player->sendMessage($this->getPrefix()." ".$this->lang->getTranslate(
                     "form.buyconfirm.error1"
                  ));
               }
            }
         }
      });
      $form->setCallableClose(function (Player $player){
         //??
      });
      $player->sendForm($form);
   }
   
}