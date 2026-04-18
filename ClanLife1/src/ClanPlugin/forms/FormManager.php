<?php

declare(strict_types=1);

namespace ClanPlugin\forms;

use pocketmine\player\Player;
use pocketmine\Server;
use ClanPlugin\Main;
use ClanPlugin\clan\Clan;
use ClanPlugin\clan\ClanRank;

class FormManager {
    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function openMainMenu(Player $player): void {
        $clan = $this->plugin->getClanManager()->getClanByPlayer($player->getName());
        if ($clan) {
            $this->openClanMenu($player, $clan);
        } else {
            $this->openNoClanMenu($player);
        }
    }

    private function openNoClanMenu(Player $player): void {
        $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) return;
            switch ($data) {
                case 0:
                    $this->openCreateClanForm($player);
                    break;
                case 1:
                    $this->openClanListForm($player);
                    break;
            }
        });
        $form->setTitle("Central do Clã");
        $form->setContent("§7Você não está em um clã.\n§aEscolha uma opção:");
        $form->addButton("§aCriar Clã");
        $form->addButton("§bLista de Clãs");
        $player->sendForm($form);
    }

    private function openClanMenu(Player $player, Clan $clan): void {
        $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) use ($clan) {
            if ($data === null) return;
            switch ($data) {
                case 0:
                    $this->openClanInfo($player, $clan);
                    break;
                case 1:
                    $this->openMembersMenu($player, $clan);
                    break;
                case 2:
                    $this->openMessagesMenu($player, $clan);
                    break;
                case 3:
                    $this->openManagementMenu($player, $clan);
                    break;
                case 4:
                    $this->openRankingMenu($player);
                    break;
                case 5:
                    $this->openNotificationsMenu($player);
                    break;
            }
        });
        $memberCount = $clan->getMemberCount();
        $maxMembers = $clan->getMaxMembers();
        $form->setTitle("§l" . $clan->getTag() . " §r§7- Nível {$clan->getLevel()}");
        $form->setContent("§7Membros: §f{$memberCount}/{$maxMembers}\n§7Kills do Clã: §f{$clan->getKills()}\n§7Sua posição: §f" . ClanRank::getDisplayName($clan->getRankOf($player->getName())));
        $form->addButton("§aInformações");
        $form->addButton("§bMembros");
        $form->addButton("§eMensagens");
        $form->addButton("§cGerenciar");
        $form->addButton("§6Rankings");
        $form->addButton("§dNotificações");
        $player->sendForm($form);
    }

    private function openClanInfo(Player $player, Clan $clan): void {
        $form = new \jojoe77777\FormAPI\CustomForm(function (Player $player, ?array $data) use ($clan) {
            if ($data === null) return;
            // Just viewing info, no action
            $this->openClanMenu($player, $clan);
        });
        $form->setTitle("Informações do Clã");
        $form->addLabel("§6Nome: §f" . $clan->getName());
        $form->addLabel("§6Tag: §f" . $clan->getTag());
        $form->addLabel("§6Líder: §f" . $clan->getLeader());
        $form->addLabel("§6Nível: §f" . $clan->getLevel());
        $form->addLabel("§6Kills: §f" . $clan->getKills());
        $form->addLabel("§6Descrição:\n§7" . ($clan->getDescription() ?: "Nenhuma descrição"));
        $form->addLabel("§6Membros: §f" . $clan->getMemberCount() . "/" . $clan->getMaxMembers());
        $form->addButton("Voltar");
        $player->sendForm($form);
    }

    private function openMembersMenu(Player $player, Clan $clan): void {
        $members = $clan->getMembers();
        $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) use ($clan, $members) {
            if ($data === null) return;
            if ($data >= 0 && $data < count($members)) {
                $selected = $members[$data];
                if ($selected['player'] !== $player->getName() && $clan->hasPermission($player->getName(), ClanRank::PERM_KICK)) {
                    $this->openMemberActions($player, $clan, $selected['player']);
                } else {
                    $this->openMembersMenu($player, $clan);
                }
            } else {
                $this->openClanMenu($player, $clan);
            }
        });
        $form->setTitle("Membros do Clã");
        $content = "=== ONLINE (" . count(array_filter($members, fn($m) => Server::getInstance()->getPlayerExact($m['player']) !== null)) . ") ===\n";
        $content .= "=== OFFLINE (" . count(array_filter($members, fn($m) => Server::getInstance()->getPlayerExact($m['player']) === null)) . ") ===\n";
        foreach ($members as $member) {
            $online = Server::getInstance()->getPlayerExact($member['player']) !== null;
            $status = $online ? "§a●" : "§c●";
            $rankColor = match($member['rank']) {
                ClanRank::LEADER => "§c",
                ClanRank::SUBLEADER => "§6",
                ClanRank::ELITE => "§b",
                default => "§7"
            };
            $name = $rankColor . ClanRank::getDisplayName($member['rank']) . " §f" . $member['player'];
            $content .= "{$status} {$name} §7(Kills: {$member['kills']})\n";
        }
        $form->setContent($content);
        foreach ($members as $member) {
            $form->addButton($member['player']);
        }
        $form->addButton("§cVoltar");
        $player->sendForm($form);
    }

    private function openMemberActions(Player $player, Clan $clan, string $target): void {
        $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) use ($clan, $target) {
            if ($data === null) return;
            switch ($data) {
                case 0:
                    $this->plugin->getClanManager()->kickMember($clan, $player, $target);
                    break;
                case 1:
                    $this->plugin->getClanManager()->promoteMember($clan, $player, $target);
                    break;
            }
            $this->openMembersMenu($player, $clan);
        });
        $form->setTitle("Gerenciar " . $target);
        $form->addButton("§cExpulsar");
        $form->addButton("§aPromover");
        $form->addButton("§7Voltar");
        $player->sendForm($form);
    }

    private function openMessagesMenu(Player $player, Clan $clan): void {
        $form = new \jojoe77777\FormAPI\CustomForm(function (Player $player, ?array $data) use ($clan) {
            if ($data === null) return;
            if ($data[0] !== null && $data[0] !== "") {
                $clan->sendMessage($player->getName(), $data[0]);
                $player->sendMessage("§aMensagem enviada!");
            }
            $this->openMessagesHistory($player, $clan);
        });
        $form->setTitle("Mensagens do Clã");
        $form->addInput("Nova mensagem:", "Digite sua mensagem...");
        $form->addLabel("§7As últimas 15 mensagens:");
        $messages = $clan->getMessages(15);
        foreach ($messages as $msg) {
            $time = date("H:i", $msg['timestamp']);
            $form->addLabel("§7[{$time}] §f{$msg['sender']}: §7{$msg['message']}");
        }
        $player->sendForm($form);
    }

    private function openMessagesHistory(Player $player, Clan $clan): void {
        $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) use ($clan) {
            if ($data === null) return;
            $this->openClanMenu($player, $clan);
        });
        $form->setTitle("Histórico de Mensagens");
        $content = "";
        $messages = $clan->getMessages(15);
        foreach ($messages as $msg) {
            $time = date("H:i", $msg['timestamp']);
            $content .= "§7[{$time}] §f{$msg['sender']}: §7{$msg['message']}\n";
        }
        $form->setContent($content ?: "§7Nenhuma mensagem.");
        $form->addButton("§aVoltar");
        $player->sendForm($form);
    }

    private function openManagementMenu(Player $player, Clan $clan): void {
        $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) use ($clan) {
            if ($data === null) return;
            switch ($data) {
                case 0:
                    $this->openInviteForm($player, $clan);
                    break;
                case 1:
                    $this->openEditTagForm($player, $clan);
                    break;
                case 2:
                    $this->openEditDescriptionForm($player, $clan);
                    break;
                case 3:
                    $this->openDeleteConfirmation($player, $clan);
                    break;
                default:
                    $this->openClanMenu($player, $clan);
            }
        });
        $form->setTitle("Gerenciar Clã");
        $content = "§7Você tem permissão para:\n";
        if ($clan->hasPermission($player->getName(), ClanRank::PERM_INVITE)) $content .= "§a- Convidar jogadores\n";
        if ($clan->hasPermission($player->getName(), ClanRank::PERM_KICK)) $content .= "§a- Expulsar membros\n";
        if ($clan->hasPermission($player->getName(), ClanRank::PERM_PROMOTE)) $content .= "§a- Promover membros\n";
        if ($clan->hasPermission($player->getName(), ClanRank::PERM_EDIT_TAG)) $content .= "§a- Editar tag\n";
        if ($clan->hasPermission($player->getName(), ClanRank::PERM_EDIT_DESC)) $content .= "§a- Editar descrição\n";
        $form->setContent($content);
        if ($clan->hasPermission($player->getName(), ClanRank::PERM_INVITE)) $form->addButton("§aConvidar");
        if ($clan->hasPermission($player->getName(), ClanRank::PERM_EDIT_TAG)) $form->addButton("§eEditar Tag");
        if ($clan->hasPermission($player->getName(), ClanRank::PERM_EDIT_DESC)) $form->addButton("§eEditar Descrição");
        if ($clan->isLeader($player->getName())) $form->addButton("§cApagar Clã");
        $form->addButton("§7Voltar");
        $player->sendForm($form);
    }

    private function openInviteForm(Player $player, Clan $clan): void {
        $form = new \jojoe77777\FormAPI\CustomForm(function (Player $player, ?array $data) use ($clan) {
            if ($data === null) return;
            if (isset($data[0]) && $data[0] !== "") {
                $this->plugin->getClanManager()->invitePlayer($clan, $player, $data[0]);
            }
            $this->openClanMenu($player, $clan);
        });
        $form->setTitle("Convidar Jogador");
        $form->addInput("Nome do jogador:", "Digite o nome...");
        $player->sendForm($form);
    }

    private function openEditTagForm(Player $player, Clan $clan): void {
        $form = new \jojoe77777\FormAPI\CustomForm(function (Player $player, ?array $data) use ($clan) {
            if ($data === null) return;
            if (isset($data[0]) && isset($data[1])) {
                $color = "§" . $data[1];
                $this->plugin->getClanManager()->editTag($clan, $player, $data[0], $color);
                $player->sendMessage("§aTag atualizada!");
            }
            $this->openClanMenu($player, $clan);
        });
        $form->setTitle("Editar Tag");
        $form->addInput("Tag (sem cores):", $clan->getRawTag());
        $form->addDropdown("Cor:", ["§fBranco", "§aVerde", "§cVermelho", "§eAmarelo", "§bAzul", "§dRoxo", "§6Laranja"]);
        $player->sendForm($form);
    }

    private function openEditDescriptionForm(Player $player, Clan $clan): void {
        $form = new \jojoe77777\FormAPI\CustomForm(function (Player $player, ?array $data) use ($clan) {
            if ($data === null) return;
            if (isset($data[0])) {
                $this->plugin->getClanManager()->editDescription($clan, $player, $data[0]);
                $player->sendMessage("§aDescrição atualizada!");
            }
            $this->openClanMenu($player, $clan);
        });
        $form->setTitle("Editar Descrição");
        $form->addInput("Descrição:", $clan->getDescription());
        $player->sendForm($form);
    }

    private function openDeleteConfirmation(Player $player, Clan $clan): void {
        $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) use ($clan) {
            if ($data === null) return;
            if ($data === 0) {
                if ($this->plugin->getClanManager()->deleteClan($clan, $player)) {
                    $player->sendMessage("§cClã apagado com sucesso!");
                } else {
                    $player->sendMessage("§cVocê não tem permissão!");
                }
                $this->openMainMenu($player);
            } else {
                $this->openClanMenu($player, $clan);
            }
        });
        $form->setTitle("Apagar Clã");
        $form->setContent("§cTem certeza que deseja apagar o clã §e{$clan->getName()}§c? Essa ação é irreversível!");
        $form->addButton("§cSim, apagar");
        $form->addButton("§aNão, voltar");
        $player->sendForm($form);
    }

    private function openRankingMenu(Player $player): void {
        $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) return;
            if ($data === 0) {
                $this->openClanRanking($player);
            } else {
                $this->openPlayerRanking($player);
            }
        });
        $form->setTitle("Rankings");
        $form->setContent("Escolha o tipo de ranking:");
        $form->addButton("§aTop Clãs");
        $form->addButton("§bTop Kills");
        $player->sendForm($form);
    }

    private function openClanRanking(Player $player): void {
        $clans = $this->plugin->getClanManager()->getTopClans();
        $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) return;
            $this->openRankingMenu($player);
        });
        $form->setTitle("Top Clãs");
        $content = "";
        $i = 1;
        foreach ($clans as $clanData) {
            $content .= "§e{$i}º §f{$clanData['name']} §7- §f{$clanData['kills']} kills\n";
            $i++;
        }
        $form->setContent($content ?: "§7Nenhum clã registrado.");
        $form->addButton("§aVoltar");
        $player->sendForm($form);
    }

    private function openPlayerRanking(Player $player): void {
        $players = $this->plugin->getClanManager()->getTopPlayers();
        $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) return;
            $this->openRankingMenu($player);
        });
        $form->setTitle("Top Kills");
        $content = "";
        $i = 1;
        foreach ($players as $playerData) {
            $content .= "§e{$i}º §f{$playerData['player']} §7- §f{$playerData['kills']} kills\n";
            $i++;
        }
        $form->setContent($content ?: "§7Nenhum jogador registrado.");
        $form->addButton("§aVoltar");
        $player->sendForm($form);
    }

    private function openNotificationsMenu(Player $player): void {
        $notifications = $this->plugin->getDatabase()->getNotifications($player->getName());
        $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) use ($notifications) {
            if ($data === null) return;
            if ($data >= 0 && $data < count($notifications)) {
                $notif = $notifications[$data];
                $this->plugin->getDatabase()->markNotificationSeen($notif['id']);
                if ($notif['type'] === 'clan_invite') {
                    $clanName = $notif['data']['clan'];
                    $inviter = $notif['data']['inviter'];
                    $this->openInviteAcceptForm($player, $clanName, $inviter);
                } else {
                    $this->openNotificationsMenu($player);
                }
            } else {
                $this->openMainMenu($player);
            }
        });
        $form->setTitle("Notificações");
        if (empty($notifications)) {
            $form->setContent("§7Nenhuma notificação.");
        } else {
            $content = "";
            foreach ($notifications as $notif) {
                if ($notif['type'] === 'clan_invite') {
                    $content .= "§aConvite para o clã §e{$notif['data']['clan']} §apor §e{$notif['data']['inviter']}\n";
                }
            }
            $form->setContent($content);
            foreach ($notifications as $notif) {
                $form->addButton("§aAceitar convite");
            }
        }
        $form->addButton("§cFechar");
        $player->sendForm($form);
    }

    private function openInviteAcceptForm(Player $player, string $clanName, string $inviter): void {
        $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) use ($clanName) {
            if ($data === null) return;
            if ($data === 0) {
                if ($this->plugin->getClanManager()->acceptInvite($player, $clanName)) {
                    $player->sendMessage("§aVocê entrou no clã!");
                } else {
                    $player->sendMessage("§cNão foi possível entrar no clã.");
                }
            }
            $this->openMainMenu($player);
        });
        $form->setTitle("Convite para Clã");
        $form->setContent("§aVocê foi convidado por §e{$inviter} §apara entrar no clã §e{$clanName}§a. Deseja aceitar?");
        $form->addButton("§aAceitar");
        $form->addButton("§cRecusar");
        $player->sendForm($form);
    }

    public function openCreateClanForm(Player $player): void {
        $form = new \jojoe77777\FormAPI\CustomForm(function (Player $player, ?array $data) {
            if ($data === null) return;
            $name = $data[0] ?? "";
            $tag = $data[1] ?? "";
            $colorIndex = (int)($data[2] ?? 0);
            $colors = ["§f", "§a", "§c", "§e", "§b", "§d", "§6", "§4"];
            $color = $colors[$colorIndex] ?? "§f";

            if (empty($name) || empty($tag)) {
                $player->sendMessage("§cPreencha todos os campos!");
                return;
            }
            if ($this->plugin->getClanManager()->createClan($name, $tag, $color, $player)) {
                $player->sendMessage("§aClã criado com sucesso!");
            } else {
                $player->sendMessage("§cNão foi possível criar o clã. Nome já existe ou você já está em um clã.");
            }
            $this->openMainMenu($player);
        });
        $form->setTitle("Criar Clã");
        $form->addInput("Nome do clã (sem cores)", "Ex: Sagaz");
        $form->addInput("Tag do clã (sem cores)", "Ex: SGZ");
        $form->addDropdown("Cor da tag", ["Branco", "Verde", "Vermelho", "Amarelo", "Azul", "Roxo", "Laranja", "Vermelho Escuro"]);
        $player->sendForm($form);
    }

    private function openClanListForm(Player $player): void {
        $clans = $this->plugin->getClanManager()->getClanList();
        $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) use ($clans) {
            if ($data === null) return;
            if ($data >= 0 && $data < count($clans)) {
                $clanNames = array_keys($clans);
                $clanName = $clanNames[$data];
                $this->openClanViewForm($player, $clans[$clanName]);
            } else {
                $this->openMainMenu($player);
            }
        });
        $form->setTitle("Lista de Clãs");
        $content = "";
        foreach ($clans as $clan) {
            $content .= "§e{$clan->getName()} §7- Nível {$clan->getLevel()} §f({$clan->getMemberCount()}/{$clan->getMaxMembers()} membros)\n";
        }
        $form->setContent($content ?: "§7Nenhum clã encontrado.");
        foreach ($clans as $clan) {
            $form->addButton("§a" . $clan->getName());
        }
        $form->addButton("§cVoltar");
        $player->sendForm($form);
    }

    private function openClanViewForm(Player $player, Clan $clan): void {
        $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) use ($clan) {
            if ($data === null) return;
            if ($data === 0) {
                $this->plugin->getDatabase()->addInvite($clan->getName(), $player->getName(), $player->getName());
                $player->sendMessage("§aSolicitação de entrada enviada para o clã §e{$clan->getName()}§a!");
                $leader = $this->plugin->getServer()->getPlayerExact($clan->getLeader());
                if ($leader) {
                    $this->plugin->getDatabase()->addNotification($leader->getName(), "join_request", [
                        'clan' => $clan->getName(),
                        'player' => $player->getName()
                    ]);
                    $leader->sendMessage("§e{$player->getName()} §asolicitou entrar no clã §e{$clan->getName()}§a!");
                }
            }
            $this->openClanListForm($player);
        });
        $form->setTitle($clan->getName());
        $form->setContent("§6Tag: §f" . $clan->getTag() . "\n§6Líder: §f" . $clan->getLeader() . "\n§6Nível: §f" . $clan->getLevel() . "\n§6Membros: §f" . $clan->getMemberCount() . "/" . $clan->getMaxMembers() . "\n§6Descrição:\n§7" . ($clan->getDescription() ?: "Nenhuma descrição"));
        $form->addButton("§aSolicitar Entrada");
        $form->addButton("§cVoltar");
        $player->sendForm($form);
    }
    public function openLeaderboardMenu(Player $player, bool $showClans = true): void {
    if ($showClans) {
        $this->showClanRanking($player);
    } else {
        $this->showPlayerRanking($player);
    }
}

