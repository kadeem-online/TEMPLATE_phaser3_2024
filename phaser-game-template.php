<?php

/*
 * Plugin Name:       Phaser Game Template
 * Plugin URI:        https://example.com/plugins/phaser-game-template/
 * Description:       A basic plugin for a phaser 3 web game.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.2
 * Author:            Alexander Kadeem
 * Author URI:        https://kadeem.online/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       phaser-game-template
 * Domain Path:       /languages
 * Requires Plugins:  
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

################################################################################
# VARIABLES
################################################################################

define("PhaserGameTemplateVersion", "1.0.0");
define("PhaserGameTemplateShortcode", "phaser_game_template_shortcode");
define("PhaserGameTemplateLocalData", array(
  "PLUGIN_ASSET_URL" => plugins_url('assets/', __FILE__),
));

################################################################################
# FUNCTIONS
################################################################################

/**
 * Generates the content to be passed to the section that calls the given
 * shortcode in the form of a string.
 * 
 * @return string
 */
function PhaserGameTemplate_game_shotcode()
{
  return "
    <p>The game container will be placed here</p>
    <div id='game-container-id'>
    </div>
    <script></script>
  ";
}
add_shortcode(PhaserGameTemplateShortcode, "PhaserGameTemplate_game_shotcode");

/**
 * This function is called when the plugin is activated.
 * 
 * @return void
 */
function PhaserGameTemplate_on_plugin_activate()
{
  // Save the value for the specified version if it exists.
  if (defined(PhaserGameTemplateVersion)) {
    update_option("phaser_game_template_tracked_version", PhaserGameTemplateVersion);
  } else {
    $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), 'plugin');

    if (!empty($plugin_data['Version'])) {
      update_option("phaser_game_template_tracked_version", $plugin_data['Version']);
    }
  }

  // Run the generate dynamic scripts
  PhaserGameTemplate_generate_dynamic_scripts();
  return;
}
register_activation_hook(__FILE__, 'PhaserGameTemplate_on_plugin_activate');

/**
 * Checks if the plugin has been updated, if so it regenerates the enqueue scripts
 * to reflect changes on the scripts specified within the manifest.json
 * 
 * @return void
 */
function PhaserGameTemplate_on_plugin_update()
{
  // Check for a saved version that is being tracked
  $stored_version = get_option('phaser_game_template_tracked_version');

  // if no saved version exists in the options, save the current version.
  if (empty($stored_version)) {
    $_FLAG_version_saved = false;

    if (defined(PhaserGameTemplateVersion)) {
      update_option("phaser_game_template_tracked_version", PhaserGameTemplateVersion);
      $_FLAG_version_saved = true;
    } else {
      $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), 'plugin');

      if (!empty($plugin_data['Version'])) {
        update_option("phaser_game_template_tracked_version", $plugin_data['Version']);
        $_FLAG_version_saved = true;
      }
    }

    // Generate dynamic scripts if the version has been ascertained.
    if ($_FLAG_version_saved) {
      PhaserGameTemplate_generate_dynamic_scripts();
    }

    // if a version number cannot be acertained do not generate dynamic scripts
    // to avoid potential excess processing.
    return;
  }

  try {
    $current_version = "";

    if (defined(PhaserGameTemplateVersion)) {
      $current_version = PhaserGameTemplateVersion;
    } else {
      $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), 'plugin');

      if (!empty($plugin_data['Version'])) {
        $current_version = $plugin_data["Version"];
      } else {
        throw new Exception("Failed to get the current plugin version.");
      }
    }

    // run updates if the current version is larger than the version saved.
    if (version_compare($current_version, $stored_version, '>')) {
      update_option("phaser_game_template_tracked_version", $current_version);

      PhaserGameTemplate_generate_dynamic_scripts();
    }
  } catch (\Throwable $th) {
    // TODO: Handle exceptions as desired
  }

  return;
}
add_action('admin_init', 'PhaserGameTemplate_on_plugin_update');

function PhaserGameTemplate_generate_dynamic_scripts()
{
  $plugin_dir = plugin_dir_path(__FILE__);

  // get the manifest file
  $manifest_file_path = $plugin_dir . "assets/.vite/manifest.json";
  if (!file_exists($manifest_file_path)) {
    return;
  }

  // get the decoded data
  $manifest = json_decode(file_get_contents($manifest_file_path), true);
  if (!$manifest) {
    return;
  }

  // prepare the manifest scripts into a usable array.
  $wpScripts = array();
  $scraped_characters = ['.js', '.ts', '_', "."]; // remove from handles
  foreach ($manifest as $key => $entry) {
    if (isset($entry['file'])) {
      // script handle
      $file = basename($entry['file']);
      $scriptHandle = str_replace($scraped_characters, '', $file);

      // script dependencies
      $deps = array();
      if (isset($entry['imports']) && is_array($entry['imports'])) {
        foreach ($entry['imports'] as $import) {
          $import_key = basename($import);
          $import_handle = str_replace($scraped_characters, '', $import_key);
          array_push($deps, $import_handle);
        }
      }

      $wpScripts[$scriptHandle] = [
        'file' => plugins_url("assets/" . $entry['file'], __FILE__),
        'deps' => $deps,
      ];
    }
  }

  // Create the enqueue file and add the scripts;
  $enqueue_file_path = $plugin_dir . "enqueue-game-scripts.php";
  $enqueue_content = "<?php\n\n";

  // Create the contents of the file
  $enqueue_content .= PhaserGameTemplate_generate_scripts_enqueue($wpScripts);
  $enqueue_content .= PhaserGameTemplate_generate_scripts_module_tags($wpScripts);

  // update or save the php file contents
  file_put_contents($enqueue_file_path, $enqueue_content);
  return;
}

