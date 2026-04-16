<?php

declare(strict_types=1);

namespace ClanPlugin\event;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\entity\object\ArmorStand;
use pocketmine\player\Player;
use ClanPlugin\Main;

class EventListener implements Listener {
    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();

        // Verifica se é o item especial de leaderboard
        if ($item->getNamedTag()->getTag("leaderboard_item") !== null) {
            $event->cancel();
            if ($player->isSneaking()) {
                // Remove o texto flutuante alvo
                if ($this->plugin->getFloatingTextManager()->removeFloatingText($player)) {
                    $player->sendMessage("§aTexto flutuante removido!");
                } else {
                    $player->sendMessage("§cNenhum texto flutuante encontrado.");
                }
            } else {
                // Cria um novo texto flutuante
                if ($this->plugin->getFloatingTextManager()->createFloatingText($player)) {
                    $player->sendMessage("§aTexto flutuante criado!");
                } else {
                    $player->sendMessage("§cNão foi possível criar o texto flutuante.");
                }
            }
            return;
        }

        // Interação com entidade leaderboard (texto flutuante)
        $target = $event->getEntity();
        if ($target !== null) {
            $this->plugin->getFloatingTextManager()->onInteractWithLeaderboard($player, $target);
        }
    }

    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof ArmorStand && $entity->getNamedTag()->getTag("leaderboard_text") !== null) {
            $event->cancel();
        }
    }

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $cause = $player->getLastDamageCause();
        if ($cause instanceof \pocketmine\event\entity\EntityDamageByEntityEvent) {
            $killer = $cause->getDamager();
            if ($killer instanceof Player) {
                $this->plugin->getClanManager()->addKill($killer->getName());
            }
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $invites = $this->plugin->getDatabase()->getInvitesForPlayer($player->getName());
        if (!empty($invites)) {
            $player->sendMessage("§aVocê tem " . count($invites) . " convite(s) de clã pendente(s)! Use §e/clan §apara ver.");
        }
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void {
        // Nenhuma ação especial por enquanto
    }
}