private function showClanRanking(Player $player): void {
    $clans = $this->plugin->getClanManager()->getTopClans();
    $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) {
        if ($data === null) return;
        if ($data === 0) {
            $this->openLeaderboardMenu($player, false);
        }
    });
    $form->setTitle("📊 Ranking de Clãs");
    $content = "";
    $i = 1;
    foreach ($clans as $clanData) {
        $content .= "§e{$i}º §f{$clanData['name']} §7- §f{$clanData['kills']} kills\n";
        $i++;
        if ($i > 15) break;
    }
    $form->setContent($content ?: "§7Nenhum clã registrado.");
    $form->addButton("§aAlternar para Ranking de Kills");
    $form->addButton("§cFechar");
    $player->sendForm($form);
}

private function showPlayerRanking(Player $player): void {
    $players = $this->plugin->getClanManager()->getTopPlayers();
    $form = new \jojoe77777\FormAPI\SimpleForm(function (Player $player, ?int $data) {
        if ($data === null) return;
        if ($data === 0) {
            $this->openLeaderboardMenu($player, true);
        }
    });
    $form->setTitle("📊 Ranking de Kills");
    $content = "";
    $i = 1;
    foreach ($players as $playerData) {
        $content .= "§e{$i}º §f{$playerData['player']} §7- §f{$playerData['kills']} kills\n";
        $i++;
        if ($i > 15) break;
    }
    $form->setContent($content ?: "§7Nenhum jogador registrado.");
    $form->addButton("§aAlternar para Ranking de Clãs");
    $form->addButton("§cFechar");
    $player->sendForm($form);
}
}