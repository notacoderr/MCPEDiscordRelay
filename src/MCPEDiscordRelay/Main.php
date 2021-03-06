<?php
/*
 * MCPEDiscordRelay
 * Developer: Nomadjimbob
 * Website: https://github.com/nomadjimbob/MCPEDiscordRelay
 * Licensed under GNU GPL 3.0 (https://github.com/nomadjimbob/MCPEDiscordRelay/blob/master/LICENSE)
 */
declare(strict_types=1);

namespace MCPEDiscordRelay;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\scheduler\TaskHandler;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

class Main extends PluginBase implements Listener {

	public $attachment						= null;
	private $enabled							= false;
	private $discordWebHookURL		= "";
	private $discordWebHookName		= "";
        private $task    = null;

	public function onLoad() {

	}

	public function onEnable() {
		$this->saveDefaultConfig();
		$this->reloadConfig();

		if($this->getConfig()->get("enabled")) {
			$this->initTasks();
		}
		
		if($this->enabled) {
			$this->getLogger()->info(TextFormat::WHITE . "Plugin is Enabled");
			$this->sendToDiscord("MCPEDiscordRelay enabled");
		} else {
			$this->getLogger()->info(TextFormat::WHITE . "Plugin is Disabled");
		}
	}
	
	
	public function onDisable() {
		$this->sendToDiscord("MCPEDiscordRelay disabled");
		$this->endTasks();
		$this->enabled = false;
		$this->getLogger()->info(TextFormat::WHITE . "Plugin is Disabled");
	}
	
	
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command->getName()){
			case "version":
				$sender->sendMessage("1.0");
				return true;
			default:
				return false;
		}
	}
	

    public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event) {
        $player = $event->getPlayer();
        $message = $event->getMessage();

        $this->sendToDiscord("<".$player->getName()."> ".$message);
    }
	
	
	public function initTasks() {
		$url = $this->getConfig()->get("discord_webhook_url", "");
		$prefix = "https://discordapp.com/api/webhooks/";
		$prefixLength = strlen($prefix);
		if(substr($url, 0, $prefixLength) == $prefix && strlen($url) > $prefixLength) {
			$this->discordWebHookURL = $url;
			$this->discordWebHookName = $this->getConfig()->get("discord_webhook_name", "MCPEDiscordRelay");
		
			if($this->attachment == null) {
				$this->attachment = new Attachment();
				$this->getServer()->getLogger()->addAttachment($this->attachment);

                                $mtime = intval($this->getConfig()->get("discord_webhook_refresh", 10)) * 20;
                                $this->task = $this->getScheduler()->scheduleRepeatingTask(new Broadcast($this), $mtime);

                                $this->getServer()->getPluginManager()->registerEvents($this, $this);

				$this->enabled = true;
				return true;
			}		
		} else {
			$this->getLogger()->info(TextFormat::WHITE . "Webhook URL doesn't look right in config.yml");
		}
		
		$this->endTasks();
		return false;
	}
	
	
	public function endTasks() {
                if($this->task != null) {
                    $this->task->remove();
                    $this->task = null;
                }

		if($this->attachment != null) {
			$this->getServer()->getLogger()->removeAttachment($this->attachment);
			$this->attachment = null;
		}
	}


	public function sendToDiscord(string $msg) {
		if($this->enabled && $this->attachment != null) {
			$this->attachment->appendStream($msg);
		}
	}


        public function getDiscordWebHookURL() {
            return $this->discordWebHookURL;
        }


        public function getDiscordWebHookName() {
            return $this->discordWebHookName;
        }

	public function getEnabled() {
        return $this->enabled;
        }

	public function backFromAsync($player, $result) {

	}
}
