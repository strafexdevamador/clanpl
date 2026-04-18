<?php

declare(strict_types=1);

namespace ClanPlugin\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use ClanPlugin\Main;

class LfTopsCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("lftops", "Receber item de leaderboard flutuante", "/lftops");
        $this->setPermission("clanplugin.leaderboard.create");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cUse este comando no jogo.");
            return false;
        }

        if (!$this->testPermission($sender)) {
            return false;
        }

        // Aqui você deve criar o item. Exemplo simples:
        $item = \pocketmine\item\VanillaItems::PAPER();
        $item->setCustomName("§aCriador de Leaderboard");
        $sender->getInventory()->addItem($item);
        $sender->sendMessage("§aVocê recebeu o item de criação de leaderboard flutuante!");
        return true;
    }
}