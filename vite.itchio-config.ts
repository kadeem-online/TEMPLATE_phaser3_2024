import { defineConfig } from "vite";
import path from "path";

function filterManualChunks(id: string) {
	if (id.includes("node_modules/phaser3-rex-plugins")) {
		return "phaser3-rex-plugins";
	}
	if (id.includes("node_modules/phaser")) {
		return "phaser";
	}
}

export default defineConfig({
	appType: "spa",
	base: "",
	build: {
		outDir: path.resolve(__dirname, `dist/itch`),
		rollupOptions: {
			output: {
				entryFileNames: "[name].[hash].js",
				chunkFileNames: "[name].[hash].js",
				assetFileNames: "assets/[name].[hash].[ext]",
				manualChunks: filterManualChunks,
			},
		},
	},
	server: {
		port: 25080,
	},
	preview: {
		port: 25173,
	},
});
