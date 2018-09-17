<?php

if (!defined('ABSPATH')) {
    exit;
}

class Options {
    public static function setupMenu(): void
    {
        add_submenu_page('mp_dd_settings', 'Parser', 'Parser', 'edit_posts', 'mp_dd_parser', [Options::class, 'showOptionsPage']);
    }

    public static function showOptionsPage(): void
    {
        $active_tab = "wizardawn";
        if (isset($_GET['tab'])) {
            $active_tab = $_GET['tab'];
        }
        ?>
        <div class="wrap">
            <h1>Users Options</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=<?= $_GET['page'] ?>&tab=wizardawn" class="nav-tab <?= $active_tab == 'wizardawn' ? 'nav-tab-active' : '' ?>">Wizardawn</a>
                <a href="?page=<?= $_GET['page'] ?>&tab=donjon" class="nav-tab <?= $active_tab == 'donjon' ? 'nav-tab-active' : '' ?>">donjon</a>
            </h2>
            <?php
            switch ($active_tab) {
                case "wizardawn":
                    require_once "Wizardawn/Wizardawn.php";
                    break;
                case "donjon":
                    require_once "Donjon.php";
                    break;
            }
            ?>
        </div>
        <?php
    }
}
add_action('admin_menu', [Options::class, 'setupMenu']);
