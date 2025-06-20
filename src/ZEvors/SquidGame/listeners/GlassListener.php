<?php

declare(strict_types=1);

namespace ZEvors\SquidGame\listeners;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Block;
use pocketmine\block\Glass;
use pocketmine\block\Air;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\sound\BlockBreakSound;
use pocketmine\world\particle\BlockBreakParticle;
use ZEvors\SquidGame\Main;

class GlassListener implements Listener {
    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $block = $event->getBlockAgainst(); 
        $blockPosition = $block->getPosition(); 
        $world = $player->getWorld();
        
        $nbt = $item->getNamedTag();
        if (!$nbt->getString("SquidGame", "")) {
            return;
        }
        
        $x = $blockPosition->getX();
        $y = $blockPosition->getY();
        $z = $blockPosition->getZ();
        
        $this->plugin->addGlassPosition($world->getFolderName(), $x, $y, $z);
        
        for ($dx = -1; $dx <= 1; $dx++) {
            for ($dz = -1; $dz <= 1; $dz++) {
                $newX = $x + $dx;
                $newZ = $z + $dz;
                
                $targetPosition = new Position($newX, $y, $newZ, $world);
                $world->setBlock($targetPosition, VanillaBlocks::GLASS());
                
                $this->plugin->addGlassPosition($world->getFolderName(), $newX, $y, $newZ);
            }
        }
    }
    
    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $from = $event->getFrom();
        $to = $event->getTo();
        
        if ($from->floor()->equals($to->floor())) {
            return;
        }
        
        $world = $player->getWorld();
        $playerPos = $to->floor();
        $checkY = $playerPos->getY() - 1;
        
        $playerX = $playerPos->getX();
        $playerZ = $playerPos->getZ();
        
        $targetPosition = new Position($playerX, $checkY, $playerZ, $world);
        $targetBlock = $world->getBlock($targetPosition);
        
        if ($targetBlock instanceof Glass && 
            $this->plugin->isGlassPosition(
                $world->getFolderName(),
                $playerX,
                $checkY,
                $playerZ
            )) {
            
            $foundGlass = [];
            for ($dx = -2; $dx <= 2; $dx++) {
                for ($dz = -2; $dz <= 2; $dz++) {
                    $checkX = $playerX + $dx;
                    $checkZ = $playerZ + $dz;
                    
                    if ($this->plugin->isGlassPosition(
                        $world->getFolderName(),
                        $checkX,
                        $checkY,
                        $checkZ
                    )) {
                        $foundGlass[] = [$checkX, $checkZ];
                    }
                }
            }
            
            $centerPositions = [];
            foreach ($foundGlass as $glass) {
                list($glassX, $glassZ) = $glass;
                
                $isCenterX = true;
                $isCenterZ = true;
                
                for ($dx = -1; $dx <= 1; $dx++) {
                    for ($dz = -1; $dz <= 1; $dz++) {
                        $testX = $glassX + $dx;
                        $testZ = $glassZ + $dz;
                        
                        $isRegistered = false;
                        foreach ($foundGlass as $testGlass) {
                            if ($testGlass[0] == $testX && $testGlass[1] == $testZ) {
                                $isRegistered = true;
                                break;
                            }
                        }
                        
                        if (!$isRegistered) {
                            if ($dx != 0) {
                                $isCenterX = false;
                            }
                            if ($dz != 0) {
                                $isCenterZ = false;
                            }
                        }
                    }
                }
                
                if ($isCenterX && $isCenterZ) {
                    $centerPositions[] = [$glassX, $glassZ];
                }
            }
            
            foreach ($centerPositions as $center) {
                list($centerX, $centerZ) = $center;
                
                for ($bx = -1; $bx <= 1; $bx++) {
                    for ($bz = -1; $bz <= 1; $bz++) {
                        $breakX = $centerX + $bx;
                        $breakZ = $centerZ + $bz;
                        $breakY = $checkY;
                        
                        $breakPosition = new Position($breakX, $breakY, $breakZ, $world);
                        $breakBlock = $world->getBlock($breakPosition);
                        
                        if ($breakBlock instanceof Glass && 
                            $this->plugin->isGlassPosition(
                                $world->getFolderName(),
                                $breakX,
                                $breakY,
                                $breakZ
                            )) {
                            
                            $world->setBlock($breakPosition, VanillaBlocks::AIR());
                            
                            $this->plugin->removeGlassPosition(
                                $world->getFolderName(),
                                $breakX,
                                $breakY,
                                $breakZ
                            );
                            
                            $world->addSound($breakPosition, new BlockBreakSound(VanillaBlocks::GLASS()));
                            $world->addParticle($breakPosition->add(0.5, 0.5, 0.5), new BlockBreakParticle($breakBlock));
                        }
                    }
                }
            }
        }
    }
    
    public function onBlockBreak(BlockBreakEvent $event): void {
        $block = $event->getBlock();
        
        if ($block instanceof Glass &&
            $this->plugin->isGlassPosition(
                $block->getPosition()->getWorld()->getFolderName(),
                $block->getPosition()->getX(),
                $block->getPosition()->getY(),
                $block->getPosition()->getZ()
            )) {
            
            $blockPos = $block->getPosition();
            
            $this->plugin->removeGlassPosition(
                $blockPos->getWorld()->getFolderName(),
                $blockPos->getX(),
                $blockPos->getY(),
                $blockPos->getZ()
            );
        }
    }
}
