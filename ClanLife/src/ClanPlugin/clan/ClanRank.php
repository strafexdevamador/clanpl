<?php

declare(strict_types=1);

namespace ClanPlugin\clan;

class ClanRank {
    public const LEADER = "leader";
    public const SUBLEADER = "subleader";
    public const MODERATOR = "moderator";
    public const ELITE = "elite";
    public const MEMBER = "member";

    public const PERM_INVITE = "invite";
    public const PERM_KICK = "kick";
    public const PERM_PROMOTE = "promote";
    public const PERM_EDIT_TAG = "edit_tag";
    public const PERM_EDIT_DESC = "edit_desc";
    public const PERM_MUTE = "mute";
    public const PERM_CHAT = "chat";

    public static function getDisplayName(string $rank): string {
        return match($rank) {
            self::LEADER => "Líder",
            self::SUBLEADER => "Sub-líder",
            self::MODERATOR => "Moderador",
            self::ELITE => "Elite",
            self::MEMBER => "Membro",
            default => "Membro"
        };
    }

    public static function getNextRank(string $current): ?string {
        return match($current) {
            self::MEMBER => self::ELITE,
            self::ELITE => self::MODERATOR,
            self::MODERATOR => self::SUBLEADER,
            default => null
        };
    }

    public static function getOrder(string $rank): int {
        return match($rank) {
            self::LEADER => 1,
            self::SUBLEADER => 2,
            self::MODERATOR => 3,
            self::ELITE => 4,
            self::MEMBER => 5,
            default => 6
        };
    }

    public static function hasPermission(string $rank, string $permission): bool {
        if ($rank === self::LEADER) return true;
        switch ($permission) {
            case self::PERM_INVITE:
            case self::PERM_KICK:
            case self::PERM_EDIT_TAG:
            case self::PERM_EDIT_DESC:
                return $rank === self::SUBLEADER || $rank === self::MODERATOR;
            case self::PERM_PROMOTE:
                return $rank === self::SUBLEADER;
            case self::PERM_MUTE:
                return $rank === self::SUBLEADER || $rank === self::MODERATOR;
            case self::PERM_CHAT:
                return true;
            default:
                return false;
        }
    }
}