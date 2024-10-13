import { defineConfig } from "vite";
import path from "path";
import fs from "fs-extra";

const plugin_dir_name = "phaser-game-template";
// a list of relative urls of files to include in the plugin build.
const plugin_files: string[] = ["phaser-game-template.php"];

async function plugin_prebuild() {
	const target_dir = `${__dirname}/dist/${plugin_dir_name}`;
	fs.emptyDirSync(target_dir);
	console.log("Plugin directory cleaned!");
}

async function plugin_write_bundle() {
	try {
		for (const file of plugin_files) {
			const srcFile = path.resolve(__dirname, file);
			const destFile = path.resolve(
				__dirname,
				`dist/${plugin_dir_name}/${file}`
			);

			await fs.copy(srcFile, destFile, {
				overwrite: true, // Allow overwriting existing files
			});
			console.log(`Copied: ${file}`);
		}
		console.log("Specified files copied to plugin folder!");
	} catch (err) {
		console.error("Error copying files:", err);
	}
}

function filterManualChunks(id: string) {
	if (id.includes("node_modules/phaser3-rex-plugins")) {
		return "phaser3-rex-plugins";
	}
	if (id.includes("node_modules/phaser")) {
		return "phaser";
	}
}

export default defineConfig({
	build: {
		outDir: path.resolve(__dirname, `dist/${plugin_dir_name}/assets`),
		emptyOutDir: false,
		manifest: true,
		rollupOptions: {
			input: {
				main: path.resolve(__dirname, `src/main.ts`),
			},
			output: {
				entryFileNames: "dist/[name].[hash].js",
				chunkFileNames: "dist/[name].[hash].js",
				assetFileNames: "game_assets/[name].[hash].[ext]",
				manualChunks: filterManualChunks,
			},
		},
	},
	server: {
		port: 30080,
	},
	preview: {
		port: 30173,
	},
	plugins: [
		{
			name: "clean-plugin-folder",
			buildStart: plugin_prebuild,
			writeBundle: plugin_write_bundle,
		},
	],
});
