<?php

declare(strict_types=1);

namespace ZEvors\SquidGame;

use pocketmine\plugin\PluginBase;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
// use SquidGame\commands\SquidGameCommand;
// use SquidGame\commands\SetupCommand;
// use SquidGame\commands\DuelCommand;
use ZEvors\SquidGame\commands\GlassCommand;
use ZEvors\SquidGame\listeners\GlassListener;

class Main extends PluginBase {
    use SingletonTrait;

    private Config $glassConfig;
    
    private array $glassPositions = [];

    public function onLoad(): void {
        self::setInstance($this);
        
        if (!file_exists($this->getDataFolder())) {
            mkdir($this->getDataFolder());
        }
    }

    public function onEnable(): void {
        $this->saveDefaultConfig();
        
        $this->glassConfig = new Config($this->getDataFolder() . "glass.yml", Config::YAML);
        $this->loadGlassPositions();
        
        $this->getServer()->getCommandMap()->register("squidgame", new GlassCommand($this));
        
        $this->getServer()->getPluginManager()->registerEvents(new GlassListener($this), $this);
    }

    public function onDisable(): void {
        $this->saveGlassPositions();
    }

    public function isGlassPosition(string $world, int $x, int $y, int $z): bool {
        $positionKey = "$world:$x:$y:$z";
        return isset($this->glassPositions[$positionKey]);
    }

    public function addGlassPosition(string $world, int $x, int $y, int $z): void {
        $positionKey = "$world:$x:$y:$z";
        $this->glassPositions[$positionKey] = true;
    }

    public function removeGlassPosition(string $world, int $x, int $y, int $z): void {
        $positionKey = "$world:$x:$y:$z";
        if (isset($this->glassPositions[$positionKey])) {
            unset($this->glassPositions[$positionKey]);
        }
    }

    private function loadGlassPositions(): void {
        $positions = $this->glassConfig->get("positions", []);
        foreach ($positions as $position) {
            $parts = explode(":", $position);
            if (count($parts) === 4) {
                $this->glassPositions[$position] = true;
            }
        }
    }

    private function saveGlassPositions(): void {
        $this->glassConfig->set("positions", array_keys($this->glassPositions));
        $this->glassConfig->save();
    }
}
