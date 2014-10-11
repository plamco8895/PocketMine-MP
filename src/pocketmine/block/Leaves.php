<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____  
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \ 
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/ 
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_| 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 * 
 *
*/

namespace pocketmine\block;

use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\Server;

class Leaves extends Transparent{
	const OAK = 0;
	const SPRUCE = 1;
	const BIRCH = 2;
	const JUNGLE = 3;
	const ACACIA = 0;
	const DARK_OAK = 1;

	public function __construct($meta = 0){
		parent::__construct(self::LEAVES, $meta, "Leaves");
		$names = [
			self::OAK => "Oak Leaves",
			self::SPRUCE => "Spruce Leaves",
			self::BIRCH => "Birch Leaves",
			self::JUNGLE => "Jungle Leaves",
		];
		$this->name = $names[$this->meta & 0x03];
		$this->hardness = 1;
	}

	private function findLog(Block $pos, array $visited, $distance, &$check, $fromSide = null){
		++$check;
		$index = $pos->x . "." . $pos->y . "." . $pos->z;
		if(isset($visited[$index])){
			return false;
		}
		if($pos->getID() === self::WOOD){
			return true;
		}elseif($pos->getID() === self::LEAVES and $distance < 3){
			$visited[$index] = true;
			$down = $pos->getSide(0)->getID();
			if($down === Item::WOOD){
				return true;
			}
			if($fromSide === null){
				for($side = 2; $side <= 5; ++$side){
					if($this->findLog($pos->getSide($side), $visited, $distance + 1, $check, $side) === true){
						return true;
					}
				}
			}else{ //No more loops
				switch($fromSide){
					case 2:
						if($this->findLog($pos->getSide(2), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(4), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(5), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}
						break;
					case 3:
						if($this->findLog($pos->getSide(3), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(4), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(5), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}
						break;
					case 4:
						if($this->findLog($pos->getSide(2), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(3), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(4), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}
						break;
					case 5:
						if($this->findLog($pos->getSide(2), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(3), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(5), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}
						break;
				}
			}
		}

		return false;
	}

	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if(($this->meta & 0b00001100) === 0){
				$this->meta |= 0x08;
				$this->getLevel()->setBlock($this, $this, false, false, true);
			}
		}elseif($type === Level::BLOCK_UPDATE_RANDOM){
			if(($this->meta & 0b00001100) === 0x08){
				$this->meta &= 0x03;
				$visited = [];
				$check = 0;

				Server::getInstance()->getPluginManager()->callEvent($ev = new LeavesDecayEvent($this));

				if($ev->isCancelled() or $this->findLog($this, $visited, 0, $check) === true){
					$this->getLevel()->setBlock($this, $this, false, false);
				}else{
					$this->getLevel()->setBlock($this, new Air(), false, false);
					if(mt_rand(1, 20) === 1){ //Saplings
						$this->getLevel()->dropItem($this, Item::get($this->id, $this->meta & 0x03, 1));
					}
					if(($this->meta & 0x03) === self::OAK and mt_rand(1, 200) === 1){ //Apples
						$this->getLevel()->dropItem($this, Item::get(Item::APPLE, 0, 1));
					}

					return Level::BLOCK_UPDATE_NORMAL;
				}
			}
		}

		return false;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$this->meta |= 0x04;
		$this->getLevel()->setBlock($this, $this, true);
	}

	public function getDrops(Item $item){
		$drops = [];
		if($item->isShears()){
			$drops[] = [Item::LEAVES, $this->meta & 0x03, 1];
		}else{
			if(mt_rand(1, 20) === 1){ //Saplings
				$drops[] = [Item::SAPLING, $this->meta & 0x03, 1];
			}
			if(($this->meta & 0x03) === self::OAK and mt_rand(1, 200) === 1){ //Apples
				$drops[] = [Item::APPLE, 0, 1];
			}
		}

		return $drops;
	}
}