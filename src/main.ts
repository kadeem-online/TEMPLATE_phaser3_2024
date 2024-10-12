import { runGame } from "./game/core";

function onPageLoad() {
	runGame();
}

window.addEventListener("load", onPageLoad);
