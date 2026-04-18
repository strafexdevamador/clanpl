<?php

declare(strict_types=1);

namespace ClanPlugin;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use ClanPlugin\database\Database;
use ClanPlugin\database\MessagesDatabase;
use ClanPlugin\clan\ClanManager;
use ClanPlugin\command\ClanCommand;
use ClanPlugin\command\LfTopsCommand;
use ClanPlugin\forms\FormManager;
use ClanPlugin\event\EventListener;
use ClanPlugin\floating\FloatingTextManager;

class Main extends PluginBase {

    private static Main $instance;
    private Database $database;
    private MessagesDatabase $messagesDb;
    private ClanManager $clanManager;
    private FormManager $formManager;
    private FloatingTextManager $floatingTextManager;

    public function onLoad(): void {
        self::$instance = $this;
    }

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->database = new Database($this);
        $this->clanManager = new ClanManager($this);
        $this->formManager = new FormManager($this);
        $this->messagesDb = new MessagesDatabase($this);

        $this->getServer()->getCommandMap()->register("clan", new ClanCommand($this));
$this->getServer()->getCommandMap()->register("lftops", new LfTopsCommand($this));
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->floatingTextManager = new FloatingTextManager($this);

        $this->getLogger()->info("ClanPlugin habilitado!");
    }
    public function getFloatingTextManager(): FloatingTextManager {
        return $this->floatingTextManager;
    }

    public function onDisable(): void {
        $this->database->close();
    }

    public static function getInstance(): Main {
        return self::$instance;
    }

    public function getDatabase(): Database {
        return $this->database;
    }
    public function getMessagesDatabase(): MessagesDatabase { 
        return $this->messagesDb;
    }

    public function getClanManager(): ClanManager {
        return $this->clanManager;
    }

    public function getFormManager(): FormManager {
        return $this->formManager;
    }
}