<?php

declare(strict_types=1);

namespace ZEvors\SquidGame\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\block\VanillaBlocks;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\Plugin;
use ZEvors\SquidGame\Main;

class GlassCommand extends Command {
    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct(
            "squidgame"
        );
        $this->setPermission("squidgame.game");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            $sender->sendMessage("§cThere is no permission to use this command.");
            return false;
        }

        $crystal = VanillaBlocks::STAINED_GLASS()->asItem();
        
        $nbt = $crystal->getNamedTag();
        $nbt->setString("SquidGame", "true");
        $crystal->setNamedTag($nbt);
        
        
        $crystal->setLore([
            "§c¡The care of the scallops!"
        ]);

        if ($sender->getInventory()->canAddItem($crystal)) {
            $sender->getInventory()->addItem($crystal);
        } else {
        }

        return true;
    }
}
