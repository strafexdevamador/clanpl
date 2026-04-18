<?php

declare(strict_types=1);

namespace ClanPlugin\clan;

use ClanPlugin\Main;

class Clan {
    private Main $plugin;
    private string $name;
    private string $tag;
    private string $tagColor;
    private string $description;
    private string $leader;
    private int $level;
    private int $kills;
    private array $members = [];

    public function __construct(Main $plugin, array $data) {
        $this->plugin = $plugin;
        $this->name = $data['name'];
        $this->tag = $data['tag'];
        $this->tagColor = $data['tagColor'] ?? '§f';
        $this->description = $data['description'] ?? '';
        $this->leader = $data['leader'];
        $this->level = (int)($data['level'] ?? 1);
        $this->kills = (int)($data['kills'] ?? 0);
        $this->loadMembers();
    }

    private function loadMembers(): void {
        $this->members = $this->plugin->getDatabase()->getClanMembers($this->name);
    }

    public function getName(): string {
        return $this->name;
    }

    public function getTag(): string {
        return $this->tagColor . $this->tag . "§r";
    }

    public function getRawTag(): string {
        return $this->tag;
    }

    public function getTagColor(): string {
        return $this->tagColor;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getLeader(): string {
        return $this->leader;
    }

    public function getLevel(): int {
        return $this->level;
    }

    public function getKills(): int {
        return $this->kills;
    }

    public function getMembers(): array {
        return $this->members;
    }

    public function getMemberCount(): int {
        return count($this->members);
    }

    public function getMaxMembers(): int {
        return 5 + ($this->level * 2); // Example: level 1 = 7, level 10 = 25
    }

    public function getRankOf(string $player): ?string {
        foreach ($this->members as $member) {
            if ($member['player'] === $player) {
                return $member['rank'];
            }
        }
        return null;
    }

    public function isMember(string $player): bool {
        return $this->getRankOf($player) !== null;
    }

    public function isLeader(string $player): bool {
        return $this->leader === $player;
    }

    public function isSubLeader(string $player): bool {
        $rank = $this->getRankOf($player);
        return $rank === ClanRank::SUBLEADER;
    }

    public function hasPermission(string $player, string $permission): bool {
    if ($this->isLeader($player)) return true;
    $rank = $this->getRankOf($player);
    return ClanRank::hasPermission($rank, $permission);
}

    public function addMember(string $player, string $rank): bool {
        return $this->plugin->getDatabase()->addPlayerToClan($player, $this->name, $rank);
    }

    public function removeMember(string $player): void {
        $this->plugin->getDatabase()->removePlayerFromClan($player);
        $this->loadMembers();
    }

    public function setRank(string $player, string $rank): void {
        $this->plugin->getDatabase()->updatePlayerRank($player, $rank);
        $this->loadMembers();
    }

    public function addKills(int $amount): void {
        $this->kills += $amount;
        $this->plugin->getDatabase()->updateClanKills($this->name, $this->kills);
        $this->checkLevelUp();
    }

    private function checkLevelUp(): void {
        $newLevel = floor($this->kills / 100) + 1; // 100 kills per level
        if ($newLevel > $this->level) {
            $this->level = $newLevel;
            $this->plugin->getDatabase()->updateClanLevel($this->name, $this->level);
            // Broadcast level up message
            foreach ($this->members as $member) {
                $player = $this->plugin->getServer()->getPlayerExact($member['player']);
                if ($player) {
                    $player->sendMessage("§aSeu clã §e{$this->name} §aatingiu o nível §e{$this->level}§a!");
                }
            }
        }
    }

    
    public function sendMessage(string $sender, string $message): void {
    // Armazenar no banco separado
    $this->plugin->getMessagesDatabase()->addMessage($this->name, $sender, $message);
    $formatted = "§7[§bClã§7] §f{$sender}: §7{$message}";
    foreach ($this->members as $member) {
        $player = $this->plugin->getServer()->getPlayerExact($member['player']);
        if ($player) {
            $player->sendMessage($formatted);
        }
    }
}

public function getMessages(int $limit = 15): array {
    return $this->plugin->getMessagesDatabase()->getMessages($this->name, $limit);
}

    public function delete(): void {
        $this->plugin->getDatabase()->deleteClan($this->name);
    }
}