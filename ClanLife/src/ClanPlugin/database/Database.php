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
        try {
            $stmt = $this->db->prepare("INSERT INTO clans (name, tag, tagColor, leader, created_at) VALUES (:name, :tag, :tagColor, :leader, :created)");
            $stmt->bindValue(":name", $name, SQLITE3_TEXT);
            $stmt->bindValue(":tag", $tag, SQLITE3_TEXT);
            $stmt->bindValue(":tagColor", $tagColor, SQLITE3_TEXT);
            $stmt->bindValue(":leader", $leader, SQLITE3_TEXT);
            $stmt->bindValue(":created", time(), SQLITE3_INTEGER);
            return $stmt->execute() instanceof \SQLite3Result;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao criar clã: " . $e->getMessage());
            return false;
        }
    }

    public function deleteClan(string $clanName): bool {
        try {
            $clanName = SQLite3::escapeString($clanName);
            $this->db->exec("DELETE FROM clans WHERE name = '$clanName'");
            $this->db->exec("UPDATE players SET clan = NULL, rank = NULL WHERE clan = '$clanName'");
            return true;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao deletar clã: " . $e->getMessage());
            return false;
        }
    }

    public function getClan(string $clanName): ?array {
        try {
            $clanName = SQLite3::escapeString($clanName);
            $result = $this->db->query("SELECT * FROM clans WHERE name = '$clanName'");
            return $result->fetchArray(SQLITE3_ASSOC) ?: null;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao buscar clã: " . $e->getMessage());
            return null;
        }
    }

    public function getAllClans(): array {
        try {
            $result = $this->db->query("SELECT * FROM clans ORDER BY kills DESC");
            $clans = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $clans[] = $row;
            }
            return $clans;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao buscar clãs: " . $e->getMessage());
            return [];
        }
    }

    public function updateClanKills(string $clanName, int $kills): void {
        try {
            $clanName = SQLite3::escapeString($clanName);
            $this->db->exec("UPDATE clans SET kills = $kills WHERE name = '$clanName'");
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao atualizar kills: " . $e->getMessage());
        }
    }

    public function updateClanLevel(string $clanName, int $level): void {
        try {
            $clanName = SQLite3::escapeString($clanName);
            $this->db->exec("UPDATE clans SET level = $level WHERE name = '$clanName'");
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao atualizar level: " . $e->getMessage());
        }
    }

    public function updateClanDescription(string $clanName, string $desc): void {
        try {
            $clanName = SQLite3::escapeString($clanName);
            $desc = SQLite3::escapeString($desc);
            $this->db->exec("UPDATE clans SET description = '$desc' WHERE name = '$clanName'");
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao atualizar descrição: " . $e->getMessage());
        }
    }

    public function updateClanTag(string $clanName, string $tag, string $color): void {
        try {
            $clanName = SQLite3::escapeString($clanName);
            $tag = SQLite3::escapeString($tag);
            $color = SQLite3::escapeString($color);
            $this->db->exec("UPDATE clans SET tag = '$tag', tagColor = '$color' WHERE name = '$clanName'");
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao atualizar tag: " . $e->getMessage());
        }
    }

    // Player methods
    public function addPlayerToClan(string $player, string $clan, string $rank): bool {
        try {
            $stmt = $this->db->prepare("INSERT OR REPLACE INTO players (player, clan, rank, joined_at) VALUES (:player, :clan, :rank, :joined)");
            $stmt->bindValue(":player", $player, SQLITE3_TEXT);
            $stmt->bindValue(":clan", $clan, SQLITE3_TEXT);
            $stmt->bindValue(":rank", $rank, SQLITE3_TEXT);
            $stmt->bindValue(":joined", time(), SQLITE3_INTEGER);
            return $stmt->execute() instanceof \SQLite3Result;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao adicionar player ao clã: " . $e->getMessage());
            return false;
        }
    }

    public function removePlayerFromClan(string $player): void {
        try {
            $player = SQLite3::escapeString($player);
            $this->db->exec("UPDATE players SET clan = NULL, rank = NULL WHERE player = '$player'");
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao remover player do clã: " . $e->getMessage());
        }
    }

    public function getPlayerClan(string $player): ?array {
        try {
            $player = SQLite3::escapeString($player);
            $result = $this->db->query("SELECT * FROM players WHERE player = '$player'");
            return $result->fetchArray(SQLITE3_ASSOC) ?: null;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao buscar clã do player: " . $e->getMessage());
            return null;
        }
    }

    public function getClanMembers(string $clanName): array {
        try {
            $clanName = SQLite3::escapeString($clanName);
            $result = $this->db->query("SELECT * FROM players WHERE clan = '$clanName' ORDER BY 
                CASE rank 
                    WHEN 'leader' THEN 1 
                    WHEN 'subleader' THEN 2 
                    WHEN 'moderator' THEN 3
                    WHEN 'elite' THEN 4
                    ELSE 5 
                END, kills DESC");
            $members = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $members[] = $row;
            }
            return $members;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao buscar membros: " . $e->getMessage());
            return [];
        }
    }

    public function updatePlayerRank(string $player, string $rank): void {
        try {
            $player = SQLite3::escapeString($player);
            $rank = SQLite3::escapeString($rank);
            $this->db->exec("UPDATE players SET rank = '$rank' WHERE player = '$player'");
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao atualizar rank: " . $e->getMessage());
        }
    }

    public function updatePlayerKills(string $player, int $kills): void {
        try {
            $player = SQLite3::escapeString($player);
            $this->db->exec("UPDATE players SET kills = $kills WHERE player = '$player'");
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao atualizar kills do player: " . $e->getMessage());
        }
    }

    public function getTopPlayersByKills(int $limit = 10): array {
        try {
            $result = $this->db->query("SELECT player, kills FROM players ORDER BY kills DESC LIMIT $limit");
            $players = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $players[] = $row;
            }
            return $players;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao buscar top players: " . $e->getMessage());
            return [];
        }
    }

    // Invites methods
    public function addInvite(string $clan, string $player, string $invitedBy): void {
        try {
            $stmt = $this->db->prepare("INSERT INTO invites (clan, player, invited_by, timestamp) VALUES (:clan, :player, :invitedBy, :time)");
            $stmt->bindValue(":clan", $clan, SQLITE3_TEXT);
            $stmt->bindValue(":player", $player, SQLITE3_TEXT);
            $stmt->bindValue(":invitedBy", $invitedBy, SQLITE3_TEXT);
            $stmt->bindValue(":time", time(), SQLITE3_INTEGER);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao adicionar convite: " . $e->getMessage());
        }
    }

    public function getInvite(string $player, string $clan): ?array {
        try {
            $player = SQLite3::escapeString($player);
            $clan = SQLite3::escapeString($clan);
            $result = $this->db->query("SELECT * FROM invites WHERE player = '$player' AND clan = '$clan'");
            return $result->fetchArray(SQLITE3_ASSOC) ?: null;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao buscar convite: " . $e->getMessage());
            return null;
        }
    }

    public function getInvitesForPlayer(string $player): array {
        try {
            $player = SQLite3::escapeString($player);
            $result = $this->db->query("SELECT * FROM invites WHERE player = '$player'");
            $invites = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $invites[] = $row;
            }
            return $invites;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao buscar convites: " . $e->getMessage());
            return [];
        }
    }

    public function removeInvite(string $player, string $clan): void {
        try {
            $player = SQLite3::escapeString($player);
            $clan = SQLite3::escapeString($clan);
            $this->db->exec("DELETE FROM invites WHERE player = '$player' AND clan = '$clan'");
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao remover convite: " . $e->getMessage());
        }
    }

    // Notifications methods
    public function addNotification(string $player, string $type, array $data): void {
        try {
            $stmt = $this->db->prepare("INSERT INTO notifications (player, type, data, timestamp) VALUES (:player, :type, :data, :time)");
            $stmt->bindValue(":player", $player, SQLITE3_TEXT);
            $stmt->bindValue(":type", $type, SQLITE3_TEXT);
            $stmt->bindValue(":data", json_encode($data), SQLITE3_TEXT);
            $stmt->bindValue(":time", time(), SQLITE3_INTEGER);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao adicionar notificação: " . $e->getMessage());
        }
    }

    public function getNotifications(string $player): array {
        try {
            $player = SQLite3::escapeString($player);
            $result = $this->db->query("SELECT * FROM notifications WHERE player = '$player' AND seen = 0 ORDER BY timestamp DESC");
            $notifications = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $row['data'] = json_decode($row['data'], true);
                $notifications[] = $row;
            }
            return $notifications;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao buscar notificações: " . $e->getMessage());
            return [];
        }
    }

    public function markNotificationSeen(int $id): void {
        try {
            $this->db->exec("UPDATE notifications SET seen = 1 WHERE id = $id");
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao marcar notificação como vista: " . $e->getMessage());
        }
    }

    public function clearNotifications(string $player): void {
        try {
            $player = SQLite3::escapeString($player);
            $this->db->exec("DELETE FROM notifications WHERE player = '$player'");
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao limpar notificações: " . $e->getMessage());
        }
    }
}
