<?php

declare(strict_types=1);

namespace ClanPlugin\database;

use SQLite3;
use ClanPlugin\Main;

class MessagesDatabase {
    private SQLite3 $db;
    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->db = new SQLite3($plugin->getDataFolder() . "clan_messages.db");
        $this->initTable();
    }

    private function initTable(): void {
        $this->db->exec("CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            clan TEXT NOT NULL,
            sender TEXT NOT NULL,
            message TEXT NOT NULL,
            timestamp INTEGER NOT NULL
        )");
        // Criar índices para busca rápida
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_clan_timestamp ON messages (clan, timestamp DESC)");
    }

    /**
     * Adiciona uma mensagem e mantém apenas as 15 mais recentes por clã.
     */
    public function addMessage(string $clan, string $sender, string $message): void {
        // Inserir nova mensagem
        $stmt = $this->db->prepare("INSERT INTO messages (clan, sender, message, timestamp) VALUES (:clan, :sender, :message, :time)");
        $stmt->bindValue(":clan", $clan, SQLITE3_TEXT);
        $stmt->bindValue(":sender", $sender, SQLITE3_TEXT);
        $stmt->bindValue(":message", $message, SQLITE3_TEXT);
        $stmt->bindValue(":time", time(), SQLITE3_INTEGER);
        $stmt->execute();

        // Manter apenas as 15 mais recentes
        $this->db->exec("DELETE FROM messages WHERE id NOT IN (
            SELECT id FROM messages WHERE clan = '$clan' ORDER BY timestamp DESC LIMIT 15
        )");
    }

    /**
     * Retorna as últimas $limit mensagens do clã.
     */
    public function getMessages(string $clan, int $limit = 15): array {
        $stmt = $this->db->prepare("SELECT sender, message, timestamp FROM messages WHERE clan = :clan ORDER BY timestamp DESC LIMIT :limit");
        $stmt->bindValue(":clan", $clan, SQLITE3_TEXT);
        $stmt->bindValue(":limit", $limit, SQLITE3_INTEGER);
        $result = $stmt->execute();

        $messages = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $messages[] = $row;
        }
        return array_reverse($messages); // Ordem cronológica crescente
    }

    /**
     * Remove todas as mensagens de um clã (ao deletar o clã).
     */
    public function deleteClanMessages(string $clan): void {
        $this->db->exec("DELETE FROM messages WHERE clan = '$clan'");
    }

    public function close(): void {
        $this->db->close();
    }
}<?php

declare(strict_types=1);

namespace ClanPlugin\database;

use SQLite3;
use ClanPlugin\Main;

class MessagesDatabase {
    private SQLite3 $db;
    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->db = new SQLite3($plugin->getDataFolder() . "clan_messages.db");
        $this->initTable();
    }

    private function initTable(): void {
        $this->db->exec("CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            clan TEXT NOT NULL,
            sender TEXT NOT NULL,
            message TEXT NOT NULL,
            timestamp INTEGER NOT NULL
        )");
        // Criar índices para busca rápida
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_clan_timestamp ON messages (clan, timestamp DESC)");
    }

    /**
     * Adiciona uma mensagem e mantém apenas as 15 mais recentes por clã.
     */
    public function addMessage(string $clan, string $sender, string $message): void {
        // Inserir nova mensagem
        $stmt = $this->db->prepare("INSERT INTO messages (clan, sender, message, timestamp) VALUES (:clan, :sender, :message, :time)");
        $stmt->bindValue(":clan", $clan, SQLITE3_TEXT);
        $stmt->bindValue(":sender", $sender, SQLITE3_TEXT);
        $stmt->bindValue(":message", $message, SQLITE3_TEXT);
        $stmt->bindValue(":time", time(), SQLITE3_INTEGER);
        $stmt->execute();

        // Manter apenas as 15 mais recentes
        $this->db->exec("DELETE FROM messages WHERE id NOT IN (
            SELECT id FROM messages WHERE clan = '$clan' ORDER BY timestamp DESC LIMIT 15
        )");
    }

    /**
     * Retorna as últimas $limit mensagens do clã.
     */
    public function getMessages(string $clan, int $limit = 15): array {
        $stmt = $this->db->prepare("SELECT sender, message, timestamp FROM messages WHERE clan = :clan ORDER BY timestamp DESC LIMIT :limit");
        $stmt->bindValue(":clan", $clan, SQLITE3_TEXT);
        $stmt->bindValue(":limit", $limit, SQLITE3_INTEGER);
        $result = $stmt->execute();

        $messages = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $messages[] = $row;
        }
        return array_reverse($messages); // Ordem cronológica crescente
    }

    /**
     * Remove todas as mensagens de um clã (ao deletar o clã).
     */
    public function deleteClanMessages(string $clan): void {
        $this->db->exec("DELETE FROM messages WHERE clan = '$clan'");
    }

    public function close(): void {
        $this->db->close();
    }
}