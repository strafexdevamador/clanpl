<?php

declare(strict_types=1);

namespace ClanPlugin\clan;

use ClanPlugin\Main;

class ClanInvite {
    private Main $plugin;
    private string $clan;
    private string $player;
    private string $invitedBy;
    private int $timestamp;

    public function __construct(Main $plugin, string $clan, string $player, string $invitedBy, int $timestamp) {
        $this->plugin = $plugin;
        $this->clan = $clan;
        $this->player = $player;
        $this->invitedBy = $invitedBy;
        $this->timestamp = $timestamp;
    }

    public function getClan(): string {
        return $this->clan;
    }

    public function getPlayer(): string {
        return $this->player;
    }

    public function getInvitedBy(): string {
        return $this->invitedBy;
    }

    public function getTimestamp(): int {
        return $this->timestamp;
    }

    public function accept(): bool {
        $clan = $this->plugin->getClanManager()->getClan($this->clan);
        if (!$clan) {
            return false;
        }
        if ($clan->getMemberCount() >= $clan->getMaxMembers()) {
            return false;
        }
        return $clan->addMember($this->player, ClanRank::MEMBER);
    }

    public function decline(): void {
        $this->plugin->getDatabase()->removeInvite($this->player, $this->clan);
    }
}
