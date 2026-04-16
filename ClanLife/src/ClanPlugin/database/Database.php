<?php

declare(strict_types=1);

namespace ClanPlugin\database;

use pocketmine\utils\Config;
use SQLite3;
use ClanPlugin\Main;
use ClanPlugin\clan\Clan;
use ClanPlugin\clan\ClanRank;

class Database {
    private SQLite3 $db;
    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->db = new SQLite3($plugin->getDataFolder() . "clans.db");
        $this->initTables();
    }

    private function initTables(): void {
        $this->db->exec("CREATE TABLE IF NOT EXISTS clans (
            name TEXT PRIMARY KEY,
            tag TEXT,
            tagColor TEXT,
            description TEXT,
            leader TEXT,
            level INTEGER DEFAULT 1,
            kills INTEGER DEFAULT 0,
            created_at INTEGER
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS players (
            player TEXT PRIMARY KEY,
            clan TEXT,
            rank TEXT,
            kills INTEGER DEFAULT 0,
            joined_at INTEGER
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS invites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            clan TEXT,
            player TEXT,
            invited_by TEXT,
            timestamp INTEGER
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            player TEXT,
            type TEXT,
            data TEXT,
            seen INTEGER DEFAULT 0,
            timestamp INTEGER
        )");
    }

    public function close(): void {
        $this->db->close();
    }

    // Clan methods
    public function createClan(string $name, string $tag, string $tagColor, string $leader): bool {
        $stmt = $this->db->prepare("INSERT INTO clans (name, tag, tagColor, leader, created_at) VALUES (:name, :tag, :tagColor, :leader, :created)");
        $stmt->bindValue(":name", $name, SQLITE3_TEXT);
        $stmt->bindValue(":tag", $tag, SQLITE3_TEXT);
        $stmt->bindValue(":tagColor", $tagColor, SQLITE3_TEXT);
        $stmt->bindValue(":leader", $leader, SQLITE3_TEXT);
        $stmt->bindValue(":created", time(), SQLITE3_INTEGER);
        return $stmt->execute() instanceof SQLite3Result;
    }

    public function deleteClan(string $clanName): bool {
        $this->db->exec("DELETE FROM clans WHERE name = '$clanName'");
        $this->db->exec("UPDATE players SET clan = NULL, rank = NULL WHERE clan = '$clanName'");
        $this->db->exec("DELETE FROM messages WHERE clan = '$clanName'");
        return true;
    }

    public function getClan(string $clanName): ?array {
        $result = $this->db->query("SELECT * FROM clans WHERE name = '$clanName'");
        $data = $result->fetchArray(SQLITE3_ASSOC);
        return $data ?: null;
    }

    public function getAllClans(): array {
        $result = $this->db->query("SELECT * FROM clans ORDER BY kills DESC");
        $clans = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $clans[] = $row;
        }
        return $clans;
    }

    public function updateClanKills(string $clanName, int $kills): void {
        $this->db->exec("UPDATE clans SET kills = $kills WHERE name = '$clanName'");
    }

    public function updateClanLevel(string $clanName, int $level): void {
        $this->db->exec("UPDATE clans SET level = $level WHERE name = '$clanName'");
    }

    public function updateClanDescription(string $clanName, string $desc): void {
        $desc = SQLite3::escapeString($desc);
        $this->db->exec("UPDATE clans SET description = '$desc' WHERE name = '$clanName'");
    }

    public function updateClanTag(string $clanName, string $tag, string $color): void {
        $this->db->exec("UPDATE clans SET tag = '$tag', tagColor = '$color' WHERE name = '$clanName'");
    }

    // Player methods
    public function addPlayerToClan(string $player, string $clan, string $rank): bool {
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO players (player, clan, rank, joined_at) VALUES (:player, :clan, :rank, :joined)");
        $stmt->bindValue(":player", $player, SQLITE3_TEXT);
        $stmt->bindValue(":clan", $clan, SQLITE3_TEXT);
        $stmt->bindValue(":rank", $rank, SQLITE3_TEXT);
        $stmt->bindValue(":joined", time(), SQLITE3_INTEGER);
        return $stmt->execute() instanceof SQLite3Result;
    }

    public function removePlayerFromClan(string $player): void {
        $this->db->exec("UPDATE players SET clan = NULL, rank = NULL WHERE player = '$player'");
    }

    public function getPlayerClan(string $player): ?array {
        $result = $this->db->query("SELECT * FROM players WHERE player = '$player'");
        $data = $result->fetchArray(SQLITE3_ASSOC);
        return $data ?: null;
    }

    public function getClanMembers(string $clanName): array {
        $result = $this->db->query("SELECT * FROM players WHERE clan = '$clanName' ORDER BY 
            CASE rank 
                WHEN 'leader' THEN 1 
                WHEN 'subleader' THEN 2 
                WHEN 'elite' THEN 3 
                ELSE 4 
            END, kills DESC");
        $members = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $members[] = $row;
        }
        return $members;
    }

    public function updatePlayerRank(string $player, string $rank): void {
        $this->db->exec("UPDATE players SET rank = '$rank' WHERE player = '$player'");
    }

    public function updatePlayerKills(string $player, int $kills): void {
        $this->db->exec("UPDATE players SET kills = $kills WHERE player = '$player'");
    }

    public function getTopPlayersByKills(int $limit = 10): array {
        $result = $this->db->query("SELECT player, kills FROM players ORDER BY kills DESC LIMIT $limit");
        $players = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $players[] = $row;
        }
        return $players;
    }

    // Messages methods
    
    // Invites methods
    public function addInvite(string $clan, string $player, string $invitedBy): void {
        $stmt = $this->db->prepare("INSERT INTO invites (clan, player, invited_by, timestamp) VALUES (:clan, :player, :invitedBy, :time)");
        $stmt->bindValue(":clan", $clan, SQLITE3_TEXT);
        $stmt->bindValue(":player", $player, SQLITE3_TEXT);
        $stmt->bindValue(":invitedBy", $invitedBy, SQLITE3_TEXT);
        $stmt->bindValue(":time", time(), SQLITE3_INTEGER);
        $stmt->execute();
    }

    public function getInvite(string $player, string $clan): ?array {
        $result = $this->db->query("SELECT * FROM invites WHERE player = '$player' AND clan = '$clan'");
        $data = $result->fetchArray(SQLITE3_ASSOC);
        return $data ?: null;
    }

    public function getInvitesForPlayer(string $player): array {
        $result = $this->db->query("SELECT * FROM invites WHERE player = '$player'");
        $invites = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $invites[] = $row;
        }
        return $invites;
    }

    public function removeInvite(string $player, string $clan): void {
        $this->db->exec("DELETE FROM invites WHERE player = '$player' AND clan = '$clan'");
    }

    // Notifications methods
    public function addNotification(string $player, string $type, array $data): void {
        $stmt = $this->db->prepare("INSERT INTO notifications (player, type, data, timestamp) VALUES (:player, :type, :data, :time)");
        $stmt->bindValue(":player", $player, SQLITE3_TEXT);
        $stmt->bindValue(":type", $type, SQLITE3_TEXT);
        $stmt->bindValue(":data", json_encode($data), SQLITE3_TEXT);
        $stmt->bindValue(":time", time(), SQLITE3_INTEGER);
        $stmt->execute();
    }

    public function getNotifications(string $player): array {
        $result = $this->db->query("SELECT * FROM notifications WHERE player = '$player' AND seen = 0 ORDER BY timestamp DESC");
        $notifications = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $row['data'] = json_decode($row['data'], true);
            $notifications[] = $row;
        }
        return $notifications;
    }

    public function markNotificationSeen(int $id): void {
        $this->db->exec("UPDATE notifications SET seen = 1 WHERE id = $id");
    }

    public function clearNotifications(string $player): void {
        $this->db->exec("DELETE FROM notifications WHERE player = '$player'");
    }
}