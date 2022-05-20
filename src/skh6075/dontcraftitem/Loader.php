<?php

declare(strict_types=1);

namespace skh6075\dontcraftitem;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\EventPriority;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\item\Item;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use function array_pop;
use function array_shift;

final class Loader extends PluginBase{

	public static string $prefix = "§l§b[알림]§r§7 ";

	/**
	 * @phpstan-var array<string, bool>
	 * @var bool[]
	 */
	private array $db = [];

	protected function onEnable() : void{
		$this->saveDefaultConfig();
		$this->db = $this->getConfig()->getAll();

		$this->getServer()->getPluginManager()->registerEvent(CraftItemEvent::class, function(CraftItemEvent $event): void{
			if(!$event->isCancelled()){
				$result = $event->getOutputs();
				$item = array_pop($result);
				if(!$item instanceof Item || !isset($this->db[$this->getItemHash($item)])){
					return;
				}
				$player = $event->getPlayer();
				if($player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
					return;
				}
				$event->cancel();
				$player->sendActionBarMessage(TextFormat::RED . "해당 아이템은 조합이 금지되어있습니다.");
			}
		}, EventPriority::MONITOR, $this, false);
	}

	protected function onDisable() : void{
		$this->getConfig()->setAll($this->db);
		$this->getConfig()->save();
	}

	public function getItemHash(Item $item): string{
		return $item->getId() . ":" . $item->getMeta();
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!$sender instanceof Player || !$command->testPermission($sender)){
			return false;
		}
		switch(array_shift($args) ?? ''){
			case '추가':
				if(($item = $sender->getInventory()->getItemInHand())->isNull()){
					$sender->sendMessage(self::$prefix . "공기는 조합밴으로 관리할 수 없는 아이템입니다.");
					return false;
				}
				if(isset($this->db[$this->getItemHash($item)])){
					$sender->sendMessage(self::$prefix . "이미 조합밴 대상 아이템입니다.");
					return false;
				}
				$this->db[$this->getItemHash($item)] = true;
				$sender->sendMessage(self::$prefix . "손에 든 아이템 조합을 금지시켰습니다.");
				break;
			case '삭제':
				if(($item = $sender->getInventory()->getItemInHand())->isNull()){
					$sender->sendMessage(self::$prefix . "공기는 조합밴으로 관리할 수 없는 아이템입니다.");
					return false;
				}
				if(!isset($this->db[$this->getItemHash($item)])){
					$sender->sendMessage(self::$prefix . "해당 아이템은 조합밴 대상 아이템이 아닙니다.");
					return false;
				}
				unset($this->db[$this->getItemHash($item)]);
				$sender->sendMessage(self::$prefix . "손에 든 아이템 조합금지를 해제했습니다.");
				break;
			default:
				$sender->sendMessage(self::$prefix . "/조합밴 추가 | 조합밴을 추가합니다.");
				$sender->sendMessage(self::$prefix . "/조합밴 삭제 | 조합밴을 삭제합니다.");
				break;
		}
		return true;
	}
}