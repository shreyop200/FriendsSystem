<?php

namespace FriendZone;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\command\{CommandSender, Command};
use pocketmine\event\player\{
    PlayerJoinEvent,
    PlayerQuitEvent,
    PlayerChatEvent
};

class FriendsZone extends PluginBase implements Listener{

    /** @var string */
    private $prefix = "§l§6FRIENDS§r§b »§r ";

    public function onEnable(): void{
        @mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info($this->prefix."§aFriendsSystem Activated!");
    }

    public function onJoin(PlayerJoinEvent $event): void{
        $player = $event->getPlayer();
        $name = $player->getName();

        // FILES
        if(!file_exists($this->getDataFolder().$name.".yml")){
            $playerfile = new Config($this->getDataFolder().$name.".yml", Config::YAML);
            $playerfile->set("Friend", []);
            $playerfile->set("Invitations", []);
            $playerfile->set("blocked", false);
            $playerfile->save();
        }else{
            $playerfile = new Config($this->getDataFolder().$name.".yml", Config::YAML);
            $invitations = $playerfile->get("Invitations");
            foreach($invitations as $invitation){
                $player->sendMessage($this->prefix."§a".$invitation."§r§e is now your friend!");
            }
            $friends = $playerfile->get("Friend");
            foreach($friends as $friend){
                $friendPlayer = $this->getServer()->getPlayerExact($friend);
                if($friendPlayer !== null){
                    $friendPlayer->sendMessage($this->prefix."§a".$player->getName()." §eis online now");
                }
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event): void{
        $player = $event->getPlayer();
        $name = $player->getName();

        // FILES ON QUIT
        $playerfile = new Config($this->getDataFolder().$name.".yml", Config::YAML);
        $friends = $playerfile->get("Friend");
        foreach($friends as $friend){
            $friendPlayer = $this->getServer()->getPlayerExact($friend);
            if($friendPlayer !== null){
                $friendPlayer->sendMessage($this->prefix."§a".$player->getName()." §6is offline now");
            }
        }
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
        if($cmd->getName() === "friends"){
            if($sender instanceof Player){
                $playerfile = new Config($this->getDataFolder().$sender->getName().".yml", Config::YAML);
                if(empty($args[0])){
                    $sender->sendMessage("§l§eFRIEND SYSTEM§r");
                    $sender->sendMessage("§6/friends §aaccept » §bAccept a friend request");
                    $sender->sendMessage("§6/friends §creject » §bReject a friend request");
                    $sender->sendMessage("§6/friends §ainvite <player> » §bSend a friend request");
                    $sender->sendMessage("§6/friends §cremove <player> » §bRemove a friend");
                    $sender->sendMessage("§6/friends §clist » §bList your friends");
                    $sender->sendMessage("§6/friends §cblocked <on/off> » §bToggle friend requests");
                    return true;
                }
                switch(strtolower($args[0])){
                    case "accept":
                        if(!isset($args[1])){
                            $sender->sendMessage($this->prefix."§cUsage: /friends accept <player>");
                            break;
                        }
                        $invited = $args[1];
                        $invitedfile = new Config($this->getDataFolder().$invited.".yml", Config::YAML);
                        $invitations = $invitedfile->get("Invitations");
                        if(!in_array($sender->getName(), $invitations)){
                            $sender->sendMessage($this->prefix."§c".$invited." has not invited you to be friends");
                            break;
                        }
                        $invitations = array_diff($invitations, [$sender->getName()]);
                        $invitedfile->set("Invitations", $invitations);
                        $friends = $invitedfile->get("Friend");
                        $friends[] = $sender->getName();
                        $invitedfile->set("Friend", $friends);
                        $invitedfile->save();
                        $sender->sendMessage($this->prefix."§aYou are now friends with ".$invited);
                        if(($invitedPlayer = $this->getServer()->getPlayerExact($invited)) !== null){
                            $invitedPlayer->sendMessage($this->prefix."§a".$sender->getName()." §ehas accepted your friend request");
                        }
                        break;
                    case "reject":
                        if(!isset($args[1])){
                            $sender->sendMessage($this->prefix."§cUsage: /friends reject <player>");
                            break;
                        }
                        $invited = $args[1];
                        $invitedfile = new Config($this->getDataFolder().$invited.".yml", Config::YAML);
                        $invitations = $invitedfile->get("Invitations");
                        if(!in_array($sender->getName(), $invitations)){
                            $sender->sendMessage($this->prefix."§c".$invited." has not invited you to be friends");
                            break;
                        }
                        $invitations = array_diff($invitations, [$sender->getName()]);
                        $invitedfile->set("Invitations", $invitations);
                        $invitedfile->save();
                        $sender->sendMessage($this->prefix."§aYou have rejected the friend request from ".$invited);
                        if(($invitedPlayer = $this->getServer()->getPlayerExact($invited)) !== null){
                            $invitedPlayer->sendMessage($this->prefix."§c".$sender->getName()." §ehas rejected your friend request");
                        }
                        break;
                    case "invite":
                        if(!isset($args[1])){
                            $sender->sendMessage($this->prefix."§cUsage: /friends invite <player>");
                            break;
                        }
                        $invited = $args[1];
                        if(($invitedPlayer = $this->getServer()->getPlayerExact($invited)) === null){
                            $sender->sendMessage($this->prefix."§c".$invited." is not online");
                            break;
                        }
                        $invitedfile = new Config($this->getDataFolder().$invited.".yml", Config::YAML);
                        $invitations = $invitedfile->get("Invitations");
                        if(in_array($sender->getName(), $invitations)){
                            $sender->sendMessage($this->prefix."§cYou have already invited ".$invited." to be friends");
                            break;
                        }
                        $invitations[] = $sender->getName();
                        $invitedfile->set("Invitations", $invitations);
                        $invitedfile->save();
                        $sender->sendMessage($this->prefix."§aYou have invited ".$invited." to be friends");
                        $invitedPlayer->sendMessage($this->prefix."§e".$sender->getName()." §ahas invited you to be friends. Type §a/friends accept ".$sender->getName()." §ato accept");
                        break;
                    case "remove":
                        if(!isset($args[1])){
                            $sender->sendMessage($this->prefix."§cUsage: /friends remove <player>");
                            break;
                        }
                        $friend = $args[1];
                        $friendfile = new Config($this->getDataFolder().$friend.".yml", Config::YAML);
                        $friends = $friendfile->get("Friend");
                        if(!in_array($sender->getName(), $friends)){
                            $sender->sendMessage($this->prefix."§c".$friend." is not your friend");
                            break;
                        }
                        $friends = array_diff($friends, [$sender->getName()]);
                        $friendfile->set("Friend", $friends);
                        $friendfile->save();
                        $sender->sendMessage($this->prefix."§aYou have removed ".$friend." from your friends list");
                        if(($friendPlayer = $this->getServer()->getPlayerExact($friend)) !== null){
                            $friendPlayer->sendMessage($this->prefix."§c".$sender->getName()." §ehas removed you from their friends list");
                        }
                        break;
                    case "list":
                        $playerfile = new Config($this->getDataFolder() . $sender->getName() . ".yml", Config::YAML);
                        $friends = $playerfile->get("Friend");
                        $sender->sendMessage($this->prefix."§6Your Friends:");
                        $sender->sendMessage(implode(", ", $friends));
                        break;
                    case "blocked":
                        if(!isset($args[1])){
                            $sender->sendMessage($this->prefix."§cUsage: /friends blocked <on/off>");
                            break;
                        }
                        $blockState = strtolower($args[1]);
                        if($blockState !== "on" && $blockState !== "off"){
                            $sender->sendMessage($this->prefix."§cInvalid state. Use 'on' or 'off'");
                            break;
                        }
                        $config = new Config($this->getDataFolder() . "blocked.yml", Config::YAML);
                        $config->set("Blocked", $blockState);
                        $config->save();
                        $sender->sendMessage($this->prefix."§aFriend requests are now ".$blockState);
                        break;
                    default:
                        $sender->sendMessage($this->prefix."§cInvalid subcommand. Use /friends for a list of commands");
                        break;
                }
            }
            return true;
        }
        return false;
    }
}

