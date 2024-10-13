import Phaser from "phaser";

// Utilities
import { SCENES } from "../util/variables";
import { UTIL_getPublicDirectoryURLPrefix } from "../util/functions";

// Assets
import IMG_DEBUG_TILE from "../assets/images/debug_tile.png";
import IMG_DEBUG_BACKGROUND from "../assets/images/debug_background.jpg";

export default class BootScene extends Phaser.Scene {
	constructor() {
		super(SCENES.boot_scene);
	}

	preload() {
		this.load.setBaseURL(UTIL_getPublicDirectoryURLPrefix());

		this.load.image("debug_grass_tile", IMG_DEBUG_TILE);
		this.load.image("debug_background", IMG_DEBUG_BACKGROUND);
	}

	create() {
		this._DEBUG_banner();
	}

	_DEBUG_banner() {
		this.add.image(
			Number(this.game.config.width) / 2,
			Number(this.game.config.height) / 2,
			"debug_background"
		);

		// confirm the scene is viewable
		const debug_text = this.add.text(
			Number(this.game.config.width) / 2,
			Number(this.game.config.height) / 2,
			"Boot Scene Works.",
			{
				fontSize: 32,
				color: "#fafafa",
				stroke: "#272727",
				strokeThickness: 2,
			}
		);
		debug_text.setOrigin(0.5);

		// confirm asset load properly
		this.add.tileSprite(
			Number(this.game.config.width) / 2,
			Number(this.game.config.height) - 18 / 2,
			Number(this.game.config.width),
			18,
			"debug_grass_tile"
		);
	}
}