function PhaserGameTemplate_generate_scripts_enqueue(array $game_scripts)
{
  $_version = "null";

  if (defined(PhaserGameTemplateVersion)) {
    $_version = PhaserGameTemplateVersion;
  } else {
    $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), 'plugin');

    if (!empty($plugin_data['Version'])) {
      $_version = $plugin_data['Version'];
    }
  }

  $content = "function PhaserGameTemplate_enqueue_scripts(){\n";

  // add the lines
  foreach ($game_scripts as $handle => $script) {
    try {
      // enqueue script
      $_line = "wp_enqueue_script("; // open enqueue
      $_line .= "'" . $handle . "',"; // handle
      $_line .= "'" . $script["file"] . "',"; // file url
      $_line .= var_export($script["deps"], true) . ","; // dependencies
      $_line .= "'$_version',"; // version
      $_line .= "true"; // in footer
      $_line .= ");\n"; // close enqueue

      // add line once it is fully generated successfully.
      $content .= $_line;
    } catch (\Throwable $th) {
      // TODO: handle failure to generate the function content
      wp_die($th);
    }
  }

  // add the localization to all included scripts
  $handles = array_keys($game_scripts);
  $content .= PhaserGameTemplate_generate_script_localization($handles);

  $content .= "}\n\n";
  return $content;
}

function PhaserGameTemplate_generate_scripts_module_tags(array $game_scripts)
{
  $content = "function PhaserGameTemplate_add_module_type(";
  $content .= "\$tag, \$handle, \$src){\n";

  try {
    //   // get array of script handles to be checked
    $game_script_haystack = array_keys($game_scripts);

    $content .= "\$game_script_haystack = ";
    $content .= var_export($game_script_haystack, true) . ";\n";

    // Check if the tag is a game script
    $content .= "if(in_array(\$handle, \$game_script_haystack)){\n";
    $content .= "\$tag = ";
    $content .= "'<script type=\"module\" src=\"' . ";
    $content .= "esc_url( \$src ) . ";
    $content .= "'\" id=\"' . \$handle . '\"></script>';\n";
    $content .= "}\n";

    // return the tag
    $content .= "return \$tag;\n";

  } catch (\Throwable $th) {
    // TODO: handle errors in the function.
    return "";
  }

  $content .= "}\n\n";
  return $content;
}

function PhaserGameTemplate_generate_script_localization(array $game_script_handles)
{
  if (!defined("PhaserGameTemplateLocalData")) {
    return "";
  }

  $content = "\n";

  // add the wordpress local variables to the function
  $content .= "\$WORDPRESS_DATA = ";
  $content .= var_export(PhaserGameTemplateLocalData, true) . ";\n\n";

  // generate a localization line for each handle
  foreach ($game_script_handles as $handle) {
    try {
      $_line = "wp_localize_script(";
      $_line .= "'$handle', 'WORDPRESS_DATA', \$WORDPRESS_DATA";
      $_line .= ");\n";

      $content .= $_line;
    } catch (\Throwable $th) {
      // TODO: handle errors from localization generation.
    }
  }

  return $content;
}

function PhaserGameTemplate_enqueue_shortcode_scripts()
{
  include_once plugin_dir_path(__FILE__) . "enqueue-game-scripts.php";

  add_action("wp_enqueue_scripts", "PhaserGameTemplate_enqueue_scripts");
  add_filter('script_loader_tag', 'PhaserGameTemplate_add_module_type', 10, 3);
}

function PhaserGameTemplate_check_shortcode_in_posts($posts)
{
  if (empty($posts)) {
    return $posts;
  }

  if (is_admin()) {
    return;
  }

  // Check if the [my_shortcode] is present in any of the posts
  $shortcode_found = false;

  foreach ($posts as $post) {
    if (has_shortcode($post->post_content, PhaserGameTemplateShortcode)) {
      $shortcode_found = true;
      break;
    }
  }

  // If the shortcode is found, enqueue the script
  if ($shortcode_found) {
    PhaserGameTemplate_enqueue_shortcode_scripts();
  }

  return $posts;
}
add_action('the_posts', 'PhaserGameTemplate_check_shortcode_in_posts');
