<?php

declare(strict_types=1);

namespace ClanPlugin\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use ClanPlugin\Main;

class ClanCommand extends Command {
    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("clan", "Sistema de Clãs", "/clan", ["c"]);
        $this->setPermission("clanplugin.use");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
    if (!$sender instanceof Player) {
        $sender->sendMessage("§cUse este comando no jogo.");
        return false;
    }

    // Se for "/lftops" diretamente, tratamos aqui (comando raiz)
    if ($commandLabel === "lftops") {
        if ($sender->hasPermission("clanplugin.leaderboard.create")) {
            $item = LeaderboardItemListener::getLeaderboardItem(); // ou uma função similar
            $sender->getInventory()->addItem($item);
            $sender->sendMessage("§aVocê recebeu o item de criação de leaderboard flutuante!");
        } else {
            $sender->sendMessage("§cSem permissão.");
        }
        return true;
    }

    // Se for "/clan" normal, abre o menu
    $this->plugin->getFormManager()->openMainMenu($sender);
    return true;
}
}