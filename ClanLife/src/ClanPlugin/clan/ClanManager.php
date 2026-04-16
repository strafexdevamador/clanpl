<?php

declare(strict_types=1);

namespace ClanPlugin\clan;

use pocketmine\player\Player;
use ClanPlugin\Main;

class ClanManager {
    private Main $plugin;
    private array $clans = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->loadClans();
    }

    private function loadClans(): void {
        $clansData = $this->plugin->getDatabase()->getAllClans();
        foreach ($clansData as $data) {
            $this->clans[$data['name']] = new Clan($this->plugin, $data);
        }
    }

    public function getClan(string $name): ?Clan {
        return $this->clans[$name] ?? null;
    }

    public function getClanByPlayer(string $player): ?Clan {
        $playerData = $this->plugin->getDatabase()->getPlayerClan($player);
        if ($playerData && isset($playerData['clan'])) {
            return $this->getClan($playerData['clan']);
        }
        return null;
    }

    public function createClan(string $name, string $tag, string $tagColor, Player $leader): bool {
        if ($this->getClan($name) !== null) {
            return false;
        }
        if ($this->getClanByPlayer($leader->getName()) !== null) {
            return false;
        }
        $this->plugin->getDatabase()->createClan($name, $tag, $tagColor, $leader->getName());
        $clanData = $this->plugin->getDatabase()->getClan($name);
        $this->clans[$name] = new Clan($this->plugin, $clanData);
        $this->plugin->getDatabase()->addPlayerToClan($leader->getName(), $name, ClanRank::LEADER);
        return true;
    }

    public function deleteClan(Clan $clan, Player $player): bool {
        if (!$clan->isLeader($player->getName())) {
            return false;
        }
        $this->plugin->getMessagesDatabase()->deleteClanMessages($clan->getName())
        $clan->delete();
        unset($this->clans[$clan->getName()]);
        return true;
    }

    public function invitePlayer(Clan $clan, Player $inviter, string $targetName): bool {
        if (!$clan->hasPermission($inviter->getName(), ClanRank::PERM_INVITE)) {
            return false;
        }
        $target = $this->plugin->getServer()->getPlayerExact($targetName);
        if (!$target) {
            return false;
        }
        if ($this->getClanByPlayer($targetName) !== null) {
            return false;
        }
        if ($this->plugin->getDatabase()->getInvite($targetName, $clan->getName())) {
            return false;
        }
        $this->plugin->getDatabase()->addInvite($clan->getName(), $targetName, $inviter->getName());
        $target->sendMessage("§aVocê foi convidado para o clã §e{$clan->getName()} §apor §e{$inviter->getName()}§a. Use /clan accept {$clan->getName()} para aceitar.");
        $this->plugin->getDatabase()->addNotification($targetName, "clan_invite", [
            'clan' => $clan->getName(),
            'inviter' => $inviter->getName()
        ]);
        return true;
    }

    public function acceptInvite(Player $player, string $clanName): bool {
        $invite = $this->plugin->getDatabase()->getInvite($player->getName(), $clanName);
        if (!$invite) {
            return false;
        }
        $clan = $this->getClan($clanName);
        if (!$clan) {
            return false;
        }
        if ($clan->getMemberCount() >= $clan->getMaxMembers()) {
            $player->sendMessage("§cO clã está cheio!");
            return false;
        }
        $clan->addMember($player->getName(), ClanRank::MEMBER);
        $this->plugin->getDatabase()->removeInvite($player->getName(), $clanName);
        $player->sendMessage("§aVocê entrou no clã §e{$clanName}§a!");
        return true;
    }

    public function kickMember(Clan $clan, Player $kicker, string $targetName): bool {
        if (!$clan->hasPermission($kicker->getName(), ClanRank::PERM_KICK)) {
            return false;
        }
        if (!$clan->isMember($targetName)) {
            return false;
        }
        if ($clan->isLeader($targetName)) {
            return false;
        }
        $clan->removeMember($targetName);
        $target = $this->plugin->getServer()->getPlayerExact($targetName);
        if ($target) {
            $target->sendMessage("§cVocê foi expulso do clã §e{$clan->getName()}§c!");
        }
        return true;
    }

    public function promoteMember(Clan $clan, Player $promoter, string $targetName): bool {
        if (!$clan->hasPermission($promoter->getName(), ClanRank::PERM_PROMOTE)) {
            return false;
        }
        $currentRank = $clan->getRankOf($targetName);
        if (!$currentRank) {
            return false;
        }
        $newRank = ClanRank::getNextRank($currentRank);
        if (!$newRank) {
            return false;
        }
        $clan->setRank($targetName, $newRank);
        $target = $this->plugin->getServer()->getPlayerExact($targetName);
        if ($target) {
            $target->sendMessage("§aVocê foi promovido a §e" . ClanRank::getDisplayName($newRank) . " §ano clã!");
        }
        return true;
    }

    public function editTag(Clan $clan, Player $player, string $tag, string $color): bool {
        if (!$clan->hasPermission($player->getName(), ClanRank::PERM_EDIT_TAG)) {
            return false;
        }
        $this->plugin->getDatabase()->updateClanTag($clan->getName(), $tag, $color);
        $clan->tag = $tag;
        $clan->tagColor = $color;
        return true;
    }

    public function editDescription(Clan $clan, Player $player, string $desc): bool {
        if (!$clan->hasPermission($player->getName(), ClanRank::PERM_EDIT_DESC)) {
            return false;
        }
        $this->plugin->getDatabase()->updateClanDescription($clan->getName(), $desc);
        $clan->description = $desc;
        return true;
    }

    public function addKill(string $playerName): void {
        $playerData = $this->plugin->getDatabase()->getPlayerClan($playerName);
        if ($playerData && isset($playerData['clan'])) {
            $kills = (int)($playerData['kills'] ?? 0) + 1;
            $this->plugin->getDatabase()->updatePlayerKills($playerName, $kills);
            $clan = $this->getClan($playerData['clan']);
            if ($clan) {
                $clan->addKills(1);
            }
        }
    }

    public function getTopClans(int $limit = 10): array {
        return $this->plugin->getDatabase()->getAllClans();
    }

    public function getTopPlayers(int $limit = 10): array {
        return $this->plugin->getDatabase()->getTopPlayersByKills($limit);
    }

    public function getClanList(): array {
        return $this->clans;
    }
}