<?php
    class RecentLinks extends Modules {
        public function __init() {
            $this->addAlias("add_post","proc_post");
            $this->addAlias("update_post","proc_post");
            $this->addAlias("add_page","proc_add_page");
            $this->addAlias("update_page","proc_upd_page");
            $this->addAlias("sidebar","display");
        }
        static function __install() {
            $config = Config::current();
            $config->set("show_recent_links",True,True);
            $config->set("recent_links_reverse_order",False,True);
            $config->set("recent_links_title","Recent Links",True);
            $config->set("recent_links_display_count",5,True);
            $config->set("recent_links",array(),True);
        }
        static function __uninstall($confirm) {
            if ($confirm) {
                $config = Config::current();
                $config->remove("show_recent_links");
                $config->remove("recent_links_display_count");
                $config->remove("recent_links_save_count");
            }
        }
        public function settings_nav($navs)
		{
			if(Visitor::current()->group->can("change_settings"))
				$navs["recent_links_settings"] = array("title" => __("Recent Links", "recent-links"));

			return $navs;
		}

		public function admin_recent_links_settings($admin)
		{
			$config = Config::current();
			if(empty($_POST)) {
				return $admin->display("recent_links_settings");
			}
            if (isset($_POST['show_recent_links'])) {
                $showlinks = True;
            } else {
                $showlinks = False;
            }
            if (isset($_POST['recent_links_reverse_order'])) {
                $reverse = True;
            } else {
                $reverse = False;
            }
			if(($config->set("recent_links_title", $_POST['recent_links_title'])) && ($config->set("recent_links_display_count", $_POST['recent_links_display_count'])) && ($config->set("recent_links_reverse_order", $reverse)) && ($config->set("show_recent_links", $showlinks))) {
				Flash::notice(__("Settings updated."), "/admin/?action=recent_links_settings");
			}
		}
        public function display() {
            $config = Config::current();
            $links = $config->recent_links;
            if ((count($links) == 0) or (!$config->show_recent_links)) {
                return;
            }
            if ($config->recent_links_reverse_order) {
                $links = array_reverse($links);
            }
            echo("<h1>".$config->recent_links_title."</h1".PHP_EOL);
            echo('<ul class="recentlinks"'.PHP_EOL);
            foreach ($links as $link) {
                echo('<li><a href="'.$link['url'].'">'.$link['text'].'</a></li>'.PHP_EOL);
            }
            echo('</ul>'.PHP_EOL);
        }
        public function find_anchors($text) {
            $regex = "/<a .*?href=\"(.*?)\".*?>(.*?)<\/a>/";
            $matches = array();
            preg_match_all($regex,$text,$matches,PREG_SET_ORDER);
            return $matches;
        }
        public function add_link($href,$name) {
            $config = Config::current();
            $links = $config->recent_links;
            $link = array("url" => $href, "text" => $name);
            if (in_array($link,$links)) {
                return;
            }
            if (count($links) >= $config->recent_links_display_count) {
                array_shift($links);
            }
            $links[] = $link;
            $config->set("recent_links",$links,true);
        }
        public function find_and_process_links($text) {
            $config = Config::current();
		    if (in_array("textilize",$config->enabled_modules)) {
		        $text = Textilize::textile($text);
		    } else if (in_array("markdown",$config->enabled_modules)) {
			    $text = Markdown::markdownify($text);
            }
            $matches = $this->find_anchors($text);
            foreach ($matches as $match) {
                $href = $match[1];
                $name = $match[2];
                $this->add_link($href,$name);
            }
        }
        public function proc_post($post,$options) {
            switch ($post->feather) {
                case "text":
                case "Text":
                    $this->find_and_process_links($post->body);
                    break;
                case "Audio":
                case "audio":
                    $this->find_and_process_links($post->description);
                    break;
                case "Chat":
                case "chat":
                    $this->find_and_process_links($post->dialogue);
                    break;
                case "Link":
                case "link":
                    $this->add_link($post->source,$post->name);
                    $this->find_and_process_links($post->description);
                    break;
                case "photo":
                case "Photo":
                    $this->find_and_process_links($post->caption);
                    break;
                case "quote":
                case "Quote":
                    $this->find_and_process_links($post->quote);
                    $this->find_and_process_links($post->source);
                    break;
                case "video":
                case "Video":
                    $this->find_and_process_links($post->caption);
                    break;
            }
        }
        public function proc_add_page($page) {
            $this->find_and_process_links($page->body);
        }
        public function proc_upd_page($page,$old) {
            $this->find_and_process_links($page->body);
        }
    }
    
    $recentlinks = new RecentLinks();
    
?>
