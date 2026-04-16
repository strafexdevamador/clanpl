<?php

declare(strict_types=1);

namespace ClanPlugin\floating;

use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\object\ArmorStand;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ClanPlugin\Main;

class FloatingTextManager {
    private Main $plugin;
    /** @var ArmorStand[] */
    private array $floatingTexts = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Cria um texto flutuante no local onde o player está olhando.
     */
    public function createFloatingText(Player $player): bool {
        try {
            $targetBlock = $player->getTargetBlock(5);
            if ($targetBlock === null) {
                $pos = $player->getPosition()->add($player->getDirectionVector()->multiply(3));
                $pos->y = $player->getPosition()->getFloorY();
            } else {
                $pos = $targetBlock->getPosition()->add(0.5, 1.5, 0.5);
            }

            $world = $player->getWorld();
            $armorStand = new ArmorStand(Location::fromObject($pos, $world, 0, 0));
            $armorStand->setInvisible(true);
            $armorStand->setNoClientCollision(true);
            $armorStand->setGravity(false);
            $armorStand->setNameTagAlwaysVisible(true);
            $armorStand->setNameTag(TextFormat::GOLD . "📊 Leaderboard\n" . TextFormat::GRAY . "Clique para ver!");
            $armorStand->setImmobile(true);
            $armorStand->getNamedTag()->setString("leaderboard_text", "true");
            $armorStand->spawnToAll();

            $this->floatingTexts[$armorStand->getId()] = $armorStand;
            return true;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao criar floating text: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove o texto flutuante que o player está olhando.
     */
    public function removeFloatingText(Player $player): bool {
        try {
            $target = $player->getTargetEntity(5);
            if ($target instanceof ArmorStand && $target->getNamedTag()->getTag("leaderboard_text") !== null) {
                $target->flagForDespawn();
                unset($this->floatingTexts[$target->getId()]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro ao remover floating text: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Abre o menu leaderboard quando o player interage com um texto flutuante.
     */
    public function onInteractWithLeaderboard(Player $player, Entity $entity): void {
        try {
            if ($entity instanceof ArmorStand && $entity->getNamedTag()->getTag("leaderboard_text") !== null) {
                $this->plugin->getFormManager()->openLeaderboardMenu($player);
            }
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Erro na interação com leaderboard: " . $e->getMessage());
        }
    }
}
