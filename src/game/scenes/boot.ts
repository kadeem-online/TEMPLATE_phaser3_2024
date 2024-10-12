import Phaser from "phaser";
import { SCENES } from "../util/variables";
import { UTIL_getPublicDirectoryURLPrefix } from "../util/functions";

export default class BootScene extends Phaser.Scene {
	constructor() {
		super(SCENES.boot_scene);
	}

	preload() {
		this.load.setBaseURL(UTIL_getPublicDirectoryURLPrefix());

		this.load.image("debug_grass_tile", "/images/debug_tile.png");
	}

	create() {
		this._DEBUG_banner();
	}

	_DEBUG_banner() {
		this.cameras.main.setBackgroundColor(0x272727);

		// confirm the scene is viewable
		const debug_text = this.add.text(
			Number(this.game.config.width) / 2,
			Number(this.game.config.height) / 2,
			"Boot Scene Works.",
			{
				fontSize: 32,
				color: "#fafafa",
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
